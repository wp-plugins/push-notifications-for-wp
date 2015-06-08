<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

require_once dirname(__FILE__ ) . '/class-pnfw-notifications.php';

class PNFW_Notifications_Android extends PNFW_Notifications {

 public function __construct() {
  parent::__construct('Android');
 }

 protected function raw_send($tokens, $title, $user_info) {
  // No devices, do nothing
  if (empty($tokens)) {
   return 0;
  }

  require_once(dirname(__FILE__) . '/../../libs/PHP_GCM/Message.php');
  require_once(dirname(__FILE__) . '/../../libs/PHP_GCM/Sender.php');
  require_once(dirname(__FILE__) . '/../../libs/PHP_GCM/Result.php');
  require_once(dirname(__FILE__) . '/../../libs/PHP_GCM/MulticastResult.php');
  require_once(dirname(__FILE__) . '/../../libs/PHP_GCM/Constants.php');
  require_once(dirname(__FILE__) . '/../../libs/PHP_GCM/InvalidRequestException.php');

  $api_key = get_option("pnfw_google_api_key");
  if (empty($api_key)) {
   pnfw_log(PNFW_SYSTEM_LOG, __("Google API Key is not correctly set.", 'pnfw'));
   return 0;
  }

  $payloadData = array_merge(array('title' => $title), $user_info);

  $sender = new PHP_GCM\Sender($api_key);
  $message = new PHP_GCM\Message('push', $payloadData);

  $max_bulk_size = 999;
  $chunks = array_chunk($tokens, $max_bulk_size);

  $sent = 0;
  foreach ($chunks as $chunk) {
   try {
    $multicastResult = $sender->sendNoRetryMulti($message, $chunk);
    $results = $multicastResult->getResults();

    for ($i = 0, $count = count($results); $i < $count; $i++) {
     $result = $results[$i];
     // This means error
     if (is_null($result->getMessageId())) {
      // If device is not registered or invalid remove it from table
      if ('NotRegistered' == $result->getErrorCode() || 'InvalidRegistration' == $result->getErrorCode()) {
       $this->delete_token($chunk[$i]);
      }
      // else not recoverable error, ignore
     }
     else {
      $this->notification_sent($chunk[$i]);

      // If there is a canonical registration id we must update
      $token = $result->getCanonicalRegistrationId();
      if (!is_null($token)) {
       $this->update_token($chunk[$i], $token);
      }
     }
    }

    unset($result);

    $sent += $multicastResult->getSuccess();
   }
   catch (\InvalidArgumentException $e) {
    pnfw_log(PNFW_SYSTEM_LOG, strip_tags($e->getMessage()));
   } catch (PHP_GCM\InvalidRequestException $e) {
    pnfw_log(PNFW_SYSTEM_LOG, strip_tags($e->getMessage()));
   } catch (\Exception $e) {
    pnfw_log(PNFW_SYSTEM_LOG, strip_tags($e->getMessage()));
   }
  }

  return $sent;
 }

}
