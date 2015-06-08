<?php

require_once dirname(__FILE__ ) . '/class-pnfw-api-registered.php';

class PNFW_API_Unregister extends PNFW_API_Registered {

 public function __construct() {
  parent::__construct(site_url('pnfw/unregister/'), 'POST');

  global $wpdb;
  $push_tokens = $wpdb->get_blog_prefix() . 'push_tokens';

  $user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $push_tokens WHERE token = %s AND os = %s", $this->token, $this->os));

  $res = $wpdb->delete($push_tokens, array("token" => $this->token, "os" => $this->os));

  if ($res === false) {
   $this->json_error('500', __('Unable to delete token', 'pnfw'));
  }

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

  exit;
 }
}
