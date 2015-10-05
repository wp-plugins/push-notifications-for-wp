<?php
/*
Plugin Name: Push Notifications for WordPress (Lite)
Plugin URI: http://delitestudio.com/wordpress/push-notifications-for-wordpress/
Description: Send push notifications to iOS, Android, and Fire OS devices when you publish a new post.
Version: 1.4
Author: Delite Studio S.r.l.
Author URI: http://www.delitestudio.com/
*/

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}
if (class_exists('PNFW_Push_Notifications_for_Posts') || class_exists('PNFW_Push_Notifications_for_WordPress')) {
 wp_die(
  __("To activate Push Notifications for WordPress (Lite) you must first disable Push Notifications for Posts and Push Notifications for WordPress", 'pnfw'),
  __("Plugin Activation Error", 'pnfw'),
  array('response' => 200, 'back_link' => TRUE)
 );

 exit;
}


if (!function_exists('pnfw_log')) {
 define("PNFW_SYSTEM_LOG", 0);
 define("PNFW_IOS_LOG", 1);
 define("PNFW_ANDROID_LOG", 2);
 define("PNFW_KINDLE_LOG", 3);
 define("PNFW_FEEDBACK_PROVIDER_LOG", 4);
 define("PNFW_ALERT_LOG", 5);

 function pnfw_log($type, $text) {
  global $wpdb;

  $table_name = $wpdb->get_blog_prefix() . 'push_logs';

  $data = array('type' => $type, 'text' => $text, 'timestamp' => current_time('mysql'));
  $wpdb->insert($table_name, $data, array('%d', '%s', '%s'));
 }
}

require_once dirname(__FILE__) . '/admin/class-pnfw-admin.php';
require_once dirname(__FILE__) . '/includes/class-pnfw-sender-manager.php';
require_once dirname(__FILE__) . '/includes/class-pnfw-ios-feedback-provider.php';




$push_notifications_for_wordpress = new PNFW_Push_Notifications_for_WordPress_Lite();

final class PNFW_Push_Notifications_for_WordPress_Lite {
 const MIN_PHP_REQUIRED = '5.3.0';
 const DB_VERSION = 1;
 const POSTS_PER_PAGE = 40;
 const USER_ROLE = 'app_subscriber';

 public function __construct() {
  register_activation_hook(__FILE__, array($this, 'activate'));
  register_deactivation_hook(__FILE__, array($this, 'deactivate'));
  add_filter('query_vars', array($this, 'manage_routes_query_vars'));
  add_action('init', array($this, 'plugin_init'));
  add_action('admin_init', array($this, 'admin_init'));
  add_action('template_redirect', array($this, 'front_controller'));

  $plugin_filename = plugin_basename(__FILE__);
  add_filter("plugin_action_links_$plugin_filename", array($this, 'settings_link'));

  add_filter('upload_mimes', array($this, 'allow_pem'));

  add_action('add_meta_boxes', array($this, 'adding_meta_box'), 10, 2);
  add_action('save_post', array($this, 'save_postdata'), 1);

  add_action('delete_user', array($this, 'delete_user'));
  add_action('deleted_user', array($this, 'user_deleted'));
  add_action('delete_post', array($this, 'post_delete'));
 }

 /**
	  * Stuff that's done when the plugin is activated
	  */
 function activate() {
  if (version_compare(PHP_VERSION, self::MIN_PHP_REQUIRED, '<')) {
   deactivate_plugins(basename(__FILE__));

   wp_die(
    sprintf(__("Push Notifications for WordPress (Lite) requires PHP version %s or later.", 'pnfw'), self::MIN_PHP_REQUIRED),
    __("Plugin Activation Error", 'pnfw'),
    array('response' => 200, 'back_link' => TRUE)
   );

   return;
  }


  if (is_multisite()) {
   deactivate_plugins(basename(__FILE__));

   wp_die(
    __("Push Notifications for WordPress (Lite) is incompatible with WordPress Multisite.", 'pnfw'),
    __("Plugin Activation Error", 'pnfw'),
    array('response' => 200, 'back_link' => TRUE)
   );

   return;
  }


  global $wpdb;

  // If first run save actual db version to avoid upgrade
  $table_name = $wpdb->get_blog_prefix() . 'push_tokens';
  if (is_null($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)))) {
   add_option('pnfw_db_version', self::DB_VERSION);
  }
  else {
   $this->upgrade();
  }

  // Create tables 	
  $table_name = $wpdb->get_blog_prefix() . 'push_tokens';
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR (1000) NULL,
    `os` VARCHAR (50) NULL,
    `lang` VARCHAR (2) NULL,
    `timestamp` DATETIME NOT NULL,
    `user_id` BIGINT (20) UNSIGNED NULL,
    `active` BOOLEAN,
    `activation_code` VARCHAR (40) NULL,
    PRIMARY KEY (`id`));";
  $wpdb->query($sql);

  $table_name = $wpdb->get_blog_prefix() . 'push_viewed';
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    `user_id` BIGINT (20) UNSIGNED NOT NULL,
    `post_id` BIGINT (20) UNSIGNED NOT NULL,
    `timestamp` DATETIME NOT NULL,
    PRIMARY KEY (`user_id`, `post_id`));";
  $wpdb->query($sql);

  $table_name = $wpdb->get_blog_prefix() . 'push_sent';
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    `user_id` BIGINT (20) UNSIGNED NOT NULL,
    `post_id` BIGINT (20) UNSIGNED NOT NULL,
    `timestamp` DATETIME NOT NULL,
    PRIMARY KEY (`user_id`, `post_id`));";
  $wpdb->query($sql);

  $table_name = $wpdb->get_blog_prefix() . 'push_excluded_categories';
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    `user_id` BIGINT (20) UNSIGNED NOT NULL,
    `category_id` BIGINT (20) UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `category_id`));";
  $wpdb->query($sql);

  $table_name = $wpdb->get_blog_prefix() . 'push_logs';
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` INT UNSIGNED NOT NULL,
    `text` TEXT,
    `timestamp` DATETIME NOT NULL,
    PRIMARY KEY (`id`));";
  $wpdb->query($sql);

  update_option('pnfw_posts_per_page', self::POSTS_PER_PAGE);

  // Creare new user role
  add_role(self::USER_ROLE,
   __('App Subscriber', 'pnfw'),
   array('read' => true)
  );

  $this->manage_routes();
  flush_rewrite_rules();

  // Schedule Feedback Provider if needed	
  if (get_option("pnfw_ios_push_notifications")) {
   global $feedback_provider;
   $feedback_provider->run();
  }
 }

 /**
	  * Stuff that's done when the plugin is deactivated
	  */
 function deactivate() {
  remove_role(self::USER_ROLE);

  global $feedback_provider;
  $feedback_provider->stop();
 }

 function plugin_init() {
  load_plugin_textdomain('pnfw', false, basename(dirname(__FILE__)) . '/languages/');
  $this->upgrade();
  $this->manage_routes();
  $this->register_user_taxonomy();
 }

 function admin_init() {
  $custom_post_types = get_post_types(array("public" => 1));
  $custom_post_types = array_diff($custom_post_types, array("page", "attachment"));
  $taxonomies = get_object_taxonomies($custom_post_types);
  foreach ($taxonomies as $taxonomy) {
   add_action("delete_{$taxonomy}", array($this, 'delete_term'));
  }
 }

 function manage_routes() {




  add_rewrite_rule('pnfw/([^/]+)/?$', 'index.php?control_action=$matches[1]', 'top');
 }

 function manage_routes_query_vars($query_vars) {
  $query_vars[] = 'control_action';
  return $query_vars;
 }

 function register_user_taxonomy() {
  register_taxonomy(
   'user_cat',
   'user',
   array(
    'public' => false,
    'labels' => array(
     'name' => __('User Categories', 'pnfw'),
     'singular_name' => __('User Category', 'pnfw'),
     'menu_name' => __('Categories', 'pnfw'),
     'search_items' => __('Search User Categories', 'pnfw'),
     'popular_items' => __('Popular User Categories', 'pnfw'),
     'all_items' => __('All User Categories', 'pnfw'),
     'edit_item' => __('Edit User Category', 'pnfw'),
     'update_item' => __('Update User Category', 'pnfw'),
     'add_new_item' => __('Add New User Category', 'pnfw'),
     'new_item_name' => __('New User Category Name', 'pnfw'),
     'separate_items_with_commas' => __('Separate user categories with commas', 'pnfw'),
     'add_or_remove_items' => __('Add or remove user categories', 'pnfw'),
     'choose_from_most_used' => __('Choose from the most popular user categories', 'pnfw'),
    ),
    'rewrite' => false,
    'capabilities' => array(
     'manage_terms' => 'edit_users',
     'edit_terms' => 'edit_users',
     'delete_terms' => 'edit_users',
     'assign_terms' => 'read',
    ),
    'update_count_callback' => array(&$this, 'update_user_category_count')
   )
  );
 }

 function update_user_category_count($terms, $taxonomy) {
  global $wpdb;

  foreach ((array)$terms as $term) {
   $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term));

   do_action('edit_term_taxonomy', $term, $taxonomy);
   $wpdb->update($wpdb->term_taxonomy, compact('count'), array('term_taxonomy_id' => $term));
   do_action('edited_term_taxonomy', $term, $taxonomy);
  }
 }
 function front_controller() {
  global $wp_query;

  $control_action = isset($wp_query->query_vars['control_action']) ? $wp_query->query_vars['control_action'] : '';

  switch ($control_action) {
   case 'register':
    require_once(dirname(__FILE__) . '/includes/api/class-pnfw-api-register.php');
    $register = new PNFW_API_Register();
    break;
   case 'unregister':
    require_once(dirname(__FILE__) . '/includes/api/class-pnfw-api-unregister.php');
    $unregister = new PNFW_API_Unregister();
    break;
   case 'categories':
    require_once(dirname(__FILE__) . '/includes/api/class-pnfw-api-categories.php');
    $categories = new PNFW_API_Categories();
    break;






   case 'activate':
    require_once(dirname(__FILE__) . '/includes/api/class-pnfw-api-activate.php');
    $activate = new PNFW_API_Activate();
    break;
   case 'posts':
    require_once(dirname(__FILE__) . '/includes/api/class-pnfw-api-posts.php');
    $posts = new PNFW_API_Posts();
    break;
  }
 }

 /**
	  * Place a link to the Settings page right from the WordPress Installed Plugins page
	  */
 function settings_link($links) {
  $url = admin_url('admin.php?page=pnfw-settings-identifier');
  $settings_link = "<a href='$url'>" . __("Settings", 'pnfw') . "</a>";
  array_unshift($links, $settings_link);

  return $links;
 }

  /**
	  * Add file extension 'PEM' to the list of acceptable file extensions WordPress
	  * checks during media uploads
	  */
 function allow_pem($mimes) {
  $mimes['pem'] = 'application/x-pem-file';
  return $mimes;
 }

 /**
	  * Add a meta box to the new post/new custom post type edit screens
	  */
 function adding_meta_box($post_type, $post) {
  $enabled_post_types = get_option('pnfw_enabled_post_types', array());

  if (empty($enabled_post_types) || !in_array($post_type, $enabled_post_types)) {
   return false;
  }

  add_meta_box(
   'pnfw-meta-box',
   __('Push Notifications', 'pnfw'),
   array($this, 'render_meta_box'),
   $post_type,
   'side',
   'high'
  );
 }

 /**
	  * Print the meta box content
	  */
 function render_meta_box($post) {
  wp_nonce_field('pnfw_meta_box', 'pnfw_meta_box_nonce');

  $value = get_post_meta($post->ID, 'pnfw_do_not_send_push_notifications_for_this_post', true);

  ?>
  <label><input type="checkbox"<?php echo (!empty($value) ? ' checked="checked"' : null) ?> value="1" name="pnfw_do_not_send_push_notifications_for_this_post" id="pnfw-do-not-send-push-notifications-for-this-post" /> <?php echo sprintf(__('Do not send for this %s', 'pnfw'), strtolower(get_post_type_object($post->post_type)->labels->singular_name)); ?></label>

  <?php
  $user_cat = get_post_meta($post->ID, 'pnfw_user_cat', true); ?>

  <div id='user-categories'>
   <ul>
    <li><strong id='send-and-make-visible-only-to-box'><?php _e('Send and make visible only to', 'pnfw'); ?>:</strong></li>

    <li>
     <input type="radio" name="user_cat" id="user_cat-all" value="all" <?php checked($user_cat, ''); ?> />
     <label for="user_cat-all"><?php _e('All', 'pnfw'); ?></label>
    </li>

    <li>
     <input type="radio" name="user_cat" id="user_cat-anonymous-users" value="anonymous-users" <?php checked($user_cat, 'anonymous-users'); ?> />
     <label for="user_cat-anonymous-users"><?php _e('Anonymous users', 'pnfw'); ?></label>
    </li>

    <li>
     <input type="radio" name="user_cat" id="user_cat-registered-users" value="registered-users" <?php checked($user_cat, 'registered-users'); ?> />
     <label for="user_cat-registered-users"><?php _e('Registered users', 'pnfw'); ?></label>
    </li>
   </ul>
  </div> <!-- user-categories -->


  <div style="color:#999;">
   <?php _e('Do you want to create user groups?', 'pnfw'); ?>
   <a href="http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/">
    <?php _e('Upgrade now to Push Notifications for WordPress', 'pnfw'); ?> &rarr;
   </a>
  </div>

 <?php }

 /**
	  * When the post/custom post type is saved, saves our custom data
	  */
 function save_postdata($postid) {
  // Check if our nonce is set.
  if (!isset( $_POST['pnfw_meta_box_nonce']))
   return $postid;

  $nonce = $_POST['pnfw_meta_box_nonce'];

  if (!wp_verify_nonce($nonce, 'pnfw_meta_box'))
   return $postid;

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) // stop it from being called during auto drafts
   return false;

  if (!current_user_can('edit_post', $postid))
   return false;

  $enabled_post_types = get_option('pnfw_enabled_post_types', array());

  if (empty($postid) || isset($_POST['post_type']) && !in_array($_POST['post_type'], $enabled_post_types))
   return false;

  update_option('pnfw_last_save_timestamp', time());

  if (isset($_POST['pnfw_do_not_send_push_notifications_for_this_post'])) {
   add_post_meta($postid, 'pnfw_do_not_send_push_notifications_for_this_post', true, true);
  }
  else {
   delete_post_meta($postid, 'pnfw_do_not_send_push_notifications_for_this_post');
  }

  if (isset($_POST['user_cat'])) {
   $cat = $_POST['user_cat'];

   delete_post_meta($postid, 'pnfw_user_cat');

   if ($cat != 'all') {
    add_post_meta($postid, 'pnfw_user_cat', $cat, true);
   }
  }
 }

 /**
	  * Remove rows from custom tables when a user is deleted.
	  */
 function user_deleted($user_id) {
  pnfw_log(PNFW_SYSTEM_LOG, sprintf(__("Automatically deleted the tokens for user %s.", 'pnfw'), $user_id));

  global $wpdb;
  $wpdb->delete($wpdb->get_blog_prefix().'push_tokens', array("user_id" => $user_id));
  $wpdb->delete($wpdb->get_blog_prefix().'push_excluded_categories', array('user_id' => $user_id));
 }

 function delete_user($user_id) {
  wp_delete_object_term_relationships($user_id, 'user_cat');
 }

 function post_delete($post_id) {
  global $wpdb;
  $wpdb->delete($wpdb->get_blog_prefix().'push_viewed', array('post_id' => $post_id));
  $wpdb->delete($wpdb->get_blog_prefix().'push_sent', array('post_id' => $post_id));
 }

 function delete_term($term_id) {
  pnfw_log(PNFW_SYSTEM_LOG, sprintf(__("Automatically deleted excluded category %d.", 'pnfw'), $term_id));

  global $wpdb;
  $wpdb->delete($wpdb->get_blog_prefix().'push_excluded_categories', array('category_id' => $term_id));
 }

 function upgrade() {
  global $wpdb;
  switch(get_option('pnfw_db_version', 0)) {
   case 0: {
    // Must be done BEFORE pnfw_log
    $table_name = $wpdb->get_blog_prefix() . 'push_logs';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `type` INT UNSIGNED NOT NULL,
      `text` TEXT,
      `timestamp` DATETIME NOT NULL,
      PRIMARY KEY (`id`));";
    $wpdb->query($sql);

    pnfw_log(PNFW_SYSTEM_LOG, sprintf(__('Upgrading from version %d.', 'pnfw'), 0));

    // Create new tables
    $table_name = $wpdb->get_blog_prefix() . 'push_viewed';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      `user_id` BIGINT (20) UNSIGNED NOT NULL,
      `post_id` BIGINT (20) UNSIGNED NOT NULL,
      `timestamp` DATETIME NOT NULL,
      PRIMARY KEY (`user_id`, `post_id`));";
    $wpdb->query($sql);

    $table_name = $wpdb->get_blog_prefix() . 'push_sent';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      `user_id` BIGINT (20) UNSIGNED NOT NULL,
      `post_id` BIGINT (20) UNSIGNED NOT NULL,
      `timestamp` DATETIME NOT NULL,
      PRIMARY KEY (`user_id`, `post_id`));";
    $wpdb->query($sql);

    $table_name = $wpdb->get_blog_prefix() . 'push_excluded_categories';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      `user_id` BIGINT (20) UNSIGNED NOT NULL,
      `category_id` BIGINT (20) UNSIGNED NOT NULL,
      PRIMARY KEY (`user_id`, `category_id`));";
    $wpdb->query($sql);

    // Add user_id column
    $push_tokens = $wpdb->get_blog_prefix().'push_tokens';
    $wpdb->query("ALTER TABLE {$push_tokens} ADD (
      `user_id` BIGINT (20) UNSIGNED NULL,
      `lang` VARCHAR (2) NULL,
      `active` BOOLEAN,
      `activation_code` VARCHAR (40) NULL);");

    // Creare new user role
    add_role(self::USER_ROLE,
     __('App Subscriber', 'pnfw'),
     array('read' => true)
    );

    // Create a user for every token with user_id NULL
    $rows = $wpdb->get_results("SELECT token, os FROM {$push_tokens} WHERE user_id IS NULL");
    foreach ($rows as $row) {
     // Create anonymous wordpress user
     $user_id = wp_insert_user(array(
      'user_login' => $this->create_unique_user_login(),
      'user_email' => NULL,
      'user_pass' => NULL,
      'role' => self::USER_ROLE
     ));

     // Add its user_id to push_tokens table
     if (!is_wp_error($user_id)) {
      $wpdb->update($push_tokens, array('user_id' => $user_id, 'active' => true), array('token' => $row->token, 'os' => $row->os));
     }
    }

    // Drop deprecated table
    $table_name = $wpdb->get_blog_prefix() . 'push_notifications_sent_per_day';
    $wpdb->query("DROP TABLE IF EXISTS $table_name;");

    // Remove deprecated register page
    $page = get_page_by_path('register');
    if (isset($page))
     wp_delete_post($page->ID, true);

    // Disable old feedback provider
    wp_clear_scheduled_hook('ds_feedback_provider_event');

    // Rename options
    $this->rename_option('ds_enable_push_notifications', 'pnfw_enable_push_notifications');
    $this->rename_option('ds_ios_push_notifications', 'pnfw_ios_push_notifications');
    $this->rename_option('ds_android_push_notifications', 'pnfw_android_push_notifications');
    $this->rename_option('ds_kindle_push_notifications', 'pnfw_kindle_push_notifications');
    $this->rename_option('ds_ios_use_sandbox', 'pnfw_ios_use_sandbox');
    $this->rename_option('ds_sandbox_ssl_certificate_media_id', 'pnfw_sandbox_ssl_certificate_media_id');
    $this->rename_option('ds_sandbox_ssl_certificate_password', 'pnfw_sandbox_ssl_certificate_password');
    $this->rename_option('ds_production_ssl_certificate_media_id', 'pnfw_production_ssl_certificate_media_id');
    $this->rename_option('ds_production_ssl_certificate_password', 'pnfw_production_ssl_certificate_password');
    $this->rename_option('ds_google_api_key', 'pnfw_google_api_key');
    $this->rename_option('ds_adm_client_id', 'pnfw_adm_client_id');
    $this->rename_option('ds_adm_client_secret', 'pnfw_adm_client_secret');
    $this->rename_option('ds_api_consumer_key', 'pnfw_api_consumer_key');
    $this->rename_option('ds_api_consumer_secret', 'pnfw_api_consumer_secret');
    $this->rename_option('ds_enabled_post_types', 'pnfw_enabled_post_types');

    // Upgrade option to new values
    $post_types = get_option('pnfw_enabled_post_types', array());
    $post_types[] = 'post';
    update_option('pnfw_enabled_post_types', $post_types);

    // Schedule new Feedback Provider if needed	
    if (get_option("pnfw_ios_push_notifications")) {
     global $feedback_provider;
     $feedback_provider->run();
    }

    $this->manage_routes();
    flush_rewrite_rules();

    pnfw_log(PNFW_SYSTEM_LOG, sprintf(__('Db version %d upgraded.', 'pnfw'), 0));
    update_option('pnfw_db_version', self::DB_VERSION);
   }
  }
 }
 private function rename_option($old_option, $new_option) {
  $old_value = get_option($old_option);
  if ($old_value === false)
   return;
  update_option($new_option, $old_value);
  delete_option($old_option);
 }

 private function create_unique_user_login() {
  $tmp_user_login = 'anonymous' . wp_rand();
  return username_exists($tmp_user_login) ? $this->create_unique_user_login() : $tmp_user_login;
 }
 function opt_parameter($parameter) {
  $pars = strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ? $_POST : $_GET;

  if (!array_key_exists($parameter, $pars))
   return NULL;

  $res = filter_var($pars[$parameter], FILTER_SANITIZE_STRING);

  return $res ? $res : NULL;
 }
}



if (!function_exists('pnfw_get_post')) {
 function pnfw_get_post($key, $default = false) {
  return isset($_POST[$key]) ? $_POST[$key] : $default;
 }
}

if (!function_exists('pnfw_get_term_taxonomy')) {
 function pnfw_get_term_taxonomy($term_id) {
  global $wpdb;
  return $wpdb->get_var($wpdb->prepare("SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id=%d", $term_id));
 }
}

if (!function_exists('pnfw_get_post_lang')) {
 function pnfw_get_post_lang($post_id) {
  return null;

 }
}

if (!function_exists('pnfw_get_normalized_term_id')) {
 function pnfw_get_normalized_term_id($term_id) {
  return $term_id;

 }
}

if (!function_exists('pnfw_get_wpml_langs')) {
 function pnfw_get_wpml_langs() {
  $lang = array();
  return $lang;
 }
}

if (!function_exists('pnfw_suppress_filters')) {
 function pnfw_suppress_filters() {
  $ret = true;





  return $ret;
 }
}
if (!function_exists('pnfw_is_exclusive_user_member_of_blog')) {
 function pnfw_is_exclusive_user_member_of_blog($user_id = 0, $blog_id = 0) {
  $user_id = (int)$user_id;
  $blog_id = (int)$blog_id;

  if (empty($user_id))
   $user_id = get_current_user_id();

  if (empty($blog_id))
   $blog_id = get_current_blog_id();

  $blogs = get_blogs_of_user($user_id);

  return array_key_exists($blog_id, $blogs) && count($blogs) == 1;
 }
}

if (!function_exists('pnfw_starts_with')) {
 function pnfw_starts_with($haystack, $needle) {
  $length = strlen($needle);
  return (substr($haystack, 0, $length) === $needle);
 }
}
