=== Plugin Name ===
Contributors: delitestudio
Tags: push notifications, push notification, push, notifications, notification, mobile push, wordpress push, mobile notifications, mobile app, wordpress app, wordpress android app, wordpress ios app, ios app, ipad app, iphone app
Requires at least: 3.5
Tested up to: 4.3.1
Stable tag: 1.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Send push notifications to iOS, Android, and Fire OS devices when you publish a new post. Without paying fees as it does not use third-party servers.

== Description ==

Send push notifications to iOS, Android, and Fire OS devices when you publish a new post. Straight from your WordPress site, in real-time.

Alert your visitors when new content is published, converting them to regular and loyal readers. It’s like a newsletter, but so much more effective. Keep your audience engaged.

Push Notifications for WordPress (Lite) allows you to focus on building beautiful and unique apps, without developing your own server-side back-end. Content for the apps is collected automatically from your WordPress site, so no extra work is needed to maintain them.

With Push Notifications for WordPress (Lite) you can send, for each post, a maximum of 1,000 notifications per platform (e.g. 1,000 for iOS, + 1,000 for Android, + 1,000 for Fire OS).

> Push Notifications for WordPress (Lite) is our basic solution for small personal blogs. We also offer a full-featured plugin with a reduced memory footprint and unlimited notifications, [Push Notifications for WordPress](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/), designed for all the other websites. If you’re not sure which plugin is right for you, compare the features [here](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/what-are-the-differences-between-push-notifications-for-wordpress-and-push-notifications-for-posts/).

https://www.youtube.com/watch?v=Mt7I72UzoSY&rel=0

= Key Features =

Push Notifications for WordPress (Lite) natively supports:

* Apple Push Notification service (APNs)
* Google Cloud Messaging (GCM)
* Amazon Device Messaging (ADM)

**No charge for delivery.** You don’t have to pay any fees since Push Notifications for WordPress (Lite) does not use any third-party’s server.

**Instant notifications.** Notifications appear as message alerts, badge updates, and even sound alerts.

**Powerful APIs.** Provides easy to use REST APIs, available via HTTP. Send and receive data using the simple JSON standard. More info [here](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/documentation/).

**Allow users to receive notifications of their choice.** If you want, users can choose the categories of post of which receive push notifications. People are busy and do not like to have their time wasted. And when you do that you’re likely to lose that subscriber.

**Optional support for OAuth.** Any request sent to the API that are not properly signed will be denied.

**Android and iOS libraries.** Save hours of work by using our [Android](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/push-notifications-library-for-android/) and [iOS](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/push-notifications-library-for-ios/) libraries in your apps.

**Works with native apps, Cordova, Ionic, PhoneGap, and more frameworks.** Build beautiful and interactive mobile apps using your preferred technology. We suggest [WordPress Hybrid Client](http://wphc.julienrenaux.fr) to build amazing applications effortless.

**Localization ready.** Thanks to the presence of the POT (Portable Object Template) file, it’s really easy for you to provide your own translation files, with English and Italian translation out of the box.

> WPML is supported only by the premium [Push Notifications for WordPress](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/).

= Additional Information =

Push Notifications for WordPress (Lite) works exactly as you’d expect:

* Publishing a post **will** trigger push notifications.
* Saving a post as a draft **will not** trigger push notifications.
* Publishing a private post **will not** trigger push notifications.
* Static pages **will not** trigger push notifications.
* Scheduled posts **will** trigger push notifications at the time they’re scheduled to publish.

> Custom post types are supported only by the premium [Push Notifications for WordPress](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/).

= Who Is This Plugin For? =

This plugin is primarily intended for mobile developers who do not want to develop their server-side back-end. Supporting push notifications is incredibly complicated. This plugin lets you focus on creating the apps, without the hassle.

= Can You Build The Apps For Me? =

Yes. We’re a team of mobile developers. We created native [Android](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/push-notifications-library-for-android/) and [iOS](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/push-notifications-library-for-ios/) libraries, a [pre-packaged Android app](http://www.delitestudio.com/product/android-app-for-wordpress/), and we put our expertise to work on custom projects for companies that need great apps. Interested? [Contact us](http://www.delitestudio.com/contact/).

= Getting Started =

1. Build your iOS, Android, and/or Fire OS apps. Being a mobile developer, you should know how to do that (or use our [Android](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/push-notifications-library-for-android/) and [iOS](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/push-notifications-library-for-ios/) libraries, our [pre-packaged Android app](http://www.delitestudio.com/product/android-app-for-wordpress/), or [request a quote from our team](http://www.delitestudio.com/contact/)).
2. Install this plugin on your WordPress site.
3. Change the WordPress permalink structure (Settings → Permalinks) from "default" to one of the so-called "pretty" permalinks.
4. Enable push notifications on the plugin's settings page (providing required certificates and keys).
5. Connect the apps to your WordPress site using the included APIs. More info [here](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/documentation/).

Now, when users launch the apps, their devices will automatically register to your site. As soon as a new post is published, a push notification is sent to registered devices, with the title of the post.

= Detailed documentation =

You can find detailed documentation on the [official website](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/documentation/).

> The documentation refers to the premium version. Not everything is applicable to the free version. Compare the features [here](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/what-are-the-differences-between-push-notifications-for-wordpress-and-push-notifications-for-posts/).

= Uninstall Push Notifications for WordPress (Lite) =

If you deactivate and delete Push Notifications for WordPress (Lite), we leave data created by the plugin. Although WordPress will tell you that we do remove data on uninstall, we don’t.

If you need to remove ALL Push Notifications for WordPress (Lite) data, including tokens, users, and settings, go to: Push Notifications → Settings → Misc, and enable "Remove data on uninstall". Then when you deactivate and delete the plugin from the WordPress plugin admin, it will delete all data.

= Support =

The Delite Studio team does not provide support for the Push Notifications for WordPress (Lite) plugin on the WordPress.org forums. Ticket support is available to people who bought the premium plugin only. Note that the premium [Push Notifications for WordPress](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/) has several extra features too, so it might be well worth your investment!

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `push-notifications-for-wordpress-lite` folder to the `/wp-content/plugins/` directory
2. Activate Push Notifications for WordPress (Lite) through the 'Plugins' menu in WordPress
3. Configure the plugin by going to the Push Notifications menu that appears in your admin menu

== Frequently Asked Questions ==

> Full FAQs on the [premium plugin's page](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/faqs/).

= How is this plugin different from other push notifications plugins? =

Other plugins use third-party server's that send notifications for a fee. This plugin has a built in hub, allowing WordPress to send out the push notifications directly. For free.

= Can I see Push Notifications for WordPress (Lite) in action? =

Of course: we have created a video that demonstrates how it works:

https://www.youtube.com/watch?v=Mt7I72UzoSY&rel=0

= Who is this plugin for? =

Push Notifications for WordPress (Lite) is primarily intended for mobile developers who do not want to develop their server-side back-end. Supporting push notifications is incredibly complicated. This plugin lets you focus on creating the apps, without the hassle. And if you don’t want to build the app yourself, [we have that for you, too](http://www.delitestudio.com/product/android-push-app/ "Android Push App").

= What are the system requirements for Push Notifications for WordPress (Lite)? =

Push Notifications for WordPress (Lite) requires:

* WordPress 3.5 or later with "pretty" permalinks.
* PHP 5.3 or later.
* Inbound and outbound TCP packets over ports 2195 and 2196 (for iOS notifications).
* PHP's cURL support (for Android and Kindle notifications).

= You are selling a plugin called Push Notifications for WordPress. What’s the difference? =

You can find the difference [here](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/what-are-the-differences-between-push-notifications-for-wordpress-and-push-notifications-for-posts/ "What are the differences between Push Notifications for WordPress (Lite) and Push Notifications for WordPress?").

= Can I migrate from Push Notification for WordPress (Lite) to Push Notification for WordPress? =

Yes. We have tried to make the process very simple.

Follow these steps:

1. Upgrade Push Notification for WordPress (Lite) to the latest version.
2. Make sure the setting "Remove data on uninstall" is unchecked.
3. Upload Push Notification for WordPress without activating it.
4. Disable Push Notification for WordPress (Lite).
5. Activate Push Notification for WordPress.
6. Uninstall Push Notification for WordPress (Lite).

That's all! Enjoy all the advanced features of Push Notification for WordPress!

= With no or very little programming experience, how can I build the apps? =

If you don’t know how to build the app yourself, [we have a pre packaged Android app, too](http://www.delitestudio.com/product/android-app-for-wordpress/ "Android Push App") (iOS app coming soon).

= What is the Feedback Provider? =

The Apple Push Notification service includes a feedback service to give us information about failed remote notifications (more info in the [Local and Remote Notification Programming Guide](https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/CommunicatingWIthAPS.html#//apple_ref/doc/uid/TP40008194-CH101-SW3)). Luckily you do not have to worry because our plugin does everything for you.

= Can I send a test notification to a specific device? =

You can test a push notification through the Tokens page.

= Does it work with W3 Total Cache? =

Yes, but it requires a great deal of caution.

Two things in particular:

1. In **Performance → Page cache → Rejected user agents** you have to add the user agent of your app so that it is never sent a cached copy.
2. In **Performance → Database cache → Never cache the following pages** you have to add `/pnfw/*`

= Does it work with WPML? =

No, WPML is only supported by the premium version [Push Notifications for WordPress](http://www.delitestudio.com/wordpress/push-notifications-for-wordpress/).

= Why am I getting a Web page when connecting to the `http://yoursite/pnfw/register/`? =

You are using the default WordPress permalink structure. Change the WordPress permalink structure (**Settings → Permalinks**) from "default" to one of the so-called "pretty" permalinks.

= I have issues with sending push notifications. What should I do? =

We suggest you to carefully read the log on the Debug plugin page (**Push Notifications → Debug**). Notifications are sent asynchronously: the Debug page gather in one place all the information and errors you might find useful. Check it frequently.

= I have issues with sending iOS push notifications. What should I do? =

We suggest you to read the Apple's [Technical Note TN2265](https://developer.apple.com/library/ios/technotes/tn2265/_index.html).

= Why am I getting the error `Unable to connect to ssl://gateway.push.apple.com:2195`? =

Your server is unable to connect to the push service.

This can be caused by:

* The SSL certificate you are using, which is invalid.
* The certificate password you specified, which is not correct.
* The fact that Push Notifications for WordPress can't receive inbound and outbound TCP packets over ports 2195 and 2196.

= Strange things happen. The same token appears more than once in the Tokens page, APIs return errors, and so on. What should I do? =

You've probably installed W3 Total Cache. Read our notes above.

= When I send a test notifications everything works properly, while when I publish a post nothing is sent. What should I do? =

If you've already checked that all settings are correct, it could be a hosting or WordPress configuration problem.

In short, to send in the background push notifications, we use the WordPress standard function `wp_schedule_single_event()`. We have verified that it does not work properly if:

* In your `wp-config.php` is active the define `DISABLE_WP_CRON`. It disables background execution of wp-cron by WordPress required to send notifications of posts.
* Your hosting is blocking the call to `fsockopen()`. WordPress uses the `fsockopen` PHP function to make a request to the `wp-cron.php` file that's located in the WordPress installation directory.

Our advice is to contact your hosting provider.

= Can you build the apps for me? =

Yes. We're a team of mobile developers. We put our expertise to work on custom projects for companies that need great apps. Interested? [Contact us](http://www.delitestudio.com/contact/).

= Still have questions? =

Let us know, we will do everything to help you. [Contact us →](http://www.delitestudio.com/contact/ "Contact")

== Screenshots ==

1. The settings page.
2. An example of push notification received on an iOS device.
3. An example of push notification received on an Android device.
4. The Push Notifications widget on Add New Post page.
5. The Tokens page.
6. The OAuth page (with OAuth disabled).
7. The OAuth page (with OAuth enabled).

== Changelog ==

= 1.4 =
* New option to customise notification sound for iOS apps.
* New API to set the flag read/unread of a post.
* New option to disable email verification.
* Now manages the users already registered with different roles without returning "Email already exists".
* Updated internal libraries.

= 1.3 =
* Changed the sender's name in the activation emails.
* Full parameters validation.
* Older PHP version fix.
* Minor bug fixes.

= 1.2 =
* Full support for WordPress 4.3.
* New logs, also for APIs, more frequent and detailed.
* Minor bug fixes.

= 1.1.1 =
* Minor bug fixes.

= 1.1 =
* Option to support Apache Cordova with PushPlugin.

= 1.0 =
* First public release.

== Upgrade Notice ==

== Prerequisites ==

Push Notifications for WordPress (Lite) requires:

* WordPress 3.5 or later with “pretty” permalinks.
* PHP 5.3 or later.
* Inbound and outbound TCP packets over ports 2195 and 2196 (for iOS notifications).
* PHP’s cURL support (for Android and Kindle notifications).

To begin using this plugin, you first need an app that uses one of the supported push notification services: APNs (Apple Push Notification service), GCM (Google Cloud Messaging), or ADM (Amazon Device Messaging).

To send push notifications to iOS devices, you need the Apple Push Notification service SSL certificate in the .PEM format. For more information, see [Provisioning and Development](https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/ProvisioningDevelopment.html#//apple_ref/doc/uid/TP40008194-CH104-SW1) in the Apple Local and Push Notification Programming Guide.

To send push notifications to Android devices, you need to obtain the Google API Key. For more information, see [GCM Architectural Overview](https://developers.google.com/mobile/add).

To send push notifications to Fire OS devices, you need to obtain the Client ID and Client Secret. For more information, see [Obtaining ADM Credentials](http://developer.amazon.com/sdk/adm/credentials.html).
