<?php

class PNFW_API {
 protected $token;
 protected $os;
 protected $url;
 protected $lang;

 public function __construct($url, $http_method = null) {
  $this->url = $url;

  // Enforce HTTP method
  if (!is_null($http_method) && strtoupper($http_method) !== $this->get_method())
   $this->json_error('401', __('Invalid HTTP method', 'pnfw'));

  // Check Oauth
  $oauth_enabled = get_option("pnfw_api_consumer_secret");
  if (isset($oauth_enabled) && strlen($oauth_enabled) > 0)
   $this->check_oauth_signature();

  // Check mandatory os parameter
  $this->os = $this->get_parameter('os');
  if (!in_array($this->os, array('iOS', 'Android', 'Fire OS')))
   $this->json_error('500', __('os parameter invalid', 'pnfw'));

  // Check mandatory token parameter
  $this->token = $this->get_parameter('token');

  // Set optional lang parameter
  $this->lang = $this->opt_parameter('lang');
 }

 // Get parameters from get or post
 function get_parameters() {
  return strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ? $_POST : $_GET;
 }

 // Get mandatory parameter from get or post
 function get_parameter($parameter) {
  $pars = $this->get_parameters();

  return array_key_exists($parameter, $pars) ? $pars[$parameter] : $this->json_error('500', sprintf(__('Mandatory parameter %s missing', 'pnfw'), $parameter));
 }

 // Get optional parameter from get or post
 function opt_parameter($parameter) {
  $pars = $this->get_parameters();

  return array_key_exists($parameter, $pars) ? $pars[$parameter] : NULL;
 }

 function get_method() {
  return strtoupper($_SERVER['REQUEST_METHOD']);
 }

 function check_oauth_signature() {
  $http_method = $this->get_method();

  switch ($http_method) {
   case 'POST': $params = $_POST; break;
   case 'GET': $params = $_GET; break;
   default:
    $this->json_error('401', __('Invalid HTTP method', 'pnfw'));
  }

  $param_names = array('oauth_consumer_key', 'oauth_timestamp', 'oauth_nonce', 'oauth_signature', 'oauth_signature_method');

  // check for required OAuth parameters
  foreach ($param_names as $param_name) {
   if (empty($params[$param_name])) {
    $this->json_error('401', sprintf(__('Parameter %s is missing', 'pnfw'), $param_name));
   }
  }

  $base_request_uri = rawurlencode($this->url);

  // get the signature provided by the consumer and remove it from the parameters prior to checking the signature
  $consumer_signature = rawurldecode($params['oauth_signature']);
  unset($params['oauth_signature']);

  // normalize parameter key/values
  $params = $this->normalize_parameters($params);

  // sort parameters
  if (!uksort($params, 'strcmp')) {
   $this->json_error('401', __('Failed to sort parameters', 'pnfw'));
  }

  // form query string
  $query_params = array();
  foreach ($params as $param_key => $param_value) {
   $query_params[] = $param_key . '=' . $param_value; // join with equals sign
  }

  $query_string = rawurlencode(implode('&', $query_params)); // join with ampersand

  $string_to_sign = $http_method . '&' . $base_request_uri . '&' . $query_string;

  if ($params['oauth_signature_method'] !== 'HMAC-SHA1' && $params['oauth_signature_method'] !== 'HMAC-SHA256' ) {
   $this->json_error('401', __('Signature method is invalid', 'pnfw'));
  }

  $hash_algorithm = strtolower(str_replace('HMAC-', '', $params['oauth_signature_method']));

  $api_consumer_secret = get_option("pnfw_api_consumer_secret");

  $key_parts = array($api_consumer_secret, '');
  $key = implode('&', $key_parts);

  $signature = base64_encode(hash_hmac($hash_algorithm, $string_to_sign, $key, true));

  if ($signature !== $consumer_signature) {
   $this->json_error('401', __('Provided signature does not match', 'pnfw'));
  }
 }

 function normalize_parameters($parameters) {
  $normalized_parameters = array();

  foreach ($parameters as $key => $value) {
   $key = rawurlencode(rawurldecode($key));
   $value = rawurlencode(rawurldecode($value));

   $normalized_parameters[$key] = $value;
  }

  return $normalized_parameters;
 }

 function header_error($error) {
  switch ($error) {
   case '401':
    header('HTTP/1.1 401 Unauthorized');
    break;
   case '404':
    header('HTTP/1.1 404 Not Found');
    break;
   case '304':
    header('HTTP/1.1 304 Not Modified');
    break;

   default:
    header('HTTP/1.1 500 Internal Server Error');
  }
  exit;
 }

 function json_error($error, $detail) {
  header('Content-Type: application/json');

  switch ($error) {
   case '401':
    header('HTTP/1.1 401 Unauthorized');
    $reason = __('Unauthorized', 'pnfw');
    break;

   case '404':
    header('HTTP/1.1 404 Not Found');
    $reason = __('Not Found', 'pnfw');
    break;

   default:
    header('HTTP/1.1 500 Internal Server Error');
    $reason = __('Internal Server Error', 'pnfw');
  }

  $response = array(
   'error' => $error,
   'reason' => $reason,
   'detail' => $detail
  );

  echo json_encode($response);
  exit;
 }

 protected function current_user_id() {
  return self::get_user_id($this->token, $this->os);
 }

 protected function is_current_user_anonymous() {
  $user_id = $this->current_user_id();

  $user = get_userdata($user_id);

  return in_array(PNFW_Push_Notifications_for_WordPress_Lite::USER_ROLE, $user->roles) && empty($user->user_email);
 }

 protected function current_user_can_view_post($post_id) {
  $post_user_category = get_post_meta($post_id, 'pnfw_user_cat', true);

  if (empty($post_user_category)) {
   // All
   return true;
  }

  $user_id = $this->current_user_id();
  $user_categories = wp_get_object_terms($user_id, 'user_cat', array('fields' => 'slugs'));

  if ($this->is_current_user_anonymous()) {
   array_push($user_categories, 'anonymous-users');
  }
  else {
   array_push($user_categories, 'registered-users');
  }

  return in_array($post_user_category, $user_categories);
 }

 public static function get_user_id($token, $os) {
  global $wpdb;
  $push_tokens = $wpdb->get_blog_prefix() . 'push_tokens';
  return $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $push_tokens WHERE token=%s AND os=%s", $token, $os));
 }

 protected function is_token_missing($token = null) {
  global $wpdb;
  if (is_null($token))
   $token = $this->token;

  $push_tokens = $wpdb->get_blog_prefix() . 'push_tokens';
  $res = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $push_tokens WHERE token=%s AND os=%s", $token, $this->os));

  return empty($res);
 }

}
