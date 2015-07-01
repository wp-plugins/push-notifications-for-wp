<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

global $sender_manager; // we have to be explicit and declare that variable as global (see "A Note on Variable Scope" http://codex.wordpress.org/Function_Reference/register_activation_hook)
$sender_manager = new PNFW_Sender_Manager();

class PNFW_Sender_Manager {

 public function __construct() {
  add_action('transition_post_status', array($this, 'notify_new_custom_post'), 10, 3);

  add_action('pnfw_new_event', array($this, 'notify_new_post_in_background'));



 }

 function notify_new_custom_post($new_status, $old_status, $post) {
  $enable_push_notifications = get_option('pnfw_enable_push_notifications');

  if (!$enable_push_notifications) {
   return; // nothing to do
  }

  $custom_post_types = get_option('pnfw_enabled_post_types', array());

  if (!$custom_post_types || !in_array($post->post_type, $custom_post_types)) {
   return; // nothing to do
  }

  // Only do this when a post transitions to being published
  // Enforce that notifications will be triggered only by posts
  if ('publish' == $new_status && 'publish' != $old_status) {
   $title = $post->post_title;

   wp_schedule_single_event(time(), 'pnfw_new_event', array($post));
  }
 }

 function notify_new_post_in_background($post) {
  $do_not_send_push_notifications_for_this_post = get_post_meta($post->ID, 'pnfw_do_not_send_push_notifications_for_this_post', true);

  // This check has to be done here and not in the notify_new_custom_post which is
  // performed too early (the value of the post meta has not yet been stored)
  if ($do_not_send_push_notifications_for_this_post) {
   pnfw_log(PNFW_SYSTEM_LOG, sprintf(__('Notifications for the %s %s deliberately not sent', 'pnfw'), $post->post_type, $post->post_title));

   return;
  }

  if (get_option("pnfw_ios_push_notifications")) {
   require_once dirname(__FILE__ ) . '/notifications/class-pnfw-post-notifications-ios.php';

   $sender = new PNFW_Post_Notifications_iOS();
   $sender->send_post_to_user_categories($post);
  }

  if (get_option("pnfw_android_push_notifications")) {
   require_once dirname(__FILE__ ) . '/notifications/class-pnfw-post-notifications-android.php';

   $sender = new PNFW_Post_Notifications_Android();
   $sender->send_post_to_user_categories($post);
  }

  if (get_option("pnfw_kindle_push_notifications")) {
   require_once dirname(__FILE__ ) . '/notifications/class-pnfw-post-notifications-kindle.php';

   $sender = new PNFW_Post_Notifications_Kindle();
   $sender->send_post_to_user_categories($post);
  }
 }
}
