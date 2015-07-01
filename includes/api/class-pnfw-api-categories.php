<?php

require_once dirname(__FILE__ ) . '/class-pnfw-api-registered.php';

class PNFW_API_Categories extends PNFW_API_Registered {

 private $category_id;

 public function __construct() {
  parent::__construct(site_url('pnfw/categories/'));

  // Optional
  $timestamp = $this->opt_parameter('timestamp');
  if ($timestamp == $this->get_last_modification_timestamp())
   $this->header_error('304');

  switch($this->get_method()) {
   case 'GET': {
    $object_taxonomies = get_option('pnfw_enabled_object_taxonomies', array());
    $raw_terms = get_terms($object_taxonomies, array('hide_empty' => 0));



    $categories = array();

    foreach ($raw_terms as $raw_term) {
     // Mandatory fields
     $category = array(
      'id' => (int)$raw_term->term_id,
      'name' => $raw_term->name
     );

     // Optional fields
     $description = $raw_term->description;
     if (!empty($description))
      $category['description'] = $description;

     $category['exclude'] = $this->isCategoryExcluded(pnfw_get_normalized_term_id((int)$raw_term->term_id));



     $categories[] = $category;
    }

    header('Content-Type: application/json');

    echo json_encode(array(
     'categories' => $categories,
     'timestamp' => $this->get_last_modification_timestamp()
    ));
    break;
   }
   case 'POST': {
    $this->category_id = pnfw_get_normalized_term_id((int)$this->get_parameter('id'));
    $excluded = filter_var($this->get_parameter('exclude'), FILTER_VALIDATE_BOOLEAN);

    $this->setCategoryExcluded($excluded);
    break;
   }
   default:
    $this->json_error('401', __('Invalid HTTP method', 'pnfw'));
  }
  exit;
 }

 private function get_last_modification_timestamp() {
  //return (int)get_option('pnfw_last_save_timestamp', time());
  return time(); // FIXME
 }

 private function isCategoryExcluded($category_id = null) {
  return self::is_category_excluded($this->current_user_id(), $category_id === null ? $this->category_id : $category_id);
 }

 private function setCategoryExcluded($excluded) {
  self::set_category_excluded($this->current_user_id(), $this->category_id, $excluded);
 }

 private function is_category_excluded($user_id, $category_id) {
  global $wpdb;
  $push_excluded_categories = $wpdb->get_blog_prefix() . 'push_excluded_categories';
  return (boolean)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $push_excluded_categories WHERE category_id=%d AND user_id=%d", $category_id, $user_id));
 }

 public static function set_category_excluded($user_id, $category_id, $excluded) {
  global $wpdb;
  $push_excluded_categories = $wpdb->get_blog_prefix() . 'push_excluded_categories';
  if ($excluded) {
   $wpdb->insert($push_excluded_categories, array('category_id' => $category_id, 'user_id' => $user_id));
  }
  else {
   $wpdb->delete($push_excluded_categories, array('category_id' => $category_id, 'user_id' => $user_id));
  }
 }

}
