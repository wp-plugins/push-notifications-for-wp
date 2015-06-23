<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

$admin_dashboard = new PNFW_Admin();

final class PNFW_Admin {
   public function __construct() {
    add_action('admin_menu', array($this, 'menus'));

    add_action('admin_enqueue_scripts', array($this, 'load_admin_meta_box_script'));
 }

 function menus() {
  $admin_capability = 'activate_plugins';
  $editor_capability = 'publish_pages';

  $menu_slug = 'push-notifications-for-wordpress';

  $page_hook_suffix = add_menu_page(
   __('Push Notifications', 'pnfw'),
   __('Push Notifications', 'pnfw'),
   $editor_capability,
   $menu_slug,
   array($this, 'stats_page'),
   plugin_dir_url(__FILE__) . '../assets/imgs/icon-menu.png',
   200);

  // Use the hook suffix to compose the hook and register an action executed when plugin's options page is loaded
  add_action('load-' . $page_hook_suffix , array($this, 'chart_add_scripts'));

  $page_hook_suffix = add_submenu_page(
   $menu_slug,
   __('Settings', 'pnfw'),
   __('Settings', 'pnfw'),
   $admin_capability,
   'pnfw-settings-identifier',
   array($this, 'settings_page'));

  add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'plugin_admin_scripts'));

  add_submenu_page(
   $menu_slug,
   __('OAuth', 'pnfw'),
   __('OAuth', 'pnfw'),
   $admin_capability,
   'pnfw-oauth-identifier',
   array($this, 'oauth_page'));

  add_submenu_page(
   $menu_slug,
   __('App Subscribers', 'pnfw'),
   __('App Subscribers', 'pnfw'),
   $editor_capability,
   'pnfw-app-subscribers-identifier',
   array($this, 'app_subscribers_page'));

  add_submenu_page(
   $menu_slug,
   __('Tokens', 'pnfw'),
   __('Tokens', 'pnfw'),
   $editor_capability,
   'pnfw-tokens-identifier',
   array($this, 'tokens_page'));

  add_submenu_page(
   $menu_slug,
   __('Debug', 'pnfw'),
   __('Debug', 'pnfw'),
   $admin_capability,
   'pnfw-debug-identifier',
   array($this, 'debug_page'));

 }

 function stats_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-stats.php';

  PNFW_Admin_Stats::output();
 }

 function settings_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-settings.php';

  PNFW_Admin_Settings::output();
 }

 function oauth_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-oauth.php';

  PNFW_Admin_OAuth::output();
 }

 function app_subscribers_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-subscribers.php';

  PNFW_Admin_Subscribers::output();
 }

 function tokens_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-tokens.php';

  PNFW_Admin_Tokens::output();
 }

 function debug_page() {
  require_once dirname(__FILE__ ) . '/class-pnfw-admin-debug.php';

  PNFW_Admin_Debug::output();
 }

 function chart_add_scripts() {
  wp_register_script(
   'Chart',
   plugin_dir_url(__FILE__) . '../libs/Chart/Chart.min.js',
   false,
   null,
   true
  );

  wp_register_script(
   'adminCharts',
   plugin_dir_url(__FILE__) . '../assets/js/admin_charts.js',
   array('Chart', 'jquery'),
   '1.0',
   true
  );

     wp_enqueue_script('adminCharts');

  wp_localize_script('adminCharts', 'data_overview', $this->data_for_overview_graph());
 }

 function plugin_admin_scripts() {
  wp_enqueue_media();
  wp_enqueue_script('script', plugin_dir_url(__FILE__) . '../assets/js/script.js', array('jquery'));
 }

 function load_admin_meta_box_script() {
  global $pagenow;

  if (is_admin() && ($pagenow == 'post-new.php' || $pagenow == 'post.php')) {
    wp_register_script(
     'admin_meta_box',
     plugin_dir_url(__FILE__) . '../assets/js/admin_meta_box.js',
     array('jquery'),
     null,
     false);

    wp_enqueue_script('admin_meta_box');

    wp_localize_script('admin_meta_box',
     'strings',
     array(
      'str1' => __('Send and make visible only to', 'pnfw') . ':',
       'str2' => __('Make visible only to', 'pnfw') . ':'
    ));
  }
 }

 private function data_for_overview_graph() {
  global $wpdb;

  $wp_posts = array();
  if (get_option('pnfw_enabled_post_types')) {
   $wp_posts = get_posts(
    array(
     'posts_per_page' => 50,
     'order' => 'DESC',
     'post_type' => get_option('pnfw_enabled_post_types')
    )
   );
  }

  $posts = array();
  $sent = array();
  $read = array();

  foreach ($wp_posts as $post) {
   $short_title = strlen($post->post_title) > 30 ? substr($post->post_title, 0, 30)."..." : $post->post_title;

   array_push($posts, $short_title);

   $push_sent = $wpdb->get_blog_prefix().'push_sent';
   array_push($sent, (int)$wpdb->get_var("SELECT COUNT(*) FROM $push_sent WHERE post_ID = {$post->ID}"));

   $push_viewed = $wpdb->get_blog_prefix().'push_viewed';
   array_push($read, (int)$wpdb->get_var("SELECT COUNT(*) FROM $push_viewed WHERE post_ID = {$post->ID}"));
  }

  $res = array(
   'data_type' => 'overview',
   'post_data' => array(
    "posts" => array_reverse($posts),
    "sent" => array_reverse($sent),
    "read" => array_reverse($read)
   )
  );

  return $res;
 }
}
