<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

$admin_dashboard = new PNFW_Admin();

final class PNFW_Admin {
   public function __construct() {
    add_action('admin_menu', array($this, 'menus'));

    add_action('admin_enqueue_scripts', array($this, 'load_admin_meta_box_script'));

  add_action('admin_head', array($this, 'admin_header'));

  add_action('admin_init', array($this, 'export_subscribers'));
  add_action('admin_init', array($this, 'export_logs'));
 }

 function admin_header() {
  echo '<style type="text/css">';

  // App Subscribers page
  echo '.wp-list-table .column-username { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-email { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-user_categories { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-devices { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-excluded_categories { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';

  // Tokens page
  echo '.wp-list-table .column-id { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-token  { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-user_id { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-timestamp { overflow: hidden; text-overflow: ellipsis; }';
  echo '.wp-list-table .column-os { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-lang { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-status { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';

  // Debug page
  echo '.wp-list-table .column-type { width: 9%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
  echo '.wp-list-table .column-timestamp { width: 16%; overflow: hidden; text-overflow: ellipsis; }';
  echo '.wp-list-table .column-text { overflow: hidden; text-overflow: ellipsis; }';

  echo '.log-type-' . PNFW_SYSTEM_LOG . ' { width: 20px; height: 20px; border-radius: 50%; background-color: #cccccc; }';
  echo '.log-type-' . PNFW_IOS_LOG . ' { width: 20px; height: 20px; border-radius: 50%; background-color: #3980d5; }';
  echo '.log-type-' . PNFW_ANDROID_LOG . ' { width: 20px; height: 20px; border-radius: 50%; background-color: #99cc00; }';
  echo '.log-type-' . PNFW_KINDLE_LOG . ' { width: 20px; height: 20px; border-radius: 50%; background-color: #fd9924; }';
  echo '.log-type-' . PNFW_FEEDBACK_PROVIDER_LOG . ' { width: 20px; height: 20px; border-radius: 50%; background-color: #3980d5; }';
  echo '.log-type-' . PNFW_ALERT_LOG . ' { width: 20px; height: 20px; border-radius: 50%; background-color: #f27d7d; }';
  echo '</style>';
 }

 function menus() {
  $admin_capability = 'activate_plugins';
  $editor_capability = 'publish_pages';

  $menu_slug = 'push-notifications-for-wordpress';

  $page_hook_suffix = add_menu_page(
   __('Push Notifications', 'pnfw'),
   __('Push Notifications', 'pnfw'),
   $editor_capability,
   $menu_slug,
   array($this, 'stats_page'),
   plugin_dir_url(__FILE__) . '../assets/imgs/icon-menu.png',
   200);

  // Use the hook suffix to compose the hook and register an action executed when plugin's options page is loaded
  add_action('load-' . $page_hook_suffix , array($this, 'chart_add_scripts'));





  $page_hook_suffix = add_submenu_page(
   $menu_slug,
   __('Settings', 'pnfw'),
   __('Settings', 'pnfw'),
   $admin_capability,
   'pnfw-settings-identifier',
   array($this, 'settings_page'));

  add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'plugin_admin_scripts'));





  $page_hook_suffix = add_submenu_page(
   $menu_slug,
   __('OAuth', 'pnfw'),
   __('OAuth', 'pnfw'),
   $admin_capability,
   'pnfw-oauth-identifier',
   array($this, 'oauth_page'));





  $page_hook_suffix = add_submenu_page(
   $menu_slug,
   __('App Subscribers', 'pnfw'),
   __('App Subscribers', 'pnfw'),
   $editor_capability,
   'pnfw-app-subscribers-identifier',
   array($this, 'app_subscribers_page'));





  $page_hook_suffix = add_submenu_page(
   $menu_slug,
   __('Tokens', 'pnfw'),
   __('Tokens', 'pnfw'),
   $editor_capability,
   'pnfw-tokens-identifier',
   array($this, 'tokens_page'));





  $page_hook_suffix = add_submenu_page(
   $menu_slug,
   __('Debug', 'pnfw'),
   __('Debug', 'pnfw'),
   $admin_capability,
   'pnfw-debug-identifier',
   array($this, 'debug_page'));




 }

 function stats_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-stats.php';

  PNFW_Admin_Stats::output();
 }

 function settings_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-settings.php';

  PNFW_Admin_Settings::output();
 }

 function oauth_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-oauth.php';

  PNFW_Admin_OAuth::output();
 }

 function app_subscribers_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-subscribers.php';

  PNFW_Admin_Subscribers::output();
 }

 function tokens_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-tokens.php';

  PNFW_Admin_Tokens::output();
 }

 function debug_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-debug.php';

  PNFW_Admin_Debug::output();
 }

 function chart_add_scripts() {
  wp_register_script(
   'Chart',
   plugin_dir_url(__FILE__) . '../libs/Chart/Chart.min.js',
   false,
   null,
   true
  );

  wp_register_script(
   'adminCharts',
   plugin_dir_url(__FILE__) . '../assets/js/admin_charts.js',
   array('Chart', 'jquery'),
   '1.0',
   true
  );
     wp_enqueue_script('adminCharts');
 }
 function plugin_admin_scripts() {
  wp_enqueue_media();
  wp_enqueue_script('script', plugin_dir_url(__FILE__) . '../assets/js/script.js', array('jquery'));
 }

 function load_admin_meta_box_script() {
  global $pagenow;

  if (is_admin() && ($pagenow == 'post-new.php' || $pagenow == 'post.php')) {
    wp_register_script(
     'admin_meta_box',
     plugin_dir_url(__FILE__) . '../assets/js/admin_meta_box.js',
     array('jquery'),
     null,
     false);

    wp_enqueue_script('admin_meta_box');

    wp_localize_script('admin_meta_box',
     'strings',
     array(
      'str1' => __('Send and make visible only to', 'pnfw') . ':',
       'str2' => __('Make visible only to', 'pnfw') . ':'
    ));
  }
 }

 private function data_for_overview_graph() {
  global $wpdb;

  $wp_posts = array();
  if (get_option('pnfw_enabled_post_types')) {
   $wp_posts = get_posts(
    array(
     'posts_per_page' => 50,
     'order' => 'DESC',
     'post_type' => get_option('pnfw_enabled_post_types')
    )
   );
  }

  $posts = array();
  $sent = array();
  $read = array();

  foreach ($wp_posts as $post) {
   $short_title = strlen($post->post_title) > 30 ? substr($post->post_title, 0, 30)."..." : $post->post_title;

   array_push($posts, $short_title);

   $push_sent = $wpdb->get_blog_prefix().'push_sent';
   array_push($sent, (int)$wpdb->get_var("SELECT COUNT(*) FROM $push_sent WHERE post_ID = {$post->ID}"));

   $push_viewed = $wpdb->get_blog_prefix().'push_viewed';
   array_push($read, (int)$wpdb->get_var("SELECT COUNT(*) FROM $push_viewed WHERE post_ID = {$post->ID}"));
  }

  $res = array(
   'data_type' => 'overview',
   'post_data' => array(
    "posts" => array_reverse($posts),
    "sent" => array_reverse($sent),
    "read" => array_reverse($read)
   )
  );

  return $res;
 }
 public function export_subscribers() {
  global $wpdb;

  if (empty($_GET['pnfw_download_subscribers'])) {
   return;
  }

  $args = array(
   'role' => PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE,
   'fields' => 'all_with_meta',
   'order' => 'desc',
   'orderby' => 'id'
  );

  $user_query = new WP_User_Query($args);

  $items = $user_query->get_results();

  $separator = apply_filters('pnfw_csv_separator', ',');

  $row = array();
  $row[] = __('Username', 'pnfw');
  $row[] = __('Email', 'pnfw');
  $row[] = __('Categories', 'pnfw');
  $row[] = __('Devices', 'pnfw');

  $rows = array();
  $rows[] = '"' . implode('"' . $separator . '"', $row) . '"';

  if (!empty($items)) {
   foreach ($items as $item) {
    $row = array();

    $row[] = $item->display_name;
    $row[] = $item->user_email;

    $user_groups = wp_get_object_terms($item->ID, 'user_cat', array('fields' => 'names'));

    $row[] = implode(', ', $user_groups);

    $push_tokens = $wpdb->get_blog_prefix() . 'push_tokens';
    $token_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $push_tokens WHERE user_id=%s", $item->ID));

    $row[] = $token_count;

    $rows[] = '"' . implode('"' . $separator . '"', $row) . '"';
   }
  }

  $this->generate_csv($rows, 'subscribers_log.csv');

  exit;
 }

 public function export_logs() {
  global $wpdb;

  if (empty($_GET['pnfw_download_logs'])) {
   return;
  }

  $push_logs = $wpdb->get_blog_prefix() . 'push_logs';
  $items = $wpdb->get_results("SELECT * FROM $push_logs ORDER BY id DESC;");

  $separator = apply_filters('pnfw_csv_separator', ',');

  $row = array();
  $row[] = __('Timestamp', 'pnfw');
  $row[] = __('Type', 'pnfw');
  $row[] = __('Text', 'pnfw');

  $rows = array();
  $rows[] = '"' . implode('"' . $separator . '"', $row) . '"';

  if (!empty($items)) {
   foreach ($items as $item) {
    $row = array();

    $row[] = $item->timestamp;
    $row[] = $item->type;
    $row[] = $item->text;

    $rows[] = '"' . implode('"' . $separator . '"', $row) . '"';
   }
  }

  $this->generate_csv($rows, 'debug_log.csv');

  exit;
 }

 private function generate_csv($rows, $filename) {
  $blog_title = strtolower(get_bloginfo('name'));
  $blog_title = str_replace(' ', '_', $blog_title);

  $filename = $blog_title . '_' . $filename;

  header('Content-type: text/csv');
  header('Content-Disposition: attachment; filename=' . $filename);
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

  $log = implode("\n", $rows);
  header('Content-Length: ' . strlen($log));
  echo $log;
 }
}
