<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

require_once dirname(__FILE__ ) . '/class-pnfw-notifications.php';

class PNFW_Notifications_iOS extends PNFW_Notifications {

 public function __construct() {
  parent::__construct('iOS');
 }

 protected function raw_send($tokens, $title, $user_info) {
  // No devices, do nothing
  if (empty($tokens)) {
   return 0;
  }

  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Abstract.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Exception.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Feedback.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Message.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Log/Interface.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Log/Embedded.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Message/Custom.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Message/Exception.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Push.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Push/Exception.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Push/Server.php';
  require_once dirname(__FILE__) . '/../../libs/ApnsPHP/Push/Server/Exception.php';
  require_once dirname(__FILE__) . '/../class-pnfw-apnsphp-logger.php';

  $certificate = get_attached_file(get_option("pnfw_production_ssl_certificate_media_id"));
  $passphrase = get_option("pnfw_production_ssl_certificate_password");
  $environment = ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION;

  if (get_option("pnfw_ios_use_sandbox")) {
   $certificate = get_attached_file(get_option("pnfw_sandbox_ssl_certificate_media_id"));
   $passphrase = get_option("pnfw_sandbox_ssl_certificate_password");
   $environment = ApnsPHP_Abstract::ENVIRONMENT_SANDBOX;
  }

  if (empty($certificate)) {
   pnfw_log(PNFW_SYSTEM_LOG, __("iOS SSL certificate is not correctly set.", 'pnfw'));
      return 0;
  }

  if (empty($passphrase)) {
   pnfw_log(PNFW_SYSTEM_LOG, __("iOS SSL certificate password is not correctly set.", 'pnfw'));
      return 0;
  }

  if (!file_exists($certificate)) {
   pnfw_log(PNFW_SYSTEM_LOG, __("iOS SSL Certificate does not exists.", 'pnfw'));
   return 0;
  }

  try {
   $push = new ApnsPHP_Push($environment, $certificate);
   $push->setLogger(new PNFW_ApnsPHP_Logger());
   $push->setProviderCertificatePassphrase($passphrase);

   foreach ($tokens as &$token) {
    try {
     $this->notification_sent($token);

     $message = new ApnsPHP_Message($token);
     foreach (array_keys($user_info) as $key) {
      $message->setCustomProperty($key, strval($user_info[$key]));
     }
     $message->setText($title);
     $message->setSound();
     $message->setBadge($this->get_badge_count($token));
     $push->add($message);
    }
    catch (Exception $e) {
     // The only exception here is the invalid token, so delete it
     $this->delete_token($token);
    }
   }
   unset($token);

   $queued = count($push->getQueue(false));

   // Empty queue, do nothing
   if ($queued == 0) {
    return 0;
   }

   // Connect to the Apple Push Notification Service
   $push->connect();

   // Send all messages in the message queue
   $push->send();

   // Disconnect from the Apple Push Notification Service
   $push->disconnect();

   return $queued;
  } catch (Exception $e) {
   pnfw_log(PNFW_IOS_LOG, strip_tags($e->getMessage()));
   return 0;
  }
 }

 protected function get_badge_count($token) {
  return 1;
 }

}
