<?php

// if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN'))
    exit();

function pnfw_delete_plugin() {
 global $wpdb;

 $table_name = $wpdb->get_blog_prefix() . 'push_tokens';
 $wpdb->query("DROP TABLE IF EXISTS $table_name;");

 $table_name = $wpdb->get_blog_prefix() . 'push_viewed';
 $wpdb->query("DROP TABLE IF EXISTS $table_name;");

 $table_name = $wpdb->get_blog_prefix() . 'push_sent';
 $wpdb->query("DROP TABLE IF EXISTS $table_name;");

 $table_name = $wpdb->get_blog_prefix() . 'push_excluded_categories';
 $wpdb->query("DROP TABLE IF EXISTS $table_name;");

 $table_name = $wpdb->get_blog_prefix() . 'push_logs';
 $wpdb->query("DROP TABLE IF EXISTS $table_name;");

 $table_name = $wpdb->get_blog_prefix() . 'postmeta';
 $wpdb->query("DELETE FROM $table_name WHERE meta_key = 'pnfw_do_not_send_push_notifications_for_this_post' OR meta_key = 'pnfw_user_cat';");

 $user_query = new WP_User_Query(array('role' => 'app_subscriber'));

 foreach ($user_query->results as $user) {
  if (empty($user->user_email)) {
   if (is_multisite()) {
    require_once(ABSPATH . 'wp-admin/includes/ms.php');
    if (is_user_member_of_blog($user->ID)) {
     wpmu_delete_user($user->ID);
    }
   }
   else {
    wp_delete_user($user->ID);
   }
  }
 }

 delete_option('pnfw_db_version');
 delete_option('pnfw_posts_per_page');
 delete_option('pnfw_last_save_timestamp');
 delete_option('pnfw_enable_push_notifications');
 delete_option('pnfw_ios_push_notifications');
 delete_option('pnfw_android_push_notifications');
 delete_option('pnfw_kindle_push_notifications');
 delete_option('pnfw_url_scheme');
 delete_option('pnfw_ios_use_sandbox');
 delete_option('pnfw_sandbox_ssl_certificate_media_id');
 delete_option('pnfw_sandbox_ssl_certificate_password');
 delete_option('pnfw_production_ssl_certificate_media_id');
 delete_option('pnfw_production_ssl_certificate_password');
 delete_option('pnfw_google_api_key');
 delete_option('pnfw_adm_client_id');
 delete_option('pnfw_adm_client_secret');
 delete_option('pnfw_api_consumer_key');
 delete_option('pnfw_api_consumer_secret');
 delete_option('pnfw_enabled_post_types');
 delete_option('pnfw_enabled_object_taxonomies');
 delete_option('pnfw_use_wpautop');
 delete_option('pnfw_add_message_field_in_payload');
 delete_option('pnfw_uninstall_data');

 flush_rewrite_rules();
}

if (get_option("pnfw_uninstall_data")) {
 pnfw_delete_plugin();
}
