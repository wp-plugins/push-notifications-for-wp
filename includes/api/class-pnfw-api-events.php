<?php

require_once dirname(__FILE__ ) . '/class-pnfw-api-registered.php';

class PNFW_API_Events extends PNFW_API_Registered {

 private $post_id;

 public function __construct() {
  parent::__construct(site_url('pnfw/events/'), 'GET');

  // Optional
  $this->post_id = $this->opt_parameter('id');

  $timestamp = $this->opt_parameter('timestamp');
  if ($timestamp == $this->get_last_modification_timestamp())
   $this->header_error('304');

  if (isset($this->post_id)) {
   $post = get_post($this->post_id);

   if ($post == null) {
    $this->json_error('404', __('Post not found.', 'pnfw'));
   }

   if (!$this->current_user_can_view_post($this->post_id)) {
    $this->json_error('401', __('You do not have permission to access this post.', 'pnfw'));
   }

   $taxonomies = array_intersect(get_object_taxonomies($post), get_option('pnfw_enabled_object_taxonomies', array()));
   $terms = empty($taxonomies) ? false : get_the_terms($this->post_id, reset($taxonomies));
   $category_name = $terms ? reset($terms)->name : __('Uncategorized', 'pnfw');

   $user = get_userdata($post->post_author);
   $display_name = $user ? $user->display_name : __('Anonymous', 'pnfw');

   $content = (bool)get_option('pnfw_use_wpautop') ? wpautop($post->post_content) : $post->post_content;

   $response = array(
    'title' => $post->post_title,
    'subtitle' => $display_name,
    'body' => $content,
    'category' => $category_name
   );

   // Optional fields
   $images = $this->get_images();
   if ($images != null) {
    $response['images'] = $images;
   }

   if (!$this->is_viewed())
    $this->set_viewed();

   header('Content-Type: application/json');

   echo json_encode($response);
  }
  else {
   $posts = array();
   if (get_option('pnfw_enabled_post_types')) {
    $posts = get_posts(
     array(
      'posts_per_page' => get_option('pnfw_posts_per_page'),
      'post_type' => get_option('pnfw_enabled_post_types'),
      'suppress_filters' => pnfw_suppress_filters()
     )
    );
   }





   $events = array();

   foreach ($posts as $post) {
    // Mandatory fields
    $event = array(
     'id' => $post->ID,
     'title' => $post->post_title,
    );

    if ($this->current_user_can_view_post($post->ID)) {
     // Optional fields
     $thumbnail = $this->get_thumbnail($post->ID);
     if (!is_null($thumbnail))
      $event['thumbnail'] = $thumbnail;

     if (!$this->is_read($post->ID))
      $event['read'] = false;

     $events[] = $event;
    }
   }

   header('Content-Type: application/json');

   echo json_encode(array(
    'events' => $events,
    'timestamp' => $this->get_last_modification_timestamp()
   ));
  }
  exit;
 }

 private function get_last_modification_timestamp() {
  //return (int)get_option('pnfw_last_save_timestamp', time());
  return time(); // FIXME
 }

 private function get_images() {
  $url_photo = $this->get_image();

  if (empty($url_photo))
   return null;

  $url_thumbnail = $this->get_thumbnail();

  $images[] = array(
   'image' => $url_photo,
   'thumbnail' => $url_thumbnail
  );

  return $images;
 }

 private function get_image($post_id = null) {
  if (is_null($post_id)) {
   $post_id = $this->post_id;
  }

  if (has_post_thumbnail($post_id)) {
   $thumbnail_id = get_post_thumbnail_id($post_id);

   $array = wp_get_attachment_image_src($thumbnail_id, 'single-post-thumbnail');
   $url_thumbnail = $array[0];

   return $url_thumbnail;
  }
  else {
   return null;
  }
 }

 private function get_thumbnail($post_id = null) {
  if (is_null($post_id)) {
   $post_id = $this->post_id;
  }

  if (has_post_thumbnail($post_id)) {
   $thumbnail_id = get_post_thumbnail_id($post_id);

   $array = wp_get_attachment_image_src($thumbnail_id);
   $url_thumbnail = $array[0];

   return $url_thumbnail;
  }
  else {
   return null;
  }
 }

 public function is_viewed($post_id = null) {
  if (is_null($post_id))
   $post_id = $this->post_id;

  global $wpdb;
  $push_viewed = $wpdb->get_blog_prefix() . 'push_viewed';
  return (boolean)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $push_viewed WHERE post_id=%d AND user_id=%d", $post_id, $this->current_user_id()));
 }

 public function set_viewed($post_id = null) {
  if (is_null($post_id))
   $post_id = $this->post_id;

  global $wpdb;
  $push_viewed = $wpdb->get_blog_prefix() . 'push_viewed';
  $wpdb->insert($push_viewed, array('post_id' => $post_id, 'user_id' => $this->current_user_id(), 'timestamp' => current_time('mysql')));
 }

 public function is_read($post_id = null) {
  if (is_null($post_id))
   $post_id = $this->post_id;

  global $wpdb;
  $push_sent = $wpdb->get_blog_prefix() . 'push_sent';
  if ((boolean)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $push_sent WHERE post_id=%d AND user_id=%d", $post_id, $this->current_user_id()))) {
   return $this->is_viewed($post_id);
  }
  else {
   return true;
  }
 }
}
