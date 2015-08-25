<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

require_once dirname(__FILE__ ) . '/class-pnfw-notifications-ios.php';

class PNFW_Post_Notifications_iOS extends PNFW_Notifications_iOS {

 protected $post_id;

 protected function raw_send($tokens, $title, $post_id) {
  $this->post_id = $post_id;
  return parent::raw_send($tokens, $title, array('id' => $post_id));
 }

 protected function notification_sent($token) {
  $this->set_sent($this->post_id, $token);
 }

 protected function get_badge_count($token) {
  // Select sent post ids
  $sent_post_ids = $this->wpdb->get_col($this->wpdb->prepare("SELECT post_id FROM {$this->push_sent} WHERE user_id=%d", $this->get_user_id($token)));

  // Select viewed post ids
  $viewed_post_ids = $this->wpdb->get_col($this->wpdb->prepare("SELECT post_id FROM {$this->push_viewed} WHERE user_id=%d", $this->get_user_id($token)));

  // Get unviewed post ids from difference of viewed from sent
  $unviewed_post_ids = array_diff($sent_post_ids, $viewed_post_ids);

  $unviewed_posts = 0;
  foreach ((array)$unviewed_post_ids as $post_id) {
   // Add to unviewed only if post is published
   // and its type is allowed
   // and post is visibile to client
   if ('publish' === get_post_status($post_id) &&
    in_array(get_post_type($post_id), $this->enabled_post_types) &&
    in_array($post_id, $this->visible_posts))
    $unviewed_posts++;
  }
  return $unviewed_posts;
 }

}
