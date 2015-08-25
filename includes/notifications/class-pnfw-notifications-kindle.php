<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

require_once dirname(__FILE__ ) . '/class-pnfw-notifications.php';

class PNFW_Notifications_Kindle extends PNFW_Notifications {

 public function __construct() {
  parent::__construct('Fire OS');
 }

 protected function raw_send($tokens, $title, $user_info) {
  // No devices, do nothing
  if (empty($tokens)) {
   return 0;
  }

  $access_token = $this->get_auth_token();

  if (is_null($access_token)) {
   pnfw_log(PNFW_KINDLE_LOG, __("Can't obtain ADM access token. ADM Client ID or ADM Client Secret are probably incorrect.", 'pnfw'));
      return 0;
  }

  // Amazon wants numerical values to be strings
  $data = array_merge(array('title' => $title), array_map('strval', $user_info));

  $payload = array(
   'data' => $data,
   'consolidationKey' => 'push'
  );

  $body = json_encode($payload);

  $headers = array(
   'Content-Type: application/json',
   'Accept: application/json',
   'X-Amzn-Type-Version: com.amazon.device.messaging.ADMMessage@1.0',
   'X-Amzn-Accept-Type: com.amazon.device.messaging.ADMSendResult@1.0',
   'Authorization: Bearer ' . $access_token);

  $remove = array();
  $update = array();

  foreach ($tokens as &$token) {
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, 'https://api.amazon.com/messaging/registrations/' . $token . '/messages');
   curl_setopt($ch, CURLOPT_POST, true);
   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
   $response = curl_exec($ch);
   $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   curl_close($ch);

   $json = json_decode($response, true);

   if ($status == 200) {
    $this->set_sent($id, $token);

    $newToken = $json['registrationID'];
    if ($newToken != $token) {
     $update[] = array(
      'prevToken' => $token,
      'token' => $newToken
     );
    }
   }
   else {
    $reason = $json['reason'];
    if ('Unregistered' == $reason ||
     'InvalidRegistrationId' == $reason) {
     $remove[] = $token;
    }
   }
  }
  unset($device);

  // Remove tokens
  foreach ($remove as &$token) {
   $this->delete_token($token);
  }
  unset($token);

  // Update tokens
  foreach ($update as &$device) {
   $this->update_token($device->prevToken, $device->token);
  }
  unset($device);

  return count($tokens) - count($remove);
 }

 private function get_auth_token() {
  $clientId = get_option("pnfw_adm_client_id");

  if (empty($clientId)) {
   pnfw_log(PNFW_KINDLE_LOG, __("ADM Client ID is not correctly set.", 'pnfw'));
      return null;
  }

  $clientSecret = get_option("pnfw_adm_client_secret");

  if (empty($clientSecret)) {
   pnfw_log(PNFW_KINDLE_LOG, __("ADM Client Secret is not correctly set.", 'pnfw'));
      return null;
  }

  // Encode the body of your request, including your clientID and clientSecret values.
  $body = 'grant_type=client_credentials';
  $body .= '&scope=messaging:push';
  $body .= '&client_id='.urlencode($clientId);
  $body .= '&client_secret='.urlencode($clientSecret);

  $headers = array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.amazon.com/auth/O2/token');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
  $response = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($status != 200)
   return null;

  $json = json_decode($response, true);

  return $json['access_token'];
 }

}
