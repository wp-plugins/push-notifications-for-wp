<?php

require_once dirname(__FILE__ ) . '/class-pnfw-api.php';

class PNFW_API_Registered extends PNFW_API {

 public function __construct($url, $http_method = null) {
  parent::__construct($url, $http_method);

  // Check token is registered
  if ($this->is_token_missing())
   $this->json_error('401', __("Token not registered.\nTo solve the problem please uninstall and reinstall the app.", 'pnfw'));

  // Update lang
  if (isset($this->lang))
   $this->update_token_lang();
 }

 private function update_token_lang() {
  global $wpdb;
  $push_tokens = $wpdb->get_blog_prefix().'push_tokens';
  $wpdb->update(
   $push_tokens,
   array(
    'lang' => $this->lang
   ),
   array(
    'token' => $this->token,
    'os' => $this->os
   )
  );
 }

}
