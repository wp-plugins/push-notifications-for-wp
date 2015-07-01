<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

class PNFW_Admin_Debug {
 public static function output() {

  if (isset($_POST['issubmitted']) && $_POST['issubmitted'] == 'yes') {
   if (!wp_verify_nonce($_POST['_wpnonce'], 'empty_log')) {
    _e('Are you sure you want to do this?', 'pnfw');
    die;
   }
   else {
    $pnfw_empty_log = isset($_POST['pnfw_empty_log']) ? $_POST['pnfw_empty_log'] : 1;

    if (isset($pnfw_empty_log) && $pnfw_empty_log == 0) {
     self::empty_log();
    }
   }
  } ?>

  <div class="wrap">
   <div id="icon-options-general" class="icon32"></div>
   <h2><?php _e('Debug', 'pnfw'); ?>
    <a href="<?php echo add_query_arg('pnfw_download_logs', 'true', admin_url('admin.php?page=pnfw-debug-identifier')); ?>" class="add-new-h2"><?php _e('Export CSV', 'pnfw'); ?></a>
   </h2>

   <h3><?php _e('Feedback Provider', 'pnfw'); ?></h2>

   <?php _e('Feedback Provider is', 'pnfw'); ?>

   <strong><?php
   global $feedback_provider;
   $feedback_provider->is_active() ? _e('active', 'pnfw') : _e('disabled', 'pnfw'); ?></strong>

   <?php
   if ($feedback_provider->is_active()) {
    printf(__('(next scheduled event: %s)', 'pnfw'), date('Y-m-d H:i:s', $feedback_provider->next_scheduled()));
   } ?>

   <h3><?php _e('Checks', 'pnfw'); ?></h2>

   <p><code>DISABLE_WP_CRON</code> <?php _e('is', 'pnfw'); ?>

   <strong><?php
   (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) ? _e('true (BAD! please fix)', 'pnfw') : _e('false (GOOD!)', 'pnfw');
   ?></strong></p>

   <p><code>fsockopen</code> <?php _e('function is', 'pnfw'); ?>

   <strong><?php
   function_exists('fsockopen') ? _e('enabled (GOOD!)', 'pnfw') : _e('disabled (BAD! please fix)', 'pnfw');
   ?></strong></p>

   <h3><?php _e('Logs', 'pnfw'); ?></h2>

   <?php global $wpdb;

   $push_logs = $wpdb->get_blog_prefix() . 'push_logs';
   $logs = $wpdb->get_results("SELECT * FROM $push_logs ORDER BY id DESC;");

   $prev_timestamp = 0;

   foreach ($logs as $log) {
    $current_timestamp = strtotime($log->timestamp);

    if ($prev_timestamp - $current_timestamp > 10) {
     echo '<hr/>';
    }

    echo '<strong>' . $log->timestamp . "</strong>: " . $log->text . '<br/>';

    $prev_timestamp = $current_timestamp;
   }
   ?>

   <form action="" method="post">
    <input name="pnfw_empty_log" type="hidden" id="pnfw_empty_log" value="0" />

    <p class="submit">
     <?php wp_nonce_field('empty_log'); ?>

     <input name="issubmitted" type="hidden" value="yes" />
     <input class="button button-primary" type="submit" name="pnfw_empty_log_button" value="<?php _e('Empty Log', 'pnfw'); ?>" />
    </p>
   </form>
  </div>
 <?php }

 public static function empty_log() {
  global $wpdb;

  $table_name = $wpdb->get_blog_prefix() . 'push_logs';
  $wpdb->query("DELETE FROM $table_name;");
 }
}
