=== Abandoned Cart Lite for WooCommerce ===
Contributors: ashokrane, pinal.shah, bhavik.kiri, chetnapatel
Tags: abandon cart, shopping cart abandonment, sales recovery
Requires at least: 1.3
Tested up to: 4.5.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: http://www.tychesoftwares.com/

This easy-to-use plugin gives WooCommerce store owners the ability to recover sales that are lost to abandoned shopping carts by logged-in customers. 

== Description ==

Abandoned Cart plugin works in the background, sending email notifications to your customers, reminding them about their abandoned orders.

The Abandoned Cart plugin allows you to recover orders that were just a step away from closing. It enables you to create automatic & well-timed email reminders to be sent to your customers who have added your products to their cart, but did not complete the order. As a result, with this plugin you will start recovering at least 30% or more of your lost sales. Why let this 30% revenue go unclaimed?

Abandoned Cart Lite plugin enables to do the following things:
<ol>
<li>Recover their abandoned carts in a single click</li>
<li>Identify the Abandoned Orders information, including the products that were abandoned</li>
<li>The plugin now captures abandoned guest carts. A guest user's cart will be captured on the Checkout page, if it is abandoned after entering the email address.</li>
<li>Track abandoned orders value v/s recovered orders value</li>
<li>Admin is notified by email when an order is recovered</li>
<li>Works off-the-shelf as it comes with 1 default email template</li>
<li>Create unlimited email templates to be sent at intervals that you set - Intervals start from 1 hour after cart is abandoned</li>
<li>Add custom variables like Customer First Name, Customer Last name, Customer full name, Cart Link & Product Cart Information in the email template</li>
<li>Copy HTML from anywhere & create templates using the powerful Rich Text Editor</li>
<li>Automatically stops email notifications when a customer makes a purchase or uses the cart recovery link</li>
</ol>

Abandoned Cart PRO plugin enables to do the following additional things:
<ol>
<li>Works off-the-shelf as it comes with 3 default email templates</li>
<li>Offer incentives to customers to return and complete their checkout with discounts and coupons</li>
<li>Add custom variables like Customer Name, Product Information, Coupons, etc. in the email template</li>
<li>Embed WooCommerce coupons & also generate unique coupons in the emails being sent to customers</li>
<li>Track whether expired coupons are causing cart abandonment</li>
<li>Track emails sent, emails opened, links clicked for each template/email</li>
<li>Product report allows you to see which products are being abandoned & which are being recovered the most</li>
<li>Create unlimited email templates to be sent at intervals that you set - Intervals start from 1 minute after cart is abandoned</li>
</ol>

**Pro Version:**

**[Abandoned Cart Pro for WooCommerce 3.3](http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro "Abandoned Cart Pro for WooCommerce")** - The PRO version allows you to track products in abandoned carts, create unlimited email templates, track coupons, keep a track of sent emails & much more.


**Email Sending Setup:**

From version 1.3, it is not mandatory to set a cron job via CPanel for the abandoned cart email notifications to be sent. We are now using WP-Cron that sends the emails automatically whenever a page is requested.

Abandoned Cart Plugin relies on a function called WP-Cron, and this function only runs when there is a page requested. So, if there are no visits to your website, then the scheduled jobs are not run. Generally this method of sending the abandoned cart notification emails is reliable. However, if you are not very confident about the traffic volume of your website, then you can set a manual cron job via Cpanel or any other control panel that your host provides. 

== Installation ==

1. Ensure you have latest version of WooCommerce plugin installed
2. Unzip and upload contents of the plugin to your /wp-content/plugins/ directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The plugin will start working as per the settings entered.


== Frequently Asked Questions ==

= Can the plugin track carts abandoned by guest users? =

Currently there is no provision for tracking guest carts. This is planned in a future release.

UPDATE: This feature has been released in version 2.2.

= Why are abandoned cart notification emails not getting sent? =

Please ensure you have followed the instructions in "Email Sending Setup" right above this FAQ. Additionally, if you have the PRO version, please verify that you have selected "Enable abandoned cart notifications" option in Settings. With this option turned off, the abandoned carts are recorded, but emails are not sent.

= Where can I find the documentation on how to setup the plugin? =

The documentation can be found **[here](https://www.tychesoftwares.com/woocommerce-abandon-cart-plugin-documentation/ "WooCommerce Abandoned Cart Pro")**. The Lite version is a subset of the Pro version, so the same documentation can be used to refer for the Lite version of the plugin.

== Screenshots ==

1. Lists all Abandoned Orders.

2. Lists all email templates.

3. Abandoned Cart Settings.

4. Lists Recovered Orders.

== Changelog ==

= 2.9 =

* Bugs Fixed - Earlier if any user came from abandoned cart reminder email and place the order using PayPal payment gateway and do not reach the order received page. Then plugin was not considering that order as a recovered order. From now onwards if the user came from the abandoned cart reminder email and place the order using PayPal and does not reach the order received the page. Then plugin will consider that cart as a recovered order.

* Bugs Fixed - When the cart is abandoned as a guest & product have the special character in the attributes name, then it was displaying a blank row with only a checkbox on the Abandoned Orders tab. This has been fixed.

* Tweak - If the order is recovered from the abandoned cart reminder email then it will add a note "This order was abandoned & subsequently recovered." for the order.

= 2.8 =

* We have changed the encryption for the links that are sent in the Abandoned cart email notifications. Earlier we were using the mcrypt library for encoding the data. If mcrypt library was not installed on the server, then abandoned cart email notifications were not sent. Now we have used different functions for encoding the string. We have used microtime function & a security key. Using this security key, and after applying an algorithm on it, we generate the encoded string.

* The session now starts only on required pages of the store. Earlier it was started globally. This will help to improve the site performance.

* If billing email address of the logged-in user is not set then it was showing blank space on the abandoned orders list. This has been fixed. Now it will show the email address which was used while registering to the store.

* Earlier if email body was blank and we send the test email then blank email was sent. This has been fixed. Now if email body is blank then test email will not be sent.

* Tweak - Earlier we were populating the guest cart information by looping into the global WooCommerce cart. Now we are not looping & instead using the WooCommerce session object itself.

* Tweak - Earlier if the 'wp-content' directory has been renamed, then wp-load.php file was not included and abandoned cart reminder email was not sent.  Now, we have changed the way of including the wp-load.php file for sending the abandoned cart reminder notifications.

* Tweak - Earlier when {{products.cart}} merge tag is used in abandoned cart email template, then on click of the product name and product image, it was redirecting to the product page. Now it will redirect the user to the cart page.

* Tweak - We are now rounding off the prices with the 'round' function.

= 2.7 =

* New setting named as "Email Template Header Text" is added in Add / Edit template page. It will allow to change the header text of the email which have WooCommerce template style setting enabled for the template.

* From this version, the email sending process will run every 15 minutes instead of every 5 minutes. This will result in improved overall performance of the website.

* When Lite version of the plugin is activated on the site then it was not allowing to activate the PRO version of the plugin. This has been fixed.

* When templates are created / updated and if it has the same duration as one of the existing templates, then new template was not saved. This has been fixed.


= 2.6 =

* The plugin is now using the TinyMCE editor available in WordPress core for the email body content. The external TinyMCE library is removed from the plugin.

* The plugin is made compatible with Huge IT Image Gallery plugin. The test email was not sent to the user when Huge IT Image Gallery plugin was activated.

* The Product Report tab has been redesigned to look consistent with the WordPress style.

= 2.5.2 =

* Abandoned Orders tab has been redesigned with the WordPress WP list tables. "Action" column has been removed. The "Delete" link has been added in the abandoned orders tab. It is capable of deleting the abandoned orders when hovering the abandoned order in the list. It is also capable of deleting abandoned orders in bulk from the "Abandoned orders" tab.

* Email Templates tab has been re-designed to be consistent with the WordPress styling. It is now capable of deleting email templates in bulk. Action column in the Email templates tab has been removed. User can Edit & Delete template using hover affect on the template. This update allows you to activate / deactivate email template from the "Email Templates" page itself without having to edit the template & set it as "Active" by checking the checkbox.

* Recover Orders tab has been re-designed to be consistent with the WordPress style tables. "View Details" column in the Recovered Orders tab has been removed. User can view Details of the recovered cart using the link 'View Details' on the hover affect on 'User name' column.

* New setting named as "Send From This Email Address:" is added in Add / Edit template page. It will allow to change the From Email address of abandoned cart notification.

* New setting named as "Send Reply Emails to:" is added in Add / Edit template page. It will allow the user to change the Reply to email address of the abandoned cart notification.

* If the "Wp-Content" folder name is changed using iThemes Security (formerly Better WP Security) plugin then abandoned cart email notifications were not sent. This has been fixed.

* If the cart has been empty and we have tried to recover the order via email then it was displaying wrong Cart Recovery date. It has been fixed.

* In some cases, when cart or checkout link was clicked from the abandoned cart email notification, it was displaying "Link Expired" in the browser. This has been fixed.

* If the user has emptied their cart before the abandoned cut-off time is reached, for such carts the record in the DB will become blank. Such cart records were displayed in the abandoned orders list. This has been fixed.

= 2.5.1 =
* Some warnings were displayed on Email Templates tab. These have been fixed.

= 2.5 =

* The Settings page for the plugin has been redone. We are now using the WordPress Settings API for all the options on that page.
* When the plugin is installed & activated for the first time, 1 default email template will also be created. The template will be inactive when the plugin is activated.
* A new setting is added on the Add/Edit Template page named as "Active". If this setting is checked, only then the abandoned cart reminder email will be sent to the customers. If this setting is unchecked, then the email template won't be sent to the customers, but still you can keep it in the plugin. By default, this is unchecked.
* A new setting is added on the Add/Edit Template page named as "Use WooCommerce Template Style". If this setting is checked then abandoned cart reminder email will use the WooCommerce style (header, footer, background, etc.) for the notifications. If it is not checked then the regular email will be sent to the customer as per the formatting that is set in the template editor.
For existing users, this setting will remain unchecked. For new users of the plugin, the setting will be enabled for the existing default email template that is provided with the plugin.
* Abandoned cart email notification will be sent to the client's billing address entered on checkout instead of on the email address added by the user while registering to the website. This applies only for logged in users.
* New shortcode "{{cart.abandoned_date}}" has been introduced in this version. It will display the date and time when the cart was abandoned in the abandoned cart email notification.
* When a customer places an order within the abandoned cart cut off time, then the order received page was displaying a warning. This has been fixed.
* Abandoned Orders tab was not sorting according to the "Date" column. Same way, Recovered Orders tab was not sorting according to "Created On" & "Recovered Date" column. This has been fixed.
* Some warnings were displayed on the Abandoned Orders, Recovered Orders and Product Report tab. These have been fixed.
* The 'mailto' link was not working on the Abandoned Order details page. This has been fixed.
* Tweak - Removed the background white color for the add / edit template page.
* Tweak - Abandoned Orders tab will display the user's billing address using which the cart was abandoned. This applies only for logged in users.

= 2.4 =
* Abandon Cart record was not being deleted for users, when they do not reach the order received page but the payment for the order is already done. Also user was receiving the abandoned cart notification for such orders. This has been fixed.

= 2.3 =
* A new setting has been added "Email admin on order recovery". It will send an email to the admin's email address when an abandoned cart is recovered by the customer. This will work only for registered users.
* A new tab "Product Report" has been added. It will list the product name, number of times product has been abandoned and the number of times product has been recovered.

= 2.2 =
* The plugin now captures abandoned guest carts. A guest user's cart will be captured on the Checkout page, if it is abandoned after entering the email address.
* A new shortcode "{{cart.link}}" is added, which will include the cart URL of your shop.
* Fixed some warnings being displayed in the Settings tab.

= 2.1 =
* From this version, you can view the abandoned order details, which includes product details, billing & shipping address, under the Abandoned Orders tab.

= 2.0.1 =
* Applied fix for warning displayed on the abandoned orders page.

= 2.0 =
* The image link was coming broken while creating or editing the template if the image is present on the same server. This is fixed now.
* Added translations file for Hebrew which was contributed by a user.

= 1.9 =
* Fixed security issues pointed out by Wordpress.org review team.

= 1.8 =
* The strings for the products table, added using the shortcode {{products.cart}} in email templates have been added to the .pot, .po and .mo files of the plugin. Now the cart data will be translated to the respective language in the reminder emails as well as the test emails.

= 1.7 =
* Merge fields like {{products.cart}}, {{customer.firstname}}, etc. will be replaced with dummy data in the test emails that are sent from the template add / edit page. This ensures that you get a very close approximation of the actual email that will be delivered to your customers.
* Product image size in the abandon cart notification emails is set to a fixed height & width now.
* On WordPress Multisite, incorrect table prefix was used due to which the plugin was not functioning correctly on multisite installs. This is fixed now.

= 1.6 = 
* We have included .po, .pot and .mo files in the plugin. The plugin strings can now be translated to any language using these files.

= 1.5 =
* A shortcode {{products.cart}} can now be added in the abandoned cart notification emails. It will add the product information in the email like Product image, Product name, Quantity, Price & Total. The shortcode needs to be added from the AC menu from the template editor.
* The default value of the field "Cart abandoned cut-off time" in Settings tab was blank when the plugin is installed. This is now set to 60 minutes upon plugin activation.

= 1.4 =
* The abandoned cart emails were being sent multiple times for a single email template due to a bug. This is fixed.
* The plugin will now work on WordPress Multisite too.

= 1.3 =
* The abandoned cart email notifications are now sent out automatically without the necessity of having to set up a cron job manually.

= 1.2 =
* The test emails were not getting sent.
* Warnings fixed for some of the plugin setting pages.
* The image urls in the email were coming broken, this is fixed.

= 1.1 =
* Compatibility with WooCommerce 2.x versions
* Fixed 404 errors with images & other files

= 1.0 =
* Initial release.