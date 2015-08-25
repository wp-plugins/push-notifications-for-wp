<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

global $feedback_provider; // we have to be explicit and declare that variable as global (see "A Note on Variable Scope" http://codex.wordpress.org/Function_Reference/register_activation_hook)
$feedback_provider = new PNFW_iOS_Feedback_Provider();

class PNFW_iOS_Feedback_Provider {
 public function __construct() {
  add_action('pnfw_feedback_provider_event', array($this, 'feedback_provider'));
 }

 function run() {
  if (!$this->is_active()) {
   pnfw_log(PNFW_FEEDBACK_PROVIDER_LOG, __("Scheduled Feedback Provider.", 'pnfw'));

   wp_schedule_event(strtotime('today'), 'daily', 'pnfw_feedback_provider_event');
  }
 }

 function stop() {
  if ($this->is_active()) {
   pnfw_log(PNFW_FEEDBACK_PROVIDER_LOG, __("Un-scheduled Feedback Provider.", 'pnfw'));

   wp_clear_scheduled_hook('pnfw_feedback_provider_event');
  }
 }

 function is_active() {
  return (bool)wp_next_scheduled('pnfw_feedback_provider_event');
 }

 function next_scheduled() {
  return wp_next_scheduled('pnfw_feedback_provider_event');
 }

 function feedback_provider() {
  pnfw_log(PNFW_FEEDBACK_PROVIDER_LOG, __("Feedback Provider is running.", 'pnfw'));

  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Abstract.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Exception.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Feedback.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Message.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Log/Interface.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Log/Embedded.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Message/Custom.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Message/Exception.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Push.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Push/Exception.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Push/Server.php';
  require_once dirname(__FILE__ ) . '/../libs/ApnsPHP/Push/Server/Exception.php';
  require_once dirname(__FILE__) . '/class-pnfw-apnsphp-logger.php';

  $certificate = get_attached_file(get_option("pnfw_production_ssl_certificate_media_id"));
  $passphrase = get_option("pnfw_production_ssl_certificate_password");
  $environment = ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION;

  if (get_option("pnfw_ios_use_sandbox")) {
   $certificate = get_attached_file(get_option("pnfw_sandbox_ssl_certificate_media_id"));
   $passphrase = get_option("pnfw_sandbox_ssl_certificate_password");
   $environment = ApnsPHP_Abstract::ENVIRONMENT_SANDBOX;
  }

  if (empty($certificate)) {
   pnfw_log(PNFW_FEEDBACK_PROVIDER_LOG, __("iOS SSL certificate is not correctly set.", 'pnfw'));
      return;
  }

  if (empty($passphrase)) {
   pnfw_log(PNFW_FEEDBACK_PROVIDER_LOG, __("iOS SSL certificate password is not correctly set.", 'pnfw'));
      return;
  }

  if (!file_exists($certificate)) {
   pnfw_log(PNFW_FEEDBACK_PROVIDER_LOG, __("iOS SSL Certificate does not exists.", 'pnfw'));
   return;
  }

  try {
   // Instantiate a new ApnsPHP_Feedback object
   $feedback = new ApnsPHP_Feedback($environment, $certificate);
   $feedback->setLogger(new PNFW_ApnsPHP_Logger());
   $feedback->setProviderCertificatePassphrase($passphrase);

   // Connect to the Apple Push Notification Feedback Service
   $feedback->connect();

   // Retrieve devices from Apple Push Notification Feedback Service
   $devices = $feedback->receive();

   // Disconnect from the Apple Push Notification Feedback Service
   $feedback->disconnect();
  }
  catch (Exception $e) {
   pnfw_log(PNFW_FEEDBACK_PROVIDER_LOG, strip_tags($e->getMessage()));
   return;
  }

  global $wpdb;
  $push_tokens = $wpdb->get_blog_prefix() . 'push_tokens';

  foreach ($devices as &$device) {
   $user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $push_tokens WHERE os = 'iOS' AND token = %s", $device['deviceToken']));

   $wpdb->delete($push_tokens, array('token' => $device['deviceToken'], 'os' => 'iOS'));

   pnfw_log(PNFW_FEEDBACK_PROVIDER_LOG, sprintf(__("The Feedback Provider removed iOS token of user %s: %s.", 'pnfw'), $user_id, $device['deviceToken']));

   $user = new WP_User($user_id);

   if (in_array(PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE, $user->roles) && empty($user->user_email)) {
    pnfw_log(PNFW_SYSTEM_LOG, sprintf(__("Automatically deleted the anonymous user %s (%s) since left without tokens.", 'pnfw'), $user->user_login, $user_id));

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
  unset($device);
 }
}
