# WP Engine Ecommerce Toolkit for WooCommerce

Contributors: wpengine, patrickgarman, octalmage, boogah  
Tags: woocommerce, wp engine, ecommerce, optimization, woo, wpengine, wpe  
Requires at least: 4.4  
Tested up to: 4.5.3  
Stable tag: 1.0  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

Optimize WooCommerce sites using WP Engine’s experience with high-performance Ecommerce websites.

## Description


### Summary

The WP Engine Ecommerce Toolkit for WooCommerce helps you tune your WooCommerce site for optimal performance. The plugin uses optimizations that WP Engine has learned while running some of the most successful WooCommerce sites.

In addition to optimizing your WooCommerce site, this plugin also provides key performance indicators (KPIs), with graphs, to help you assess your WooCommerce site’s performance (WP-Admin > WooCommerce > Reports > KPI Log).

Enabling the KPI Log results in additional processing and storage, so we recommend using the KPI Log feature only while assessing your WooCommerce site’s performance. You can toggle the KPI Log setting at WP-Admin > WooCommerce > Settings > Integration.

## Optimizations

### Auto Logout

This option automatically logs out your customers as they visit pages on your site. Logging out customers makes your site more performant by minimizing the number of logged in users (which reduces server load) and by eliminating personalization on dynamic pages (which improves cacheability).

Auto Logout will not log out customers when they visit cart pages, checkout pages, and account pages. Enable Auto Logout only if you are willing to give up page personalization for site performance.

### Guest Attribution

This ensures that any user who is checking out as a guest has their order assigned to the appropriate Customer account. This is done by checking to see if a user already exists for the email address provided during checkout.

Guest Attribution pairs well with the Auto Logout optimization because it preserves customer attribution for orders with known email addresses, even when a customer gets logged out by Auto Logout.

### Remove Admin Counts

For sites with many orders, the counts listed in your admin sidebar can take time to update. By disabling admin counts, substantial speed gains can be made for administrative users who spend time on the backend of the site.

### Customer Order Index

Some queries, such as the ones that power the My Account page, can be inefficient and slow, especially if you have many return customers. By leveraging a finely-tuned index of order data and customer data, these performance issues can be minimized.

### Disable Cart Fragment JS

A single JavaScript file is responsible for a secondary admin-ajax.php call on most WooCommerce page loads. If your site does not display a cart in your header or sidebar, you should be able to disable this without impacting your site.

### KPI Log

To view the KPI Log, go to WP-Admin > WooCommerce > Reports > KPI Log.

The KPI Log tab has three sections:
High Impact — High-level performance metrics, such as adds to cart and orders processed.
Views — Page views for several categories, such as cart views and thank you page views.
All KPIs — The KPIs from all other sections plus other KPIs, such as the count of searches performed from the site.

When enabled, the KPI Log will track the following metrics:

* Adds to cart
* Orders placed
* Orders processed
* Cart views
* Checkout views
* Order thank you views
* "My Account > Orders" views
* "My Account > Address" views
* "My Account > Edit" views
* "My Account" views
* Product searches
* General searches

No specific user data or product data is tracked. For example, the KPI Log will not show you who added a product to their cart, but you should be able to get some insight on how your visitors use your site.

The KPI Log should be used to assess whether your WooCommerce site is optimized. The KPI Log is *not* recommended for long-term production use because it requires additional processing and storage. To toggle the KPI Log setting, go to WP-Admin > WooCommerce > Settings > Integration.

## Installation


### Automated Installation

With WordPress 2.7 or above, you can simply go to Plugins > Add New in the WordPress Admin. Next, search for "WP Engine Ecommerce Toolkit for WooCommerce" and click Install Now. 

### Manual Installation

1. Upload the plugin file wpe-wc-toolbox.zip to the ‘/wp-content/plugins/’ directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the ‘Plugins’ screen in WordPress.
3. Use the Settings link under ‘WP Engine Ecommerce Toolkit for WooCommerce’ on the Plugins screen to configure the plugin.

## Changelog

**1.0**
* Initial release.
