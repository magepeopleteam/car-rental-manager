===  Car Rental Manager – Online Vehicle Booking System ===
Contributors: magepeopleteam, hamidxazad, aamahin, sjrubel10
Author URI : https://mage-people.com
Tags: Car Rental, Ride Booking, Cab Booking, Car
Requires at least: 5.6
Stable tag: 1.5.0
Tested up to: 6.9
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
	
Car Rental Manager – ready-to-use WordPress car rental booking plugin. Manage vehicles, payments, and bookings effortlessly, with or without WooCommerce.


== Description ==

Launch your car rental business effortlessly with Car Rental Manager, a powerful and free WordPress plugin that lets you create a professional car rental booking system in just a few steps!
Whether you're renting cars, scooters, vans, or any other vehicles, Car Rental Manager offers a robust and user-friendly solution to manage your rental business directly from your WordPress website. Perfect for entrepreneurs, small businesses, or web agencies, this plugin empowers you to set up a seamless booking system without coding expertise. Take payments either through WooCommerce or through the plugin's own built-in Custom Payment checkout — WooCommerce is optional, so you can launch with Offline Payment (bank transfer, cash or pay-on-pickup) and add a cart later if you need one.
The free version of Car Rental Manager includes a comprehensive set of features to get you started, with the option to upgrade to the Pro version for advanced functionality directly from your WordPress admin panel. Compatible with any WordPress theme, this plugin ensures a polished and professional booking experience that blends seamlessly into your website.

Car rental manager is an easy-to-use car rental booking management system that simplifies and streamlines car rental operations.

Let's look at the key features that make the plugin more acceptable in its category.

## Key Features:

* ⚡ **Effortless Setup** – Launch your rental system in minutes with a simple guided setup—no coding skills required.  

* 🛒 **WooCommerce Integration (optional)** – Seamlessly manage bookings and accept secure payments via popular gateways.  

* 💳 **Works Without WooCommerce** – Switch Booking Mode to Custom Payment and take bookings through the plugin's own checkout, with free Offline Payment built in (PayPal and Stripe in Pro).  

* 💵 **Flexible Pricing Options**  
   - Tiered Discounts: Automatic discounts by rental duration or booking volume.  
   - Day-wise Pricing: Set different rates for specific weekdays.  
   - Seasonal Pricing (Free): Adjust prices for holidays or peak seasons.  

* 🔁 **Recurring Bookings** – Allow customers to schedule repeat rentals for consistent business.  

* 📅 **Advanced Booking Control** – Define how far in advance customers can book.  

* ⏰ **24/7 Availability** – Support round-the-clock rentals for maximum convenience.  

* 🚗 **Vehicle Management** – Create detailed vehicle profiles with images, features, and descriptions.  

* 📆 **Availability Calendar** – Show real-time availability with an interactive monthly calendar.  

* ✅ **Streamlined Booking Flow** – User-friendly, customizable front-end booking process, powered by WooCommerce or by the plugin's own checkout.  

* 📦 **Order & Admin Management** – Track and manage reservations with an intuitive back-end dashboard.  

* 🌍 **Multi-Language Ready** – Reach global customers with built-in translation and localization support.  

* 🔗 **Shortcode Support** – Embed booking forms, calendars, and listings anywhere on your site.  

* 📱 **Responsive by Design** – Optimized for desktops, tablets, and mobile devices.  


## Explore The Demo:
&#9989;  [Live Demo for Car Rental ](https://car.wprently.com/)
&#9989;  [Online Documentation](https://docs.mage-people.com/plugins/car-rental/overview)

## Buy Pro Version:
&#9989;  [Get Pro Version ](https://mage-people.com/product/wordpress-car-rental-plugin/)

== Guideline ==
Shortcode:
`
[mpcrbm_booking form='inline' progressbar='no']
`
This is a simple shortcode that will display only the search form, similar to our homepage

`
[mpcrbm_booking form='inline' title='yes' progressbar='no' search_result='yes' ajax_search='yes']
`

This is a shortcode that you will decide when the title will show or not. If title=no, then the search form title bar will not show, and if search_result='yes' then the  default search result will show with form, and if ajax_search='yes' then the  search result will show as ajax, not a redirection.
This shortcode will show the form as an inline form, and  it can also be a  horizontal form, and the progressbar can be yes or no

`
[mpcrbm_booking form='horizontal' progressbar='no']
`
and also 
`
[mpcrbm_car_list mpcrbm_left_filter='yes' style='grid' show='6' ]
`
This is a shortcode to display the car list with a left filter.

With **Car Rental Manager**, you can transform your WordPress site into a fully functional car rental platform in just a few steps:

1. Install the free plugin from the WordPress repository.  
2. Add your vehicles, set pricing rules, then pick a Booking Mode under Payments — WooCommerce, or Custom Payment with no WooCommerce required.  
3. Embed the booking system on your site using shortcodes.  
4. Start accepting bookings and managing your rental business with ease!  

This free plugin provides all the tools you need to kickstart your car rental business at no cost.  

* Features like **tiered discounts**, **day-wise pricing**, and **seasonal pricing** give you the flexibility to compete in any market.  
* **Flexible payments** — WooCommerce's gateways, or the built-in Custom Payment checkout, whichever suits your business.  
* For advanced features like **PDF export** and **form support**, the Pro version is available directly from your WordPress dashboard.




== Installation ==
Download the car-rental-manager.zip file from the WordPress plugin repository.
Log in to your WordPress admin dashboard.
Navigate to "Plugins" > "Add New."
Click the "Upload Plugin" button at the top of the page.
Choose the car-rental-manager.zip file and click "Install Now."
Once installed, click "Activate" to enable the Car Rental Manager WordPress plugin.

== Frequently Asked Questions ==

= How the calculation works? =
If a customer books for less than 24 hours, it would calculate the rent as one day; if more than 24 hours but less than 48, it would count as two days, and vice versa.

= Can I add extra feature of a transport? =
Yes, you can add extra features like seat number, car model, etc.

= Can I offer extra service? =
Yes you can offer extra services along with the car

= Where do I report security bugs found in this plugin? =
Please report security bugs found in the source code of the Car Rental Manager for WordPress plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/b1431560-8325-44d1-9a15-6f0ccfb485d4). The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

== Changelog ==

= 1.5.0 =
WooCommerce is now optional. The plugin can take bookings either through the WooCommerce cart and checkout, or through its own built-in Custom Payment checkout — chosen with a single Booking Mode setting.

**New Features**
* Booking Mode — one explicit setting decides which checkout owns a booking: **WooCommerce** (cart, checkout and orders as before) or **Custom Payment** (the plugin's own standalone checkout, no WooCommerce required). Previously the plugin refused to load at all without WooCommerce active.
* Payments settings tab — Booking Mode selector, a WooCommerce payment-gateway manager that renders each gateway's own native settings inline, and gateway cards for PayPal, Stripe and Offline Payment with their own Configure dialogs.
* Offline Payment (free) — accept bank transfer, cash or pay-on-pickup bookings with no payment processor at all. Includes its own checkout page, a booking confirmation page and a customer confirmation email. PayPal and Stripe remain Pro.
* Currency Settings tab — symbol, position, decimals and separators used to format prices when Booking Mode is Custom Payment. In WooCommerce mode WooCommerce's own currency settings continue to apply.
* Bookings list — free installs can now see their bookings in wp-admin. Shows bookings from both checkouts with a source tag; statistics, filtering, booking details and status management remain Pro.
* Payment Method card on the car add/edit screen — shows the live booking mode and enabled gateway(s), with a popup to change either without leaving the car you are editing.
* Payment setup notices — a notice that names the one action that will actually fix the site's situation (choose a mode, enable a WooCommerce gateway, or enable Offline Payment), and updates itself the moment a gateway is enabled rather than waiting for a page reload.

**Improvements**
* The WooCommerce installer is no longer a blocking popup on every admin page. It appears only on this plugin's own screens, is dismissible, and offers "No thanks — I'll use Custom Payment instead" as a real answer that switches the mode for you.
* Activating WooCommerce later automatically backfills the hidden mirror products for any vehicle added while it was inactive, so those vehicles stay bookable through the cart.
* A site already taking Offline bookings is no longer silently switched to WooCommerce when WooCommerce is activated — the chosen mode is remembered.
* The standalone booking search panel is no longer confined to a block theme's narrow content column.
* Vehicle photos on the checkout and confirmation pages are served at a higher resolution and cropped to a consistent banner instead of being upscaled.
* The booking confirmation page now offers "Book this vehicle again", "Browse vehicles" and "Back to home" instead of being a dead end.

**Fixes**
* Fixed "Book Now" and "Continue" doing nothing at all when WooCommerce was inactive. Both were keyed on the hidden WooCommerce product id, which does not exist in Custom Payment mode, so the request was skipped silently. The vehicle itself is now the booking's identity.
* Fixed a fatal error ("Call to undefined function wc_price()") on the vehicle taxonomies screen, and the same class of error across roughly ninety calls into WooCommerce (`wc_price`, `wc_get_order`, `wc_get_orders`, `WC_Tax`, `wc_clean`, currency helpers) that only became reachable once WooCommerce could be absent. All now degrade instead of crashing.
* Fixed vehicle stock never being checked when booking from the search results — the vehicle id was computed but never sent, so a fully booked vehicle could still be added to the cart.
* Fixed a fully booked vehicle producing a silent failure rather than telling the customer why.
* Fixed return times being stored in the booking's raw decimal form (for example "0.5"), which displayed as nonsense on the confirmation page and, because that value is compared as a date, made standalone bookings invisible to availability checks.

**Security**
* Booking confirmation pages are now guarded by a random per-booking token. They previously used a value derived from sequential user, order and post ids, which could be guessed to read another customer's booking.
* Payment credentials entered in the gateway dialogs are preserved when the settings form is saved, so they can never be blanked by an unrelated save.

= 1.4.0 =
Major release: a rebuilt admin experience, a single Branch Management workspace, customer-side booking self-service, and a redesigned front end.

**New Features**
* Vehicle replacement with customer approval — when the agency proposes a different vehicle for a confirmed booking, the customer now sees the proposed change (old vehicle, new vehicle and the stated reason) inside **My Bookings** and can accept or decline it, optionally with a note. Approval is ownership-checked and nonce-protected, and resolving a request clears its pending approval timeout.
* Booking activity timeline — cancellation and refund requests, modification requests and vehicle changes are now shown as activity on the booking card, so customers can follow the status of every request they raised.
* Step-by-step car setup wizard — the Add/Edit Car screen is now driven as an ordered wizard with a floating bottom bar: Back/Next, a progress ring, a "Step N of N" read-out, a clickable numbered rail and a Publish/Update action on the final step. Forward navigation is gated on required fields; a blocked step highlights the offending fields and names the step holding things up. Save Draft stays ungated.
* New `[mpcrbm_car_list]` shortcode — a standalone vehicle listing with grid/list layouts, paging and an optional left filter sidebar, e.g. `[mpcrbm_car_list mpcrbm_left_filter='yes' style='grid' show='6']`.
* New `[mpcrbm_branch_search]` shortcode — lets visitors find and browse rental branches from any page.
* Multi-Location Fee settings — per-location pickup/drop-off fee configuration now has its own settings module, moved out of Price Settings so location pricing is managed in one place.
* Guideline page — an in-admin reference covering setup steps, shortcodes and their attributes, reachable from the plugin's own sidebar.

**Admin Redesign**
* New admin shell — every plugin screen now renders inside a unified layout: plugin sidebar navigation, screen header, publish/update top bar with live post status, and a consistent card-based content area.
* Branch Management workspace — branches, locations and their taxonomies are consolidated into one screen instead of separate menu items, with a modern list view and inline actions. The sidebar item was renamed from "Locations" to **Branch Management**.
* Branch Managers — creating a branch manager account now happens in a redesigned modal with inline validation, so a user and their branch assignment are created in a single step.
* Extra Services manager — rebuilt with a modern list, search and add/edit flow, and clearer per-service pricing controls.
* Status page — the system status screen was rebuilt with grouped environment cards (WordPress & site, server, PHP limits, uploads, WooCommerce mail configuration) and actionable recommendations instead of a flat table.
* Demo data importer — reworked for reliability and clearer progress feedback while importing sample vehicles, branches and settings.
* Vehicle taxonomies (Car Type, Fuel Type, Seating Capacity, Brand, Make Year, Features) now share the same tabbed management UI as the rest of the plugin.

**Front-end Redesign**
* Car details page rebuilt with a scoped CSS-variable design system and a fully responsive layout — no theme overrides required.
* Car list, branch cards, branch search, registration form and My Bookings restyled to match the new design language.

**Improvements**
* Mobile: the settings tab bar now scrolls horizontally instead of wrapping, the Extra Service Options table scrolls instead of overflowing the page, and a legacy fixed width that broke card panels below 900px was removed.
* The active settings tab is remembered per vehicle across page reloads.
* Toggle switches show a centered loading state and an eased open/close animation while their panel loads.
* The shared FAQ / Terms & Conditions / Related Rental toolbar wraps instead of clipping its actions, and the Related Rental indicator now reflects the real selection count.
* Clearer labels: "Multi-Location Settings" is now "Multi-Location Fee".

**Fixes**
* Fixed a fatal error on add-to-cart when another MagePeople booking plugin was active. The `woocommerce_add_cart_item_data` callback returned `null` on its early exits, which every later callback on that filter received in place of the cart-item array — producing a 500 response and a generic booking failure. Both exits now return the cart item data unchanged.
* Fixed publishing a car silently doing nothing when a required field sat in an inactive (hidden) tab: browsers refuse to submit a form containing an invalid control they cannot focus, so the save failed with no message. All steps are now validated up front and the offending step is opened.

**Security**
* Fixed a PHP Object Injection vulnerability (Patchstack) reachable by users with the Editor role. Serialized post meta is now unserialized with `allowed_classes => false`, so no class is ever instantiated and no `__wakeup()`/`__destruct()` magic method can run. Object placeholders are stripped recursively, which also closes the nested-object bypass. The date/time settings sanitizer and the vehicle-duplication meta copier, previously unpatched, are now covered as well.

= 1.3.9 =
* Admin: Redesigned the Car Rental status dashboard with grouped environment cards and setup recommendations.
* Admin: UI polish — mobile-friendly settings tabs, persisted active tab, toggle loading state and clearer setting labels.

= 1.3.8 =
* Security: Plugin-wide hardening pass (SQLi / XSS / CSRF / access control / file handling audit). Blocked an arbitrary post-meta write in Price Settings by validating the target meta key against an allow-list and requiring `manage_options` plus a nonce; added the missing capability checks to the feature handler and the FAQ / Terms save and delete handlers; added nonce verification to the cart-empty action to close a CSRF hole; and removed an unauthenticated AJAX registration on the extra-service endpoint.

= 1.3.7 =
* Security: Fixed a Broken Access Control vulnerability (Patchstack) in the front-end review handlers. Unauthenticated visitors could edit or delete guest-submitted reviews by supplying a comment ID and the public review nonce. Edit/delete are now restricted to comment moderators and the logged-in review author.

== External Services ==

This plugin utilizes several external services and libraries to provide its functionality. Here's a detailed breakdown of what services are used and how:

Note: All external services are used only when necessary for the functionality requested by the user. No personal data is transmitted without user consent, and all data transmissions are done securely over HTTPS connections.
