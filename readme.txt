=== Plugin Name ===
Plugin Name: Category Email Subscribe
Plugin URI: 
Description: Plugin that allows Users to Subscribe for Emails based on Category.They will receive an email when a post is published in the category they have subscribed to.
Version: 1.0
Author: Uttam Mogilicherla
Author URI: http://mpiresolutions.com
Author Email: mpiresolutions@gmail.com
License: GPL

== Description ==

Surprisingly there is no plugin available to allow users to subscribe for posts on a wordpress website based on category.
 A subscriber for one category might not want to receive posts of another category. This plugin will help you to do that.
 Once a subscriber is added for a particular category, he/she will receive emails as soon as a post is published in that category.


** Features **
1. Add Subscribers for their desired category
2. Use Widget or Short Code to display Subscriber Form
3. Add the Subscribers manually, or upload in batch from a CSV file.
4. Email will be sent to all subscribers as soon as a post is published in that category.
5. Unsubscribe functionality for updating subscribtion.
6. Mandrill email service is integrated.
7. Export Subscriber in CSV file.

== Installation ==

1. Download the Plugin using the Install Plugins 
   OR 
   Upload folder `category-email-subscribe` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add Subscribers in Category Email Subscribe > Subscribers (See How to use in Other Notes)
3. Place [category_subscribe_form] in your page/post where you want to display the subscriber form.
4. Place [category_unsubscribe_form] in your page/post where you want to display the unsubscriber form.
5. You may also use the Widget : Category Email Subscribe Form to display subscriber form.

== How To Use ==
1. Go To **Category Email Subscribe** In Side Menu
2. Enter the Send Email from Email and Name in Settings.
3. Enter Mandrill API key in the API field.
4. Enter email template, you can use Email Template Tokens: %post_title%, %author_name%, %post_date%, %featured_image%, %post_content1%
5. Add **Subscribers** by :
a. Allow users to Subscribe using Subscription Form
   You can either use the widget to display the Subscription from
   Or use shortcode [category_subscribe_form] to display subscription form.
b. Upload a Subscriber Manually
   Go to **Category Email Subscribe > Subscriber **
   Go to **Add a Subscriber**
   Enter the details and press button *Subscribe*
c. Upload using CSV File
   The Format of CSV File must be as below :
     *The First line must be headers as it is ignored while uploading.*
     From the second line, the data should begin in following order :
		**Name,Email,Category ID**
         *Category ID* : 0 for all categories, Category ID for a particular category.
6. Use Export CSV button to export Subscribers.
7. Update Subscriber by adding shortcode [category_unsubscribe_form] to display unsubscription form.
8.  The Added Subscribers will be shown in the admin table 
 





