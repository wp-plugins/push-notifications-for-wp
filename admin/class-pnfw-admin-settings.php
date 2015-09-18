<?php

if (!defined('ABSPATH')) {
 exit; // Exit if accessed directly
}

class PNFW_Admin_Settings {
 public static function output() { ?>
  <style type="text/css">
   input.textfield {
    width: 100%;
   }
   input.upload {
    width: 100px;
    text-align:center;
    vertical-align:"middle";
    padding: 0px;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
   }
   .form-table {
    clear:none !important;
   }
   .inside .submit {
    padding:5px 0 0 0 !important;
   }
   .inside p {
    margin-left: 10px;
    margin-bottom:0;
   }
   .postbox h3 {
    cursor:default !important;
   }
   .postbox h3:hover {
    color:#464646 !important;
   }
  </style>

  <div class="wrap">

  <div id="icon-options-general" class="icon32"></div>
  <h2><?php _e('Settings', 'pnfw'); ?></h2>

  <?php if (isset($_POST['issubmitted']) && $_POST['issubmitted'] == 'yes') {
   $pnfw_enable_push_notifications = (bool)pnfw_get_post('pnfw_enable_push_notifications');
   update_option("pnfw_enable_push_notifications", $pnfw_enable_push_notifications);

   $pnfw_ios_push_notifications = (bool)pnfw_get_post('pnfw_ios_push_notifications');
   update_option("pnfw_ios_push_notifications", $pnfw_ios_push_notifications);
   if ($pnfw_ios_push_notifications) {
    global $feedback_provider;
    $feedback_provider->run();
   }
   else {
    global $feedback_provider;
    $feedback_provider->stop();
   }

   $pnfw_android_push_notifications = (bool)pnfw_get_post('pnfw_android_push_notifications');
   update_option("pnfw_android_push_notifications", $pnfw_android_push_notifications);

   $pnfw_kindle_push_notifications = (bool)pnfw_get_post('pnfw_kindle_push_notifications');
   update_option("pnfw_kindle_push_notifications", $pnfw_kindle_push_notifications);

   $pnfw_ios_use_sandbox = (bool)pnfw_get_post('pnfw_ios_use_sandbox');
   update_option("pnfw_ios_use_sandbox", $pnfw_ios_use_sandbox);

   $pnfw_url_scheme = $_POST['pnfw_url_scheme'];
   update_option("pnfw_url_scheme", $pnfw_url_scheme);

   $pnfw_sandbox_ssl_certificate_media_id = $_POST['pnfw_sandbox_ssl_certificate_media_id'];
   update_option("pnfw_sandbox_ssl_certificate_media_id", $pnfw_sandbox_ssl_certificate_media_id);

   $pnfw_sandbox_ssl_certificate_password = $_POST['pnfw_sandbox_ssl_certificate_password'];
   update_option("pnfw_sandbox_ssl_certificate_password", $pnfw_sandbox_ssl_certificate_password);

   $pnfw_production_ssl_certificate_media_id = $_POST['pnfw_production_ssl_certificate_media_id'];
   update_option("pnfw_production_ssl_certificate_media_id", $pnfw_production_ssl_certificate_media_id);

   $pnfw_production_ssl_certificate_password = $_POST['pnfw_production_ssl_certificate_password'];
   update_option("pnfw_production_ssl_certificate_password", $pnfw_production_ssl_certificate_password);

   $pnfw_google_api_key = $_POST['pnfw_google_api_key'];
   update_option("pnfw_google_api_key", $pnfw_google_api_key);

   $pnfw_adm_client_id = $_POST['pnfw_adm_client_id'];
   update_option("pnfw_adm_client_id", $pnfw_adm_client_id);

   $pnfw_adm_client_secret = $_POST['pnfw_adm_client_secret'];
   update_option("pnfw_adm_client_secret", $pnfw_adm_client_secret);

   $enabled_post_types = pnfw_get_post('pnfw_enabled_post_types');
   update_option("pnfw_enabled_post_types", $enabled_post_types);

   $enabled_object_taxonomies = pnfw_get_post('pnfw_enabled_object_taxonomies');
   update_option('pnfw_enabled_object_taxonomies', $enabled_object_taxonomies);

   $pnfw_use_wpautop = (bool)pnfw_get_post('pnfw_use_wpautop');
   update_option('pnfw_use_wpautop', $pnfw_use_wpautop);

   $pnfw_add_message_field_in_payload = (bool)pnfw_get_post('pnfw_add_message_field_in_payload');
   update_option('pnfw_add_message_field_in_payload', $pnfw_add_message_field_in_payload);

   $pnfw_uninstall_data = pnfw_get_post('pnfw_uninstall_data');
   update_option('pnfw_uninstall_data', $pnfw_uninstall_data);
  }

  $pnfw_enable_push_notifications = (bool)get_option("pnfw_enable_push_notifications");

  $pnfw_ios_push_notifications = (bool)get_option("pnfw_ios_push_notifications");
  $pnfw_android_push_notifications = (bool)get_option("pnfw_android_push_notifications");
  $pnfw_kindle_push_notifications = (bool)get_option("pnfw_kindle_push_notifications");

  $pnfw_url_scheme = get_option("pnfw_url_scheme");

  $pnfw_ios_use_sandbox = (bool)get_option("pnfw_ios_use_sandbox");

  $pnfw_sandbox_ssl_certificate_media_id = get_option("pnfw_sandbox_ssl_certificate_media_id");
  $pnfw_sandbox_ssl_certificate_password = get_option("pnfw_sandbox_ssl_certificate_password");

  $pnfw_production_ssl_certificate_media_id = get_option("pnfw_production_ssl_certificate_media_id");
  $pnfw_production_ssl_certificate_password = get_option("pnfw_production_ssl_certificate_password");

  $enabled_post_types = get_option('pnfw_enabled_post_types', array());
  $enabled_object_taxonomies = get_option('pnfw_enabled_object_taxonomies', array());

  $pnfw_use_wpautop = (bool)get_option('pnfw_use_wpautop');
  $pnfw_add_message_field_in_payload = (bool)get_option('pnfw_add_message_field_in_payload');
  $pnfw_uninstall_data = get_option('pnfw_uninstall_data');

  if ($pnfw_enable_push_notifications && $pnfw_ios_push_notifications) {
   if ($pnfw_ios_use_sandbox) {
    if (!$pnfw_sandbox_ssl_certificate_media_id) { ?>
     <div id="message" class="error"><p><?php _e('Missing sandbox SSL certificate', 'pnfw'); ?></p></div>
    <?php }
    if (!$pnfw_sandbox_ssl_certificate_password) { ?>
     <div id="message" class="error"><p><?php _e('Missing sandbox certificate password', 'pnfw'); ?></p></div>
    <?php }
   }
   else {
    if (!$pnfw_production_ssl_certificate_media_id) { ?>
     <div id="message" class="error"><p><?php _e('Missing production SSL certificate', 'pnfw'); ?></p></div>
    <?php }
    if (!$pnfw_production_ssl_certificate_password) { ?>
     <div id="message" class="error"><p><?php _e('Missing production certificate password', 'pnfw'); ?></p></div>
    <?php }
   }
  }

  $pnfw_google_api_key = get_option("pnfw_google_api_key");

  if ($pnfw_enable_push_notifications && $pnfw_android_push_notifications) {
   if (!$pnfw_google_api_key) { ?>
    <div id="message" class="error"><p><?php _e('Missing Google API Key', 'pnfw'); ?></p></div>
   <?php }
  }

  $pnfw_adm_client_id = get_option("pnfw_adm_client_id");
  $pnfw_adm_client_secret = get_option("pnfw_adm_client_secret");

  if ($pnfw_enable_push_notifications && $pnfw_kindle_push_notifications) {
   if (!$pnfw_adm_client_id) { ?>
    <div id="message" class="error"><p><?php _e('Missing ADM Client ID', 'pnfw'); ?></p></div>
   <?php }
   if (!$pnfw_adm_client_secret) { ?>
    <div id="message" class="error"><p><?php _e('Missing ADM Client Secret', 'pnfw'); ?></p></div>
   <?php }
  } ?>

  <div id="poststuff" class="metabox-holder has-right-sidebar">
   <div class="inner-sidebar">
    <div id="side-sortables" class="meta-box-sortabless" style="position:relative;">
     <!-- postbox -->
     <div class="postbox">
      <h3 class="hndle"><span><?php _e('About this Plugin', 'pnfw'); ?></span></h3>

      <div class="inside">
       <a href="http://www.delitestudio.com/?utm_source=push-notifications-for-wordpress&utm_medium=link&utm_campaign=cross-marketing"><?php _e('Developed by', 'pnfw'); ?> Delite Studio S.r.l.</a>
      </div>
     </div>
     <!-- //-postbox -->


     <!-- postbox -->
     <div class="postbox">
      <h3 class="hndle"><span><?php _e('Push Notifications for WordPress', 'pnfw'); ?></span></h3>

      <div class="inside">
       <?php _e('This is our basic solution for small personal blogs. We also offer a full-featured plugin, Push Notifications for WordPress, designed for all the other websites.', 'pnfw'); ?>
       <ul>
        <li><a href="http://www.delitestudio.com/wordpress/push-notifications-for-wordpress//?utm_source=push-notifications-for-posts&utm_medium=link&utm_campaign=cross-marketing"><?php _e('More info', 'pnfw'); ?></a></li>
        <li><a href="http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/what-are-the-differences-between-push-notifications-for-wordpress-and-push-notifications-for-posts/?utm_source=push-notifications-for-posts&utm_medium=link&utm_campaign=cross-marketing"><?php _e('Differences', 'pnfw'); ?></a></li>
       </ul>
      </div>
     </div>
     <!-- //-postbox -->


     <!-- postbox -->
     <div class="postbox">
      <h3 class="hndle"><span><?php _e('Resources', 'pnfw'); ?></span></h3>

      <div class="inside">
       <a href="http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/"><?php _e('See the online documentation', 'pnfw'); ?></a>
      </div>
      <div class="inside">

      </div>
     </div>
     <!-- //-postbox -->

    </div><!-- //-side-sortables -->
   </div><!-- //-inner-sidebar -->

   <div class="has-sidebar sm-padded">
    <div id="post-body-content" class="has-sidebar-content">
     <div class="meta-box-sortabless">
      <form action="" method="post">

      <!-- postbox -->
      <div class="postbox">
       <h3 class="hndle">
        <span><?php _e('Basic Options', 'pnfw'); ?></span>
       </h3>
       <div class="inside">
        <p>
         <input type="checkbox" name="pnfw_enable_push_notifications" id="pnfw_enable_push_notifications" <?php checked((bool)$pnfw_enable_push_notifications) ?> />
         <label for="pnfw_enable_push_notifications"><?php _e('Send push notifications when a new post is published', 'pnfw'); ?></label>
        </p>

        <p><?php _e('Send push notifications to:', 'pnfw'); ?></p>

        <table class="form-table">
         <tr valign="top">
          <td>
           <input type="checkbox" name="pnfw_ios_push_notifications" id="pnfw_ios_push_notifications" <?php checked((bool)$pnfw_ios_push_notifications) ?> /> <label for="pnfw_ios_push_notifications"><?php _e('iOS devices', 'pnfw'); ?></label><br />
           <input type="checkbox" name="pnfw_android_push_notifications" id="pnfw_android_push_notifications" <?php checked((bool)$pnfw_android_push_notifications) ?> /> <label for="pnfw_android_push_notifications"><?php _e('Android devices', 'pnfw'); ?></label> <br />
           <input type="checkbox" name="pnfw_kindle_push_notifications" id="pnfw_kindle_push_notifications" <?php checked((bool)$pnfw_kindle_push_notifications) ?> /> <label for="pnfw_kindle_push_notifications"><?php _e('Kindle Fire devices', 'pnfw'); ?></label>
          </td>
         </tr>
        </table>

        <p class="submit">
         <input class="button button-primary" type="submit" name="pnfw_save_settings_button" value="<?php _e('Save settings', 'pnfw'); ?>">
        </p>
       </div>
      </div>
      <!-- //-postbox -->

      <?php





      $custom_post_types = array('post');

      if (count($custom_post_types) > 0) { ?>

       <!-- postbox -->
       <div class="postbox">
        <h3 class="hndle">
         <span><?php _e('Send Push Notifications for', 'pnfw'); ?></span>
        </h3>
        <div class="inside">
         <table class="form-table">
          <tr valign="top">
           <td><?php
            foreach ($custom_post_types as $post_type) {
             $post_type_object = get_post_type_object($post_type);
             $checked = is_array($enabled_post_types) ? in_array($post_type_object->name, $enabled_post_types) : false;
            ?>

             <input type="checkbox" name="pnfw_enabled_post_types[]" id="pnfw_enabled_post_types[]" value="<?php echo $post_type_object->name; ?>" <?php checked($checked) ?> /> <label for="pnfw_enabled_post_types[]"><?php echo $post_type_object->label; ?></label><br />

            <?php } ?>
           </td>
          </tr>
         </table>


         <div style="color:#999;">
          <?php _e('Do you want to support custom post types?', 'pnfw'); ?>
          <a href="http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/">
           <?php _e('Upgrade now to Push Notifications for WordPress', 'pnfw'); ?> &rarr;
          </a>
         </div>


         <p class="submit">
          <input name="issubmitted" type="hidden" value="yes" />
          <input class="button button-primary" type="submit" name="pnfw_save_settings_button" value="<?php _e('Save settings', 'pnfw'); ?>" />
         </p>
        </div>
       </div>
       <!-- //-postbox -->

      <?php
      }

      $object_taxonomies = get_object_taxonomies($custom_post_types, 'objects');

      if (count($object_taxonomies) > 0) { ?>

       <!-- postbox -->
       <div class="postbox">
        <h3 class="hndle">
         <span><?php _e('Categories Filterable by App Subscribers', 'pnfw'); ?></span>
        </h3>
        <div class="inside">
         <table class="form-table">
          <tr valign="top">
           <td><?php
            foreach ($object_taxonomies as $object_taxonomy) {
             $checked = is_array($enabled_object_taxonomies) ? in_array($object_taxonomy->name, $enabled_object_taxonomies) : false;
            ?>

             <input type="checkbox" name="pnfw_enabled_object_taxonomies[]" id="pnfw_enabled_object_taxonomies[]" value="<?php echo $object_taxonomy->name; ?>" <?php checked($checked) ?> /> <label for="pnfw_enabled_object_taxonomies[]"><?php echo sprintf('%s (%s)', $object_taxonomy->label, $object_taxonomy->name); ?></label><br />

            <?php } ?>
           </td>
          </tr>
         </table>

         <p class="submit">
          <input name="issubmitted" type="hidden" value="yes" />
          <input class="button button-primary" type="submit" name="pnfw_save_settings_button" value="<?php _e('Save settings', 'pnfw'); ?>" />
         </p>
        </div>
       </div>
       <!-- //-postbox -->

      <?php
      } ?>

      <!-- postbox -->
      <div class="postbox">
       <h3 class="hndle">
        <span><?php _e('URL Scheme', 'pnfw'); ?></span>
       </h3>
       <div class="inside">
        <input name="pnfw_url_scheme" id="pnfw_url_scheme" type="text" style="" value="<?php echo $pnfw_url_scheme; ?>" class="textfield" placeholder="my-app-scheme://">

        <br/><span class="description"><?php _e('When a user registers on this site, he will receive a confirmation email with a verification link. If you have filled in the URL Scheme, after a successful verification from a mobile device the user will be redirected to the URL indicated.', 'pnfw'); ?></span>

        <p class="submit">
         <input name="issubmitted" type="hidden" value="yes" />
         <input class="button button-primary" type="submit" name="pnfw_save_settings_button" value="<?php _e('Save settings', 'pnfw'); ?>" />
        </p>
       </div>
      </div>
      <!-- //-postbox -->

      <!-- postbox -->
      <div class="postbox">
       <h3 class="hndle">
        <span><?php _e('iOS Push Notifications (via Apple Push Notification Service, APNs)', 'pnfw'); ?></span>
       </h3>
       <div class="inside">
        <p>
         <input type="checkbox" name="pnfw_ios_use_sandbox" id="pnfw_ios_use_sandbox" <?php checked((bool)$pnfw_ios_use_sandbox) ?> /> <label for="pnfw_ios_use_sandbox"><?php _e('Use sandbox environment', 'pnfw'); ?></label>
        </p>

        <table class="form-table">
         <tr valign="top">
          <th scope="row">
           <labe><?php _e('Sandbox SSL certificate (.pem)', 'pnfw'); ?></label>
          </th>
          <td>
           <label for='upload_cer' class='uploader' id='pnfw_sandbox_ssl_certificate_media_id'>
            <input name="pnfw_sandbox_ssl_certificate_media_id" type="hidden" value="<?php echo $pnfw_sandbox_ssl_certificate_media_id; ?>"/>
            <input class="button upload" name="upload_image_button" id="upload_image_button" value="<?php if (!$pnfw_sandbox_ssl_certificate_media_id) { _e('Upload', 'pnfw'); } else { _e('Change', 'pnfw'); } ?>" />
           </label>
          </td>
         </tr>

         <tr valign="top">
          <th scope="row">
           <label for="pnfw_sandbox_ssl_certificate_password"><?php _e('Certificate Password', 'pnfw'); ?></label>
          </th>
          <td>
           <input type="password" class="textfield" name="pnfw_sandbox_ssl_certificate_password" id="pnfw_sandbox_ssl_certificate_password" value="<?php echo $pnfw_sandbox_ssl_certificate_password; ?>" maxlength="255" />
          </td>
         </tr>

         <tr valign="top">
          <th scope="row">
           <label><?php _e('Production SSL certificate (.pem)', 'pnfw'); ?></label>
          </th>
          <td>
           <label for='upload_cer' class='uploader' id='pnfw_production_ssl_certificate_media_id'>
            <input name="pnfw_production_ssl_certificate_media_id" type="hidden" value="<?php echo $pnfw_production_ssl_certificate_media_id; ?>"/>
            <input class="button upload" name="upload_image_button" id="upload_image_button" value="<?php if (!$pnfw_production_ssl_certificate_media_id) { _e('Upload', 'pnfw'); } else { _e('Change', 'pnfw'); } ?>" />
           </label>
          </td>
         </tr>

         <tr valign="top">
          <th scope="row">
           <label for="pnfw_production_ssl_certificate_password"><?php _e('Certificate Password', 'pnfw'); ?></label>
          </th>
          <td>
           <input type="password" class="textfield" name="pnfw_production_ssl_certificate_password" id="pnfw_production_ssl_certificate_password" value="<?php echo $pnfw_production_ssl_certificate_password; ?>" maxlength="255" />
          </td>
         </tr>
        </table>

        <p><a href="https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/ProvisioningDevelopment.html#//apple_ref/doc/uid/TP40008194-CH104-SW1" target="_blank"><?php _e('Obtaining the SSL Certificates', 'pnfw'); ?></a></p>

        <p class="submit">
         <input name="issubmitted" type="hidden" value="yes" />
         <input class="button button-primary" type="submit" name="pnfw_save_settings_button" value="<?php _e('Save settings', 'pnfw'); ?>" />
        </p>
       </div>
      </div>
      <!-- //-postbox -->

      <!-- postbox -->
      <div class="postbox">
       <h3 class="hndle">
        <span><?php _e('Android Push Notifications (via Google Cloud Messaging, GCM)', 'pnfw'); ?></span>
       </h3>
       <div class="inside">
        <table class="form-table">
         <tr valign="top">
          <th scope="row">
           <label for="pnfw_google_api_key"><?php _e('Google API Key', 'pnfw'); ?></label>
          </th>
          <td>
           <input type="text" class="textfield" name="pnfw_google_api_key" id="pnfw_google_api_key" value="<?php echo $pnfw_google_api_key; ?>" maxlength="255" />
          </td>
         </tr>
        </table>

        <p><a href="https://developers.google.com/mobile/add" target="_blank"><?php _e('Obtaining the Google API Key', 'pnfw'); ?></a></p>
        <p class="submit">
         <input name="issubmitted" type="hidden" value="yes" />
         <input class="button button-primary" type="submit" name="pnfw_save_settings_button" value="<?php _e('Save settings', 'pnfw'); ?>" />
        </p>
       </div>
      </div>
      <!-- //-postbox -->

      <!-- postbox -->
      <div class="postbox">
       <h3 class="hndle">
        <span><?php _e('Kindle Fire Push Notifications (via Amazon Device Messaging, ADM)', 'pnfw'); ?></span>
       </h3>
       <div class="inside">
         <table class="form-table">
          <tr valign="top">
           <th scope="row">
            <label for="pnfw_adm_client_id"><?php _e('ADM Client ID', 'pnfw'); ?></label>
           </th>
           <td>
            <input type="text" class="textfield" name="pnfw_adm_client_id" id="pnfw_adm_client_id" value="<?php echo $pnfw_adm_client_id; ?>" maxlength="255" />
           </td>
          </tr>

          <tr valign="top">
           <th scope="row">
            <label for="pnfw_adm_client_secret"><?php _e('ADM Client Secret', 'pnfw'); ?></label>
           </th>
           <td>
            <input type="text" class="textfield" name="pnfw_adm_client_secret" id="pnfw_adm_client_secret" value="<?php echo $pnfw_adm_client_secret; ?>" maxlength="255" />
           </td>
          </tr>
         </table>

         <p><a href="https://developer.amazon.com/public/apis/engage/device-messaging/tech-docs/02-obtaining-adm-credentials" target="_blank"><?php _e('Obtaining the Amazon Device Messaging credentials', 'pnfw'); ?></a></p>

         <p class="submit">
          <input name="issubmitted" type="hidden" value="yes" />
          <input class="button button-primary" type="submit" name="pnfw_save_settings_button" value="<?php _e('Save settings', 'pnfw'); ?>" />
         </p>
       </div>
      </div>
      <!-- //-postbox -->

      <!-- postbox -->
      <div class="postbox">
       <h3 class="hndle">
        <span><?php _e('Misc', 'pnfw'); ?></span>
       </h3>
       <div class="inside">
        <p>
         <input type="checkbox" name="pnfw_use_wpautop" id="pnfw_use_wpautop" <?php checked((bool)$pnfw_use_wpautop) ?> />
         <label for="pnfw_use_wpautop"><?php _e('Enable wpautop filter to convert double line-breaks in the content into HTML paragraphs', 'pnfw'); ?></label>
        </p>

        <p>
         <input type="checkbox" name="pnfw_add_message_field_in_payload" id="pnfw_add_message_field_in_payload" <?php checked((bool)$pnfw_add_message_field_in_payload) ?> />
         <label for="pnfw_add_message_field_in_payload"><?php _e('In the Android notification payload add the <code>message</code> field', 'pnfw'); ?></label>
        </p>

        <br/><span class="description"><?php _e('Check this box if you are using Apache Cordova with PushPlugin.', 'pnfw'); ?></span>

        <p>
         <input type="checkbox" name="pnfw_uninstall_data" id="pnfw_uninstall_data" <?php checked((bool)$pnfw_uninstall_data) ?> />
         <label for="pnfw_uninstall_data"><?php _e('Remove data on uninstall', 'pnfw'); ?></label>
        </p>

        <br/><span class="description"><?php _e('Check this box if you would like to completely remove all of its data when the plugin is deleted.', 'pnfw'); ?></span>

        <p class="submit">
         <input name="issubmitted" type="hidden" value="yes" />
         <input class="button button-primary" type="submit" name="pnfw_save_settings_button" value="<?php _e('Save settings', 'pnfw'); ?>" />
        </p>
       </div>
      </div>
      <!-- //-postbox -->

      </form>
     </div>
    </div>
   </div>
  </div>
 </div>
 <?php
 }
}
