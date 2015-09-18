<?php

require_once dirname(__FILE__ ) . '/class-pnfw-api.php';

class PNFW_API_Activate {
 public function __construct() {
  $http_method = 'GET';

  // Enforce HTTP method
  if (!is_null($http_method) && strtoupper($http_method) !== $this->get_method())
   $this->html_response(__('We were not able to activate your account. Please contact support.', 'pnfw'), $error = true);

  $activation_code = $this->get_parameter('activation_code');

  global $wpdb;
  $table_name = $wpdb->get_blog_prefix() . 'push_tokens';

  $user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table_name WHERE activation_code = %s", $activation_code));

  if (!isset($user_id)) {
         $this->html_response(__('We were not able to activate your account. Please contact support.', 'pnfw'), $error = true);
  }

  $res = $wpdb->update($table_name, array('active' => true), array('activation_code' => $activation_code));

  if (!$res) {
         $this->html_response(__('We were not able to activate your account. Please contact support.', 'pnfw'), $error = true);
  }

  $this->send_admin_email($user_id);

  $this->html_response(__('Thank you for activating your account.', 'pnfw'), $error = false);

  exit;
 }

 private function send_admin_email($user_id) {
  $user = get_userdata($user_id);

  // The blogname option is escaped with esc_html on the way into the database in sanitize_option
  // we want to reverse this for the plain text arena of emails.
  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

  $message = sprintf(__('New confirmed app subscriber on your site %s: %s', 'pnfw'), $blogname, $user->user_email) . "\r\n\r\n";

  wp_mail(get_option('admin_email'), sprintf(__('[%s] New Confirmed App Subscriber', 'pnfw'), $blogname), $message);
 }

 private function html_response($message, $error = false) {
  header('Content-Type: text/html'); ?>

  <html>
  <head>

  <?php
  $pnfw_url_scheme = get_option("pnfw_url_scheme");
  if (!$error && $this->is_mobile() && $pnfw_url_scheme) { ?>
   <meta http-equiv="refresh" content="3;url=<?php echo $pnfw_url_scheme; ?>://" />
  <?php } ?>

  <style>
   body {
    margin: 0px;
    font: 11px tahoma;
   }
   .content {
    border: solid 2px #d1d1d1;
    width: 300px;
    height: 100px;
    background-color: <?php echo $error ? "#ff9494" : "#c8ff94" ?>;
    padding: 20px;
   }
  </style>
  </head>
  <body>
  <table width="100%" height="100%" border="0">
   <tr height="100%">
    <td width="100%" valign="center" align="center">
     <div class="content">
      <h3><?php $error ? _e('Error', 'pnfw') : _e('Success', 'pnfw'); ?></h3>
      <?php echo $message ?>
     </div>
    </td>
   </tr>
  </table>
  </body>
  </html>

  <?php exit;
 }

 private function is_mobile() {
  return preg_match("/(android|iphone|ipad|silk|kindle)/i", $_SERVER["HTTP_USER_AGENT"]);
 }

 private function get_method() {
  return strtoupper($_SERVER['REQUEST_METHOD']);
 }

 // Get parameters from get or post
 private function get_parameters() {
  return strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ? $_POST : $_GET;
 }

 // Get mandatory parameter from get or post	
 function get_parameter($parameter) {
  $pars = $this->get_parameters();

  if (!array_key_exists($parameter, $pars))
   $this->html_response(__('We were not able to activate your account. Please contact support.', 'pnfw'), $error = true);

  $res = filter_var($pars[$parameter], FILTER_SANITIZE_STRING);

  if (!$res)
   $this->html_response(__('We were not able to activate your account. Please contact support.', 'pnfw'), $error = true);

  return $res;
 }
}
