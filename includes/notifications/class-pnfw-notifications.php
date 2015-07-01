<?php



if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

class PNFW_Notifications {

 protected $wpdb;
 protected $push_viewed;
 protected $push_sent;
 protected $os;
 protected $push_tokens;
 protected $push_excluded_categories;
 protected $type;
 protected $enabled_post_types;
 protected $visible_posts;

 public function __construct($os) {
  global $wpdb;
  $this->wpdb = &$wpdb;
  $this->os = $os;
  $this->push_tokens = $this->wpdb->get_blog_prefix() . 'push_tokens';
  $this->push_viewed = $this->wpdb->get_blog_prefix() . 'push_viewed';
  $this->push_sent = $this->wpdb->get_blog_prefix() . 'push_sent';
  $this->push_excluded_categories = $this->wpdb->get_blog_prefix() . 'push_excluded_categories';
  $this->enabled_post_types = get_option('pnfw_enabled_post_types', array());
  $this->visible_posts = $this->get_visible_posts();

  switch ($this->os) {
   case 'iOS': $this->type = PNFW_IOS_LOG; break;
   case 'Android': $this->type = PNFW_ANDROID_LOG; break;
   case 'Fire OS': $this->type = PNFW_KINDLE_LOG; break;
  }
 }

 public function send_post_to_user_categories($post) {




  $user_cat = get_post_meta($post->ID, 'pnfw_user_cat', true);
  $category_id = pnfw_get_normalized_term_id((int)$this->get_category_id($post));
  $lang = pnfw_get_post_lang($post->ID);

  $total = 0;
  $page = 0;




   $tokens = $this->get_tokens($category_id, $lang, $page);

   if (empty($user_cat)) {
    // Send tokens to all categories (no filter)
    $total += $this->raw_send($tokens, $post->post_title, $post->ID);
   }
   else {
    // Send tokens only to selected category
    $out_tokens = array();
    foreach ($tokens as $token) {
     $user_id = $this->get_user_id($token);
     if ($this->user_should_be_notified($user_id, $user_cat)) {





       $out_tokens[] = $token;



     }
    }
    $total += $this->raw_send($out_tokens, $post->post_title, $post->ID);
   }

   if (count($tokens) >= 1000) {
    pnfw_log(PNFW_SYSTEM_LOG, sprintf(__('This plugin allows you to send push notifications to up to %d tokens per platform. To send more notifications, please upgrade to <a href="http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/">Push Notifications for Wordpress</a>.', 'pnfw'), 1000));
   }






  return $total;
 }
 private function user_should_be_notified($user_id, $post_user_category) {
  if (empty($post_user_category)) {
   // All
   return true;
  }

  $user_categories = wp_get_object_terms($user_id, 'user_cat', array('fields' => 'slugs'));

  if ($this->is_user_anonymous($user_id)) {
   array_push($user_categories, 'anonymous-users');
  }
  else {
   array_push($user_categories, 'registered-users');
  }

  return in_array($post_user_category, $user_categories);
 }

 private function is_user_anonymous($user_id) {
  $user = get_userdata($user_id);

  return in_array(PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE, $user->roles) && empty($user->user_email);
 }

 public function send_title_to_tokens($title, $tokens) {
  return $this->raw_send($tokens, $title, array('id' => 0));
 }

 protected function raw_send($id, $title, $tokens) {
  return 0; // Do nothing; should be reimplementend in the subclasses
 }

 protected function notification_sent($token) {
  // Do nothing; can be reimplementend in the subclasses
 }

 protected function update_token($prevToken, $token) {
  if ($prevToken == $token)
   return;

  $count = $this->wpdb->get_var($this->wpdb->prepare("SELECT COUNT(*) FROM {$this->push_tokens} WHERE token = %s AND os = %s", $token, $this->os));

  if ($count != 0) {
   // This situation can happen on an Android device, for example when you uninstall the app and then reinstall it. In that case, Google GCM assigns to the device two different tokens, one before the uninstall and one after the reinstall. But when we send the notification to the old token ($prevToken), Google GCM informs us that it does no longer exists and gives us its new token ($token). Since the new token is already present in our db we have to delete the old one.
   pnfw_log(PNFW_SYSTEM_LOG, __('Attempted an update of a token equal to a token already present', 'pnfw'));

   $this->delete_token($prevToken);

   return;
  }

  pnfw_log($this->type, sprintf(__("Updated %s token from %s to %s.", 'pnfw'), $this->os, $prevToken, $token));

  $this->wpdb->update($this->push_tokens,
   array('token' => $token),
   array('token' => $prevToken, 'os' => $this->os)
  );
 }

 protected function delete_token($token) {
  pnfw_log($this->type, sprintf(__("Removed invalid %s token: %s.", 'pnfw'), $this->os, $token));

  $user_id = $this->get_user_id($token);

  $this->wpdb->delete($this->push_tokens,
   array('token' => $token, 'os' => $this->os)
  );

  $user = new WP_User($user_id);

  if (in_array(PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE, $user->roles) && empty($user->user_email)) {
   pnfw_log(PNFW_SYSTEM_LOG, sprintf(__("Automatically deleted the anonymous user %s since left without tokens.", 'pnfw'), $user->user_login));

   require_once(ABSPATH . 'wp-admin/includes/user.php');

   if (is_multisite()) {
    require_once(ABSPATH . 'wp-admin/includes/ms.php');
    if (is_user_member_of_blog($user_id)) {
     wpmu_delete_user($user_id);
    }
   }
   else {
    wp_delete_user($user_id);
   }
  }
 }

 protected function get_visible_posts() {
  $raw_posts = array();
  if (get_option('pnfw_enabled_post_types')) {
   $raw_posts = get_posts(
    array(
     'posts_per_page' => get_option('pnfw_posts_per_page'),
     'post_type' => get_option('pnfw_enabled_post_types'),
     'fields' => 'ids'
    )
   );
  }
  return $raw_posts;
 }

 private function get_forged_query($raw_sql, $args, $limit, $offset, $lang) {
  if (is_null($lang)) {
   $values = array_merge($args, array($limit, $offset));

   return $this->wpdb->prepare(
    $raw_sql .
    " LIMIT %d
    OFFSET %d",
    $values);
  }
  else {
   $langs = array($lang);
   global $sitepress;
   if ($lang == $sitepress->get_default_language()) {
    // All user langs
    $all_langs = $this->wpdb->get_col("SELECT DISTINCT lang FROM {$this->push_tokens}");

    // All user langs not supported
    $unsupported_langs = array_diff($all_langs, pnfw_get_wpml_langs());

    // All user langs not supported plus default and empty string
    $langs = array_merge($unsupported_langs, $langs, array(''));
   }
   $in = join(',', array_fill(0, count($langs), '%s'));
   $values = array_merge($args, $langs, array($limit, $offset));

   return $this->wpdb->prepare(
    $raw_sql." AND lang IN ($in) 
    LIMIT %d
    OFFSET %d",
    $values);
  }
 }

 protected function get_tokens($category_id, $lang, $page) {



  $PAGE_LENGTH = 1000;


  if (is_null($category_id)) {
   $sql = $this->get_forged_query(
    "SELECT token
    FROM {$this->push_tokens}
    WHERE os = %s AND active = %d AND token NOT LIKE 'tokenless_%%'",
    array($this->os, true), $PAGE_LENGTH, ($page * $PAGE_LENGTH), $lang
   );
  }
  else {
   $sql = $this->get_forged_query(
    "SELECT token
    FROM {$this->push_tokens}
    WHERE os = %s AND active = %d AND token NOT LIKE 'tokenless_%%' AND (user_id NOT IN (SELECT user_id FROM {$this->push_excluded_categories} WHERE category_id = %d))",
    array($this->os, true, $category_id), $PAGE_LENGTH, ($page * $PAGE_LENGTH), $lang
   );
  }

  return $this->wpdb->get_col($sql);
 }

 protected function set_sent($post_id, $token) {
  if ($post_id == 0)
   return;

  $this->wpdb->insert($this->push_sent, array('post_id' => $post_id, 'user_id' => $this->get_user_id($token), 'timestamp' => current_time('mysql')));
 }

 protected function get_user_id($token) {
  return $this->wpdb->get_var($this->wpdb->prepare("SELECT user_id FROM {$this->push_tokens} WHERE token=%s AND os=%s", $token, $this->os));
 }

 protected function get_category_id($post) {
  // Select all taxonomies of this post
  $object_taxonomies = get_object_taxonomies($post->post_type);

  // Retrieve all enabled taxonomies
  $enabled_object_taxonomies = get_option('pnfw_enabled_object_taxonomies', array());

  // Intersect this post categories with enabled taxonomies to get the one with "category" meaning
  $object_categories = array_intersect($object_taxonomies, $enabled_object_taxonomies);

  if (empty($object_categories))
   return null;

  // Retrieve categories for this post
  $categories = get_the_terms($post->ID, reset($object_categories));

  if (empty($categories))
   return null;

  return $categories[0]->term_id;
 }

}
