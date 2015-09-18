<?php

require_once dirname(__FILE__ ) . '/class-pnfw-api-registered.php';

class PNFW_API_Activated extends PNFW_API_Registered {

 public function __construct($url, $http_method = null) {
  parent::__construct($url, $http_method);

  // Check token is activated
  if (!$this->is_token_activated())
   $this->json_error('401', __('Your email needs to be verified. Go to your email inbox and find the message from us asking you to confirm your address. Or make sure your email address is entered correctly.', 'pnfw'));
 }

 private function is_token_activated() {
  global $wpdb;
  $push_tokens = $wpdb->get_blog_prefix().'push_tokens';
  return (boolean)$wpdb->get_var($wpdb->prepare("SELECT active FROM $push_tokens WHERE token=%s AND os=%s", $this->token, $this->os));
 }

}
