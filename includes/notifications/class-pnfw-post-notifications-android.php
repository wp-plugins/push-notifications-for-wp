<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

require_once dirname(__FILE__ ) . '/class-pnfw-notifications-android.php';

class PNFW_Post_Notifications_Android extends PNFW_Notifications_Android {

 protected $post_id;

 protected function raw_send($tokens, $title, $post_id) {
  $this->post_id = $post_id;
  return parent::raw_send($tokens, $title, array('id' => $post_id));
 }

 protected function notification_sent($token) {
  $this->set_sent($this->post_id, $token);
 }

}
