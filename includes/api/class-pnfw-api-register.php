<?php

require_once dirname(__FILE__ ) . '/class-pnfw-api.php';

class PNFW_API_Register extends PNFW_API {
 protected $email;



 protected $activation_code;

 public function __construct() {
  parent::__construct(site_url('pnfw/register/'), 'POST');

  // Optional
  $prevToken = $this->opt_parameter('prevToken');
  $this->email = $this->opt_parameter('email');




  if (isset($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
   $this->json_error('500', __('Invalid email address', 'pnfw'));
  }
  $this->activation_code = $this->get_activation_code();

  global $wpdb;
  $push_tokens = $wpdb->get_blog_prefix().'push_tokens';

  if (isset($prevToken)) {
   // Check if registered
   if ($this->is_token_missing($prevToken))
    $this->json_error('404', __('prevToken not found', 'pnfw'));

   if ($prevToken == $this->token) {
    exit; // nothing to do
   }

   $data = array(
    'token' => $this->token,
    'os' => $this->os,
    'lang' => $this->lang
    );

   $where = array(
    'token' => $prevToken,
    'os' => $this->os
   );

   $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $push_tokens WHERE token = %s AND os = %s", $this->token, $this->os));

   if ($count != 0) {
    pnfw_log(PNFW_SYSTEM_LOG, sprintf(__('Attempted an update of an %s token equal to a token already present: %s.', 'pnfw'), $this->os, $this->token));

    // Delete destination token to allow overwrite
    $wpdb->delete($push_tokens, $where);
   }

   // Update prevToken with new token	
   $wpdb->update($push_tokens, $data, $where);
  }
  else {
   $user_id = username_exists($this->email);
   // If the user does not exist it is created, otherwise the role app_subscriber is added
   if (empty($user_id) || is_null($user_id)) {
    $user_id = $this->create_user($this->email);
   }
   else {
    $user = new WP_User($user_id);

    $user->add_role(PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE);
   }







   $activation_link = esc_url(add_query_arg(array('activation_code' => $this->activation_code), site_url('/pnfw/activate')));

   // Following code should not be accessed simultaneously by different threads
   $push_logs = $wpdb->get_blog_prefix().'push_logs';
   $wpdb->query("LOCK TABLES $push_tokens WRITE, $push_logs WRITE;");

   // If the device does not exist it is created, otherwise the user_id is updated
   if ($this->is_token_missing()) {
    $data = array(
     'token' => $this->token,
     'os' => $this->os,
     'lang' => $this->lang,
     'timestamp' => current_time('mysql'),
     'user_id' => $user_id,
     'active' => empty($this->email),
     'activation_code' => $this->activation_code
    );
    $wpdb->insert($push_tokens, $data);

    $wpdb->query("UNLOCK TABLES;");

    $this->new_device_email($user_id, $activation_link);
   }
   else {
    if ($this->reassign_token_to($user_id)) {
     $this->new_device_email($user_id, $activation_link);
    }
   }






  }

  exit;
 }
 private function get_activation_code() {
  if (empty($this->email))
   return null;
  else
   return sha1($this->email.'&'.$this->token.'&'.time());
 }

 private function reassign_token_to($user_id) {
  global $wpdb;
  $push_tokens = $wpdb->get_blog_prefix().'push_tokens';
  $old_user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $push_tokens WHERE token=%s AND os=%s", $this->token, $this->os));

  if ($old_user_id == $user_id) {
   $wpdb->query("UNLOCK TABLES;");

   return false; // reassign the tokens to the same user
  }

  pnfw_log(PNFW_SYSTEM_LOG, sprintf(__("Reassign %s token %s from user %s to user %s.", 'pnfw'), $this->os, $this->token, $old_user_id, $user_id));

  $wpdb->update(
   $push_tokens,
   array(
    'lang' => $this->lang,
    "user_id" => $user_id,
    'active' => empty($this->email),
    'activation_code' => $this->activation_code
   ),
   array(
    "token" => $this->token,
    "os" => $this->os
   )
  );

  if ($this->user_without_tokens($old_user_id)) {
   $wpdb->query("UNLOCK TABLES;");

   $old_user_categories = wp_get_object_terms($old_user_id, 'user_cat', array('fields' => 'ids'));

   $this->set_categories($user_id, $old_user_categories);

   $old_user = new WP_User($old_user_id);

   if (in_array(PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE, $old_user->roles) && empty($old_user->user_email)) {
    pnfw_log(PNFW_SYSTEM_LOG, sprintf(__("Automatically deleted the anonymous user %s (%s) since left without tokens.", 'pnfw'), $old_user->user_login, $old_user_id));

    require_once(ABSPATH . 'wp-admin/includes/user.php');

    if (is_multisite()) {
     require_once(ABSPATH . 'wp-admin/includes/ms.php');
     if (is_user_member_of_blog($old_user_id)) {
      wpmu_delete_user($old_user_id);
     }
    }
    else {
     wp_delete_user($old_user_id);
    }
   }
  }
  else {
   $wpdb->query("UNLOCK TABLES;");

   $old_user_categories = wp_get_object_terms($old_user_id, 'user_cat', array('fields' => 'ids'));
   $this->set_categories($user_id, $old_user_categories);
  }

  return true;
 }

 private function user_without_tokens($user_id) {
  global $wpdb;
  $push_tokens = $wpdb->get_blog_prefix().'push_tokens';
  $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $push_tokens WHERE user_id=%d", $user_id));

  return $count == 0;
 }

 private function create_user($email) {
  if (!isset($email)) {
   // Anonymous user
   $user_login = $this->create_unique_user_login();

   $user_id = wp_insert_user(array(
    'user_login' => $user_login,
       'user_email' => NULL,
       'user_pass' => NULL,
       'role' => PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE
   ));

   if (is_wp_error($user_id)) {
    $this->json_error('500', $user_id->get_error_message());
   }
  }
  else {
   $password = wp_generate_password(10, false, false);

   $user_id = wp_insert_user(array(
    'user_login' => $email,
       'user_email' => $email,
       'user_pass' => $password,
       'role' => PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE
   ));

   if (is_wp_error($user_id)) {
    $this->json_error('500', $user_id->get_error_message());
   }
  }

  return $user_id;
 }

 private function create_unique_user_login() {
  $rand = wp_rand();

  $tmp_user_login = 'anonymous' . $rand;

  if (username_exists($tmp_user_login)) {
   return $this->create_unique_user_login();
  }
  else {
   return $tmp_user_login;
  }
 }

 private function new_device_email($user_id, $activation_link) {
  $user = get_userdata($user_id);

  // The blogname option is escaped with esc_html on the way into the database in sanitize_option
  // we want to reverse this for the plain text arena of emails.
  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

  $message = sprintf(__('Thank you for joining %s!', 'pnfw'), $blogname) . "\r\n\r\n";

  $message .= __('To confirm your registration, please click on the following link:', 'pnfw') . "\r\n\r\n";
  $message .= $activation_link;

  wp_mail($user->user_email, sprintf(__('Welcome to %s', 'pnfw'), $blogname), $message);
 }

 private function set_categories($user_id, $category_ids) {
  global $sitepress;

  if (isset($sitepress)) { // Removes a WPML warning
   remove_action('deleted_term_relationships', array($sitepress, 'deleted_term_relationships'));
  }

  wp_set_object_terms($user_id, $category_ids, 'user_cat', false);
  clean_object_term_cache($user_id, 'user_cat');

  if (isset($sitepress)) {
   add_action('deleted_term_relationships', array($sitepress, 'deleted_term_relationships'), 10, 2);
  }
 }
}
