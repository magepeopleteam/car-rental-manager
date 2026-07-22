# Car Rental Manager — Comprehensive Plugin Documentation

**Plugin:** Car Rental Manager (`car-rental-manager`)  
**Author:** MagePeople Team (mage-people.com)  
**Architecture:** WordPress + WooCommerce  
**Document Date:** 2026-07-01  

---

## Table of Contents

1. [Plugin Overview](#1-plugin-overview)
2. [Architecture & Components](#2-architecture--components)
3. [Complete Feature Documentation](#3-complete-feature-documentation)
4. [Feature List](#4-feature-list)
5. [Data Model Reference](#5-data-model-reference)
6. [Workflow Analysis](#6-workflow-analysis)
7. [Hooks & Extension API](#7-hooks--extension-api)
8. [Strengths & Weaknesses](#8-strengths--weaknesses)
9. [Suggested New Features](#9-suggested-new-features)
10. [Feature Comparison](#10-feature-comparison)
11. [Improvement Roadmap](#11-improvement-roadmap)
12. [Final Summary](#12-final-summary)

---

## 1. Plugin Overview

### Purpose

Car Rental Manager is a full-featured WordPress plugin that transforms any WooCommerce-powered site into a car rental booking platform. It handles the complete rental lifecycle: vehicle catalogue management, availability checking, date/time-based pricing, online checkout via WooCommerce, booking record keeping, and multi-branch fleet management.

### Problem It Solves

Traditional WooCommerce is built for physical products with no concept of date-range bookings, fleet availability, per-location pricing, car transfers between branches, or multi-tenant branch access. This plugin adds all of that on top of WooCommerce without replacing it, so site owners keep full WooCommerce compatibility (payments, emails, tax, coupons).

### Target Users

| User Type | Role |
|---|---|
| Site Administrator | Full control over all settings, cars, branches, bookings |
| Branch Manager | Sees only their assigned branch's cars and orders |
| Customer | Searches cars, selects dates/locations, books and pays online |
| Developer / Integrator | Extends via actions, filters, and addon plugins |

---

## 2. Architecture & Components

### Directory Structure

```
car-rental-manager/
├── car-rental-manager.php          # Main plugin bootstrap
├── transport_result.php            # Standalone search-results page template
├── admin/
│   ├── MPCRBM_Admin.php            # Admin bootstrapper (loads all admin classes)
│   ├── MPCRBM_CPT.php              # Custom post types & taxonomies registration
│   ├── MPCRBM_Settings.php         # Car settings meta box (11-tab panel)
│   ├── MPCRBM_Taxonomies.php       # Taxonomy manager + main admin dashboard
│   ├── MPCRBM_Branch_Manager.php   # Branch CRUD, car assignment, transfer log
│   ├── MPCRBM_User_Branch_Manager.php  # Branch Manager role + RBAC
│   ├── MPCRBM_Hidden_Product.php   # WC product auto-creation/sync per car
│   ├── MPCRBM_Dummy_Import.php     # Demo data importer
│   ├── MPCRBM_License.php          # License display
│   ├── MPCRBM_Status.php           # System status page
│   ├── MPCRBM_Guideline.php        # Shortcode documentation page
│   ├── MPCRBM_Settings_Global.php  # 54-field global settings panel
│   └── settings/
│       ├── MPCRBM_General_Settings.php
│       ├── MPCRBM_Date_Settings.php
│       ├── MPCRBM_Price_Settings.php
│       ├── MPCRBM_Tax_Settings.php
│       ├── MPCRBM_Extra_Service.php
│       ├── MPCRBM_Extra_Service_Settings.php
│       ├── MPCRBM_Multi_Location_Settings.php
│       ├── MPCRBM_Security_Deposit_Setting.php
│       ├── MPCRBM_Operation_Area_Settings.php
│       ├── MPCRBM_Manage_Feature.php
│       ├── MPCRBM_Gallery_Imges_Settings.php
│       ├── MPCRBM_Faq_Settings.php
│       └── MPCRBM_Term_Condition_Setting.php
├── frontend/
│   ├── MPCRBM_Frontend.php         # Multi-step booking wizard, stock checks
│   ├── MPCRBM_Shortcodes.php       # [mpcrbm_booking], [mpcrbm_car_list]
│   ├── MPCRBM_Transport_Search.php # Search form + AJAX result handlers
│   ├── MPCRBM_Branch_Search.php    # [mpcrbm_branch_search] shortcode
│   ├── MPCRBM_Woocommerce.php      # WC cart/order/checkout integration
│   └── MPCRBM_Manage_Review.php    # Review/rating CRUD via WP comments
├── inc/
│   ├── MPCRBM_Function.php         # Core utility library (static methods)
│   ├── MPCRBM_Dependencies.php     # Asset enqueue management
│   ├── MPCRBM_Layout.php           # Common UI renderers (pagination, selects)
│   ├── MPCRBM_Query.php            # Common WP_Query patterns
│   └── MPCRBM_Woo_Installer.php    # Chunked WooCommerce auto-installer
├── mp_global/
│   ├── MPCRBM_Global_File_Load.php # Global asset loader + inline JS constants
│   └── class/
│       ├── MPCRBM_Global_Function.php  # Shared static utilities + security patches
│       ├── MPCRBM_Custom_Layout.php    # Toggle switches, pagination UI
│       ├── MPCRBM_Custom_Slider.php    # Slider component
│       ├── MPCRBM_Select_Icon_image.php
│       └── MPCRBM_Setting_API.php      # Settings builder/renderer
└── templates/
    ├── car-details/car_details.php     # Single car detail page
    ├── car_list/car_lists.php          # Admin car inventory table
    ├── registration/
    │   ├── registration_layout.php     # Booking wizard wrapper
    │   ├── choose_vehicles.php         # Step 1: vehicle selection
    │   ├── vehicle_item.php            # Car card with pricing
    │   ├── get_details_new.php         # Step 2: booking details
    │   ├── summary.php                 # Summary sidebar
    │   ├── extra_service.php           # Extra services step
    │   ├── extra_service_summary.php   # Extra services summary
    │   └── get_end_place.php           # Return location selector
    └── themes/default.php              # Default car list theme card
```

### Custom Post Types

| CPT Slug | Purpose | Created By |
|---|---|---|
| `mpcrbm_rent` | Vehicle listings | `MPCRBM_CPT` |
| `mpcrbm_booking` | Booking records (mirrors WC orders) | `MPCRBM_Woocommerce` |
| `mpcrbm_service_booking` | Extra service bookings | `MPCRBM_Woocommerce` |
| `mpcrbm_ex_services` | Extra service definitions | `MPCRBM_CPT` |
| `mpcrbm_operate_areas` | Operation area definitions (PRO) | `MPCRBM_CPT` |
| `mpcrbm_dep_refund` | Security deposit/refund records (PRO) | `MPCRBM_CPT` |

### Taxonomies

| Taxonomy | Hierarchical | Purpose |
|---|---|---|
| `mpcrbm_locations` | No | Branches / pickup-dropoff locations |
| `mpcrbm_car_type` | Yes | Car body type (Sedan, SUV, etc.) |
| `mpcrbm_fuel_type` | Yes | Fuel type (Petrol, Diesel, Electric) |
| `mpcrbm_seating_capacity` | Yes | Number of seats |
| `mpcrbm_car_brand` | Yes | Manufacturer |
| `mpcrbm_make_year` | Yes | Model year |
| `mpcrbm_car_feature` | Yes | Features (A/C, GPS, Bluetooth, etc.) |

### Key Options (wp_options)

| Option Key | Purpose |
|---|---|
| `mpcrbm_general_settings` | Payment system, labels, slugs, search form display |
| `mpcrbm_global_settings` | Date format, Gutenberg toggle, booking status |
| `mpcrbm_style_settings` | Theme colours, font sizes, button styles |
| `mpcrbm_faq_list` | Global FAQ entries |
| `mpcrbm_term_condition_list` | Global Terms & Conditions entries |
| `mpcrbm_dummy_already_inserted` | Demo data import flag |
| `mpcrbm_dummy_import_dismissed` | Import modal dismissed flag |

### Class Interaction Map

```
MPCRBM_Plugin
  └── loads MPCRBM_Admin
        ├── MPCRBM_CPT          (registers post types, taxonomies, columns)
        ├── MPCRBM_Settings     (meta box with 11 tabs → delegates to settings/*)
        │     ├── MPCRBM_General_Settings
        │     ├── MPCRBM_Date_Settings
        │     ├── MPCRBM_Price_Settings
        │     ├── MPCRBM_Tax_Settings
        │     ├── MPCRBM_Extra_Service_Settings
        │     ├── MPCRBM_Multi_Location_Settings
        │     ├── MPCRBM_Security_Deposit_Setting
        │     ├── MPCRBM_Operation_Area_Settings
        │     ├── MPCRBM_Manage_Feature
        │     ├── MPCRBM_Gallery_Imges_Settings
        │     ├── MPCRBM_Faq_Settings
        │     └── MPCRBM_Term_Condition_Setting
        ├── MPCRBM_Taxonomies   (admin dashboard + taxonomy AJAX management)
        ├── MPCRBM_Branch_Manager (branch CRUD, car assignment, transfer log)
        ├── MPCRBM_User_Branch_Manager (BM role + RBAC + order filtering)
        ├── MPCRBM_Hidden_Product (WC product auto-sync per car)
        ├── MPCRBM_Settings_Global (54-field global settings)
        ├── MPCRBM_Dummy_Import
        ├── MPCRBM_License
        ├── MPCRBM_Status
        └── MPCRBM_Guideline
  └── loads frontend classes
        ├── MPCRBM_Frontend     (booking wizard, stock availability AJAX)
        ├── MPCRBM_Shortcodes   ([mpcrbm_booking], [mpcrbm_car_list])
        ├── MPCRBM_Transport_Search (search form + AJAX results)
        ├── MPCRBM_Branch_Search ([mpcrbm_branch_search] shortcode)
        ├── MPCRBM_Woocommerce  (cart/checkout/order integration)
        └── MPCRBM_Manage_Review (reviews via WP comments)
  └── loads shared utilities
        ├── MPCRBM_Function     (core utility library)
        ├── MPCRBM_Dependencies (asset enqueue)
        ├── MPCRBM_Layout       (pagination, selects)
        ├── MPCRBM_Query        (common WP_Query patterns)
        └── MPCRBM_Woo_Installer (WC auto-installer)
```

---

## 3. Complete Feature Documentation

### 3.1 Vehicle Management

**How it works:**  
Cars are stored as `mpcrbm_rent` custom post type. Each car has a WooCommerce product automatically created and kept in sync (title, thumbnail, tax class). The product is hidden from the WC catalog — it exists purely for cart/checkout mechanics.

**Admin workflow:**
1. Go to **Car Rental → Add New**
2. Set title, featured image, description
3. Use the 11-tab settings panel to configure everything below

**Tabs in the car settings meta box:**

| Tab | Class | What it configures |
|---|---|---|
| General Info | `MPCRBM_General_Settings` | Taxonomies, passengers, bags, stock, pickup location, driver info toggle |
| Date | `MPCRBM_Date_Settings` | Date type (particular/repeated), available dates, off days, per-day time slots |
| Pricing | `MPCRBM_Price_Settings` | Base price, tiered/day-wise/seasonal discounts, one-way fee |
| Gallery | `MPCRBM_Gallery_Imges_Settings` | Image attachment IDs (multi-image gallery) |
| Extra Service | `MPCRBM_Extra_Service_Settings` | Enable/disable extra services, select service package |
| Tax | `MPCRBM_Tax_Settings` | WooCommerce tax status and tax class |
| Operation Area | `MPCRBM_Operation_Area_Settings` | Restrict car to specific operation areas |
| Multi-Location | `MPCRBM_Multi_Location_Settings` | Enable multi-location, define transfer fees per pickup/dropoff pair |
| FAQ | `MPCRBM_Faq_Settings` | Assign FAQs from global FAQ pool |
| Car Feature | `MPCRBM_Manage_Feature` | Included/excluded features from taxonomy |
| Term & Condition | `MPCRBM_Term_Condition_Setting` | Assign T&C from global pool |
| Security Deposit | `MPCRBM_Security_Deposit_Setting` | Enable deposit, set fixed/percentage amount |
| Branch Assignment | `MPCRBM_Branch_Manager` (PRO) | Assign home/current branch, view transfer history |

---

### 3.2 Date & Time Availability

**Two availability modes:**

**Particular Dates** — admin manually selects specific available dates using a date picker. The car is only bookable on those exact dates.

**Repeated Dates** — admin sets a start date and a repeat interval, and the system generates availability. Off-days (by weekday) and off-dates (specific dates) can be excluded.

**Per-day time slots:**  
Each weekday (Mon–Sun) can have its own open and close time. The admin sets default start/end times and can override per day.

**Stock management:**  
`mpcrbm_car_stock` stores how many physical units exist. The system queries all active bookings overlapping a date and subtracts from stock to determine real-time availability. Dates where all units are booked appear as unavailable.

---

### 3.3 Pricing System

The pricing engine supports four layered discount/surcharge types that stack:

**Base Price (`mpcrbm_day_price`)**  
The per-day rate used as the baseline for all calculations.

**Tiered Duration Discounts (`mpcrbm_tiered_discounts`)**  
Rules triggered by number of booking days. Each rule has:
- `min` / `max` days
- `type`: percentage off, fixed discount, fixed price, or override day price
- Calculated value

**Day-Wise Pricing (`mpcrbm_daywise_pricing`)**  
Different price multipliers per day of week (Mon–Sun). Useful for weekend surcharges.

**Seasonal Pricing (`mpcrbm_seasonal_pricing`)**  
Date-range overrides with a name, start date, end date, and a value (percentage or fixed).

**One-Way Fee**  
If the customer picks up from branch A and drops off at branch B, a configurable `mpcrbm_car_one_way_fee` is added. Fee can be fixed or percentage.

**Branch Price Multiplier** *(meta exists, UI not yet implemented)*  
`mpcrbm_branch_multiplier` on the branch term. Designed to multiply base price per pickup branch.

**Pricing formula applied in `vehicle_item.php`:**
```
subtotal = base_day_price × number_of_days
subtotal = apply(tiered_discounts, subtotal)
subtotal = apply(seasonal_pricing, subtotal)
subtotal = apply(daywise_pricing, subtotal)
total    = subtotal + one_way_fee + extra_services + security_deposit
```

---

### 3.4 Search & Booking Flow

**Shortcode:** `[mpcrbm_booking form='horizontal|inline' progressbar='yes|no']`

**Step 1 — Search form:**  
Customer selects pickup location, return location (optional), pickup date/time, return date/time. Form is rendered by `MPCRBM_Transport_Search::transport_search()`.

**Step 2 — Vehicle selection:**  
AJAX call to `mpcrbm_get_map_search_result` returns a filtered list of available cars. Results are stored in PHP session and the customer is redirected to a results page (or shown inline). Each car card shows price, availability badge, and "Book Now" button.

**Step 3 — Extra services:**  
If the car has extra services configured, the customer sees a form to add optional services (e.g., child seat, GPS, insurance).

**Step 4 — Cart & Checkout:**  
Clicking "Book Now" calls `mpcrbm_add_to_cart` AJAX which validates stock and adds the WooCommerce product to cart with all booking meta attached as cart item data.

**Step 5 — WooCommerce Checkout:**  
Standard WC checkout. On order creation, `MPCRBM_Woocommerce::checkout_order_processed()` creates a `mpcrbm_booking` CPT post mirroring the order, storing all rental-specific fields.

**Step 6 — Order Status Sync:**  
When WC order status changes (e.g., from Processing to Completed), `order_status_changed()` updates the corresponding `mpcrbm_booking` post status.

---

### 3.5 Branch Management System (PRO)

**Branch creation:**  
Branches are `mpcrbm_locations` taxonomy terms enriched with term meta: address, phone, and a 7-day operating hours schedule.

**Car-to-branch assignment:**  
Each car has a `mpcrbm_home_branch` (registered branch) and `mpcrbm_current_branch` (physical location). Current branch changes automatically on one-way rentals.

**Branch dashboard:**  
Accessible from the main admin panel (taxonomy tab). Shows all branches in a responsive grid with car counts, address, phone, and action buttons to view/transfer cars.

**Car transfer:**  
Admin can move a car from one branch to another from the dashboard. Every transfer is logged in `mpcrbm_branch_transfer_log` (capped at 50 entries). Log entries contain: date, from, to, reason, user ID.

**Branch search shortcode:**  
`[mpcrbm_branch_search]` renders a frontend component showing pickup/dropoff location selectors, a Flatpickr date range picker, and a branch info card (address, phone, hours) that updates on location selection.

---

### 3.6 Branch Manager Role System (PRO)

**Role:** `mpcrbm_branch_manager`

**Capabilities granted to Branch Managers:**
- All standard WordPress admin navigation is accessible
- Within the plugin: can only see and edit cars in their assigned branch(es)
- Cannot edit cars, branches, or bookings belonging to other branches
- Inactive accounts are blocked at login via the `authenticate` filter

**How data isolation works:**
- `pre_get_posts` filter restricts the car CPT list to their branches
- `terms_clauses` filter restricts the locations taxonomy list
- `user_has_cap` filter dynamically grants/revokes edit caps per car
- `current_screen` action blocks term edit pages for foreign branches

**Branch Manager pages:**
- **My Branch** — dashboard showing branch stats, address, phone, car inventory table
- **Bookings** — filterable table of WooCommerce orders for their branches

**Order filtering mechanism:**  
At checkout, `tag_order_with_branch()` saves `_mpcrbm_order_branch` to the order. The bookings page queries orders by this meta via `wc_get_orders()`. A fallback checks older orders via order item meta `_mpcrbm_start_place` (PHP loop over untagged orders).

---

### 3.7 Extra Services

**Global services** are defined as `mpcrbm_ex_services` CPT posts. Each service has: icon, name, description, base price, and quantity type (per-booking or per-day).

**Per-car assignment:** Each car can enable extra services and select which service package applies.

**Checkout behaviour:** Extra service selections are serialized into cart item meta (`mpcrbm_extra_service_info`) and persisted into order item meta and `mpcrbm_service_booking` CPT posts.

---

### 3.8 Review System

Customer reviews are stored as WordPress comments on the `mpcrbm_rent` post, with a `mpcrbm_review_rating` (1–5) comment meta field. CRUD operations are handled via AJAX (save, edit, delete). Edit and delete are restricted to the comment author or an administrator.

---

### 3.9 Security Deposit

Configurable per car: enable, type (fixed or percentage of base price), amount. The deposit is added to the cart total and stored in booking meta. Refund management for deposits is a PRO feature (`mpcrbm_dep_refund` CPT).

---

### 3.10 WooCommerce Auto-Installer

If WooCommerce is not detected on activation, a modal popup offers to automatically download, extract, and activate WooCommerce. The download uses HTTP Range headers in 1 MB chunks (stored in `wp-content/uploads/mpcrbm-installer/`) to avoid server timeouts. State is tracked via transient `mpcrbm_dl_woocommerce`.

---

### 3.11 Demo Data Importer

On first install, a modal offers to import sample cars, locations, and bookings. Controlled by `mpcrbm_dummy_already_inserted` and `mpcrbm_dummy_import_dismissed` options. Import is AJAX-driven.

---

### 3.12 Global Settings (54 fields)

Organised into sections:

| Section | Key fields |
|---|---|
| General | Payment system, direct booking, label overrides |
| Search Form | Form layout (horizontal/inline), which fields to show |
| Date & Time | Date format, time format, 12/24hr toggle |
| Booking | Default order status, notification behaviour |
| Style | Primary colour, button colour, font sizes |
| License | License key area for addons |

---

### 3.13 Car List Shortcode

`[mpcrbm_car_list]` — Displays a filterable, paginated list of cars with:
- Grid / list view toggle
- Left-sidebar filters (car type, fuel type, brand, etc.)
- Load-more or numeric pagination
- Configurable columns (1–6)
- Category filtering by taxonomy term

---

### 3.14 System Status Page

Displays: WordPress version, WooCommerce status and version, WC email sender name/address, tax enabled flag. Useful for support debugging.

---

## 4. Feature List

### Core Features (Free)

- [x] Vehicle CPT with full media support
- [x] 7 taxonomies for car classification
- [x] Date-range availability engine (particular + repeated modes)
- [x] Per-day time slot scheduling
- [x] Off-days and off-dates
- [x] Day-wise pricing (per weekday)
- [x] Seasonal pricing (date-range overrides)
- [x] Tiered/duration-based discounts
- [x] One-way fee support
- [x] Security deposit (fixed or %)
- [x] Extra services (per-booking or per-day)
- [x] Image gallery per car
- [x] Car features (include/exclude)
- [x] Per-car FAQ assignment
- [x] Per-car Terms & Conditions
- [x] WooCommerce cart & checkout integration
- [x] Hidden WC product auto-sync per car
- [x] Multi-stock support (fleet quantity)
- [x] Real-time stock availability (AJAX)
- [x] Customer review & rating system (1–5 stars)
- [x] `[mpcrbm_booking]` search shortcode (horizontal / inline)
- [x] `[mpcrbm_car_list]` listing shortcode
- [x] Left-sidebar car filter UI
- [x] Multi-step booking wizard with progress bar
- [x] WooCommerce tax class integration
- [x] Order status sync to booking CPT
- [x] Global settings panel (54 fields)
- [x] Admin taxonomy management dashboard (AJAX CRUD)
- [x] Car duplication
- [x] Bulk car delete
- [x] System status page
- [x] Shortcode guideline page
- [x] Demo data importer
- [x] WooCommerce auto-installer (chunked download)
- [x] WPML / Polylang language compatibility

### PRO Features

- [x] Branch Manager (branch CRUD, car assignment, transfer log, dashboard)
- [x] `[mpcrbm_branch_search]` shortcode with info card
- [x] Branch Manager user role with full RBAC
- [x] My Branch dashboard for managers
- [x] Bookings page scoped to branch
- [x] Branch order tagging and filtering
- [x] Multi-location transfer fees
- [x] Operation areas (geo-restricted cars)
- [x] Security deposit refund management
- [ ] Branch price multiplier (meta exists, UI not yet implemented)

### Addon-Dependent Features

- [ ] Google Calendar integration for driver/customer events (`MPCRBM_Plugin_Ecab_Calendar_Addon`)
- [ ] Geo-fence/distance-based pricing (session-based hooks exist, requires map addon)

---

## 5. Data Model Reference

### Car Post Meta Keys

| Meta Key | Type | Purpose |
|---|---|---|
| `mpcrbm_maximum_passenger` | int | Max passengers |
| `mpcrbm_maximum_bag` | int | Max bags |
| `mpcrbm_car_stock` | int | Fleet quantity |
| `mpcrbm_set_pickup_location` | string | Default pickup location slug |
| `mpcrbm_minimum_booking_period` | int | Min hours before booking |
| `mpcrbm_enable_driver_information` | string | on/off |
| `mpcrbm_driver_info` | array | name, phone, email, age |
| `mpcrbm_date_type` | string | particular / repeated |
| `mpcrbm_particular_dates` | array | explicit available dates |
| `mpcrbm_available_for_all_time` | string | on/off |
| `mpcrbm_repeated_start_date` | string | Repeat start date |
| `mpcrbm_repeated_after` | int | Repeat interval (days) |
| `mpcrbm_active_days` | array | Active weekdays |
| `mpcrbm_off_days` | array | Weekdays closed |
| `mpcrbm_off_dates` | array | Specific closed dates |
| `mpcrbm_default_start_time` | string | Default open time HH:MM |
| `mpcrbm_default_end_time` | string | Default close time HH:MM |
| `mpcrbm_[day]_start_time` | string | Per-day open time (mon–sun) |
| `mpcrbm_[day]_end_time` | string | Per-day close time |
| `mpcrbm_day_price` | float | Base price per day |
| `mpcrbm_price_based` | string | manual / km / hour |
| `mpcrbm_car_one_way_enabled` | string | on/off |
| `mpcrbm_car_one_way_fee` | float | One-way fee amount |
| `mpcrbm_car_one_way_fee_type` | string | fixed / percentage |
| `mpcrbm_enable_tired_discount` | string | on/off |
| `mpcrbm_tiered_discounts` | array | Duration discount rules |
| `mpcrbm_enable_day_wise_discount` | string | on/off |
| `mpcrbm_daywise_pricing` | array | Per-weekday price override |
| `mpcrbm_enable_seasonal_discount` | string | on/off |
| `mpcrbm_seasonal_pricing` | array | Date-range price rules |
| `_tax_status` | string | taxable / shipping / none |
| `_tax_class` | string | WC tax class |
| `display_mpcrbm_extra_services` | string | on/off |
| `mpcrbm_extra_services_id` | int | Extra service post ID |
| `mpcrbm_multi_location_enabled` | string | on/off |
| `mpcrbm_location_prices` | array | pickup/dropoff/transfer_fee |
| `mpcrbm_security_deposit_enable` | string | on/off |
| `mpcrbm_security_deposit` | float | Deposit amount |
| `mpcrbm_security_deposit_type` | string | fixed / percentage |
| `mpcrbm_gallery_images` | array | Attachment IDs |
| `mpcrbm_added_faq` | array | FAQ keys |
| `mpcrbm_term_condition_list` | array | T&C keys |
| `mpcrbm_include_features` | array | Feature term IDs |
| `mpcrbm_exclude_features` | array | Feature term IDs |
| `mpcrbm_home_branch` | string | Branch slug (PRO) |
| `mpcrbm_current_branch` | string | Current branch slug (PRO) |
| `mpcrbm_branch_enabled` | string | 0/1 (PRO) |
| `mpcrbm_branch_transfer_log` | array | Transfer audit entries (PRO) |
| `link_wc_product` | int | Linked WC product ID |

### Booking Post Meta Keys (`mpcrbm_booking`)

| Meta Key | Purpose |
|---|---|
| `mpcrbm_id` | Car post ID |
| `mpcrbm_date` | Pickup datetime |
| `return_date_time` | Return datetime |
| `mpcrbm_start_place` | Pickup location slug |
| `mpcrbm_end_place` | Dropoff location slug |
| `mpcrbm_car_quantity` | Units booked |
| `mpcrbm_tp` | Total price |
| `mpcrbm_base_price` | Base price at booking time |
| `mpcrbm_order_status` | Current order status |
| `mpcrbm_security_deposit` | Deposit charged |
| `mpcrbm_branch_one_way_fee` | One-way fee charged |
| `mpcrbm_extra_service_info` | Extra services selected |

### Branch Term Meta Keys (`mpcrbm_locations`)

| Meta Key | Purpose |
|---|---|
| `mpcrbm_branch_address` | Street address |
| `mpcrbm_branch_phone` | Phone number |
| `mpcrbm_branch_hours` | Array: {mon..sun: {open, close, closed}} |
| `mpcrbm_branch_multiplier` | Price multiplier (reserved, UI pending) |
| `mpcrbm_branch_one_way_fee` | Drop-off fee (reserved, UI pending) |

### Branch Manager User Meta

| Meta Key | Purpose |
|---|---|
| `mpcrbm_managed_branches` | Array of assigned branch slugs |
| `mpcrbm_bm_status` | 'active' or 'inactive' |

---

## 6. Workflow Analysis

### Installation Workflow

```
1. Upload & activate plugin
   └── On activation: check WooCommerce
       ├── WC present → create booking pages, register post types, flush rewrite rules
       └── WC absent  → show auto-installer modal (chunked download)

2. Import demo data (optional modal)
   └── Creates sample cars, locations, bookings

3. Configure global settings
   └── Car Rental → Global Settings (54 fields)

4. Create branches (Car Rental → Locations taxonomy)
   └── Add address, phone, operating hours per branch

5. Add cars (Car Rental → Add New)
   └── Fill 11-tab meta box per car
```

### Customer Booking Workflow

```
[Search Form]
  ↓ Customer fills: pickup, return location, dates/times
  ↓ AJAX: mpcrbm_get_map_search_result

[Vehicle Selection]
  ↓ System: filter by branch, date availability, stock
  ↓ Customer: picks car, sees price breakdown
  ↓ AJAX: mpcrbm_get_total_count_price_selected_car

[Extra Services] (optional)
  ↓ Customer: selects add-ons (child seat, GPS, etc.)
  ↓ AJAX: mpcrbm_get_extra_service_summary

[Cart]
  ↓ AJAX: mpcrbm_add_to_cart
  ↓ WC cart item created with all booking meta

[WooCommerce Checkout]
  ↓ Customer: fills billing details, selects payment
  ↓ Order created → checkout_order_processed() fires
  ↓ mpcrbm_booking CPT created as mirror

[Order Status Changes]
  ↓ order_status_changed() syncs to mpcrbm_booking post status
  ↓ If car returned to different branch → current_branch updated
```

### Branch Manager Daily Workflow

```
[Login]
  └── Inactive check at authenticate filter

[My Branch page]
  └── Stats: total cars, address, one-way fee
  └── Cars table: status, home/current branch, edit link

[Bookings page]
  └── Filtered WC orders for assigned branch(es)
  └── Table: order #, customer, dates, status, total, pickup/dropoff, car

[Transfer car]
  └── Via Branch Dashboard (admin only)
  └── AJAX: mpcrbm_transfer_car_branch
  └── Updates mpcrbm_current_branch
  └── Appends entry to mpcrbm_branch_transfer_log
```

---

## 7. Hooks & Extension API

### Action Hooks (for addons)

| Hook | When fires | Args |
|---|---|---|
| `mpcrbm_transport_search` | Render search form | shortcode attributes |
| `mpcrbm_left_side_car_filter` | Render left sidebar filter | car list array |
| `mpcrbm_settings_tab_navigation` | Add tab to car meta box nav | post ID |
| `mpcrbm_settings_tab_content` | Add tab panel content | post ID |
| `mpcrbm_extra_service_item` | Render a service row | service data |
| `mpcrbm_before_cart_item_display` | Before cart booking summary | cart item |
| `mpcrbm_after_cart_item_display` | After cart booking summary | cart item |
| `mpcrbm_checkout_order_processed` | After booking CPT created | order ID, booking ID |
| `mpcrbm_add_booking_data` | Modify booking data before save | booking data array |
| `mpcrbm_licence_section` | Add license rows | — |
| `mpcrbm_addon_list` | Add addon list items | — |

### Filter Hooks

| Hook | Purpose | Return |
|---|---|---|
| `mpcrbm_add_cart_item` | Modify cart item data | modified cart item array |
| `mpcrbm_validate_cart_item` | Custom cart validation | WP_Error or true |
| `mpcrbm_total_price` | Override calculated price | float |
| `mpcrbm_settings_sec_reg` | Add settings sections | sections array |
| `mpcrbm_get_car_data` | Modify car query args | WP_Query args |

### Key AJAX Actions

| Action | Auth | Class | Purpose |
|---|---|---|---|
| `mpcrbm_get_map_search_result` | Public | Transport_Search | Inline search results |
| `mpcrbm_get_map_search_result_redirect` | Public | Transport_Search | Session + redirect results |
| `mpcrbm_get_end_place` | Public | Transport_Search | Return location form |
| `mpcrbm_get_extra_service` | Public | Transport_Search | Extra services form |
| `mpcrbm_get_extra_service_summary` | Public | Transport_Search | Extra services summary |
| `mpcrbm_get_dropoff_locations` | Public | Transport_Search | Multi-location dropoffs |
| `mpcrbm_add_to_cart` | Public | Woocommerce | Add rental to WC cart |
| `mpcrbm_get_total_count_price_selected_car` | Public | Frontend | Price + stock check |
| `mpcrbm_get_car_qty_by_date` | Public | Frontend | Available stock HTML |
| `mpcrbm_get_ex_service` | Public | Extra_Service | Load service data |
| `mpcrbm_get_branch_info` | Public | Branch_Manager | Branch address/hours |
| `mpcrbm_transfer_car_branch` | Admin | Branch_Manager | Move car to branch |
| `mpcrbm_get_branch_cars` | Admin | Branch_Manager | Cars at branch HTML |
| `mpcrbm_render_branch_dashboard` | Admin | Branch_Manager | Lazy-load dashboard |
| `mpcrbm_load_taxonomies` | Admin | Taxonomies | Taxonomy CRUD list |
| `mpcrbm_save_taxonomy` | Admin | Taxonomies | Create term |
| `mpcrbm_update_taxonomy` | Admin | Taxonomies | Update term |
| `mpcrbm_delete_taxonomy` | Admin | Taxonomies | Delete term |
| `mpcrbm_delete_multiple_cars` | Admin | Taxonomies | Bulk trash cars |
| `mpcrbm_update_feature_meta` | Admin | Manage_Feature | Toggle feature on car |
| `mpcrbm_add_price_discount_rules` | Admin | Price_Settings | Toggle discount type |
| `mpcrbm_save_added_faq` | Admin | Faq_Settings | Assign FAQs to car |
| `mpcrbm_save_added_term_condition` | Admin | Term_Condition | Assign T&C to car |
| `mpcrbm_review_save` | Public | Manage_Review | Submit review |
| `mpcrbm_review_edit` | Public | Manage_Review | Edit own review |
| `mpcrbm_review_delete` | Public | Manage_Review | Delete own review |
| `mpcrbm_woo_download_chunk` | Admin | Woo_Installer | Download WC chunk |
| `mpcrbm_woo_install` | Admin | Woo_Installer | Extract WC ZIP |
| `mpcrbm_woo_activate` | Admin | Woo_Installer | Activate WooCommerce |
| `mpcrbm_import_dummy_data` | Admin | Dummy_Import | Import demo data |

---

## 8. Strengths & Weaknesses

### Strengths

**Architecture**
- Clean separation of concerns: each feature is a dedicated class
- Consistent use of WordPress Settings API, nonces, and `sanitize_*` functions
- Hook-based architecture makes addons easy to build without core modification
- WooCommerce integration is deep but non-destructive (hidden products, order sync)
- HPOS-compatible order queries via `wc_get_orders()`

**Feature Depth**
- Four distinct pricing layers (tiered, day-wise, seasonal, one-way) give operators granular revenue control
- Transfer audit log with 50-entry rolling history is a strong operational tool
- Chunked WC auto-installer is a good UX for non-technical users
- Branch Manager RBAC is well-thought-out: navigation unrestricted, data isolated at query level
- Stock checking is date-range aware (not just quantity-aware)

**Security**
- Nonce verification on every AJAX endpoint
- `wp_unslash()` + `sanitize_*` consistently applied
- `mpcrbm_day_price` serialization guard in `MPCRBM_Global_Function::get_post_info()` (explicit PHP Object Injection prevention)
- Capability checks before all destructive actions
- Inactive BM blocked at `authenticate` filter (before session is established)

---

### Weaknesses

**Incomplete Implementations**
- `mpcrbm_branch_multiplier` and `mpcrbm_branch_one_way_fee` term meta keys are registered and read, but there is **no admin UI** to set them. Admins cannot use branch-level pricing multipliers through the interface.
- Multi-location pricing (`mpcrbm_location_prices`) has an admin UI but the frontend price calculation does not yet fully integrate it in all code paths.
- Geo-fence/distance pricing relies on session transients and external addon hooks — the core doesn't ship with a map distance calculator.

**Performance Concerns**
- `get_branch_order_ids()` fallback: fetches all orders without `_mpcrbm_order_branch` and loops through items in PHP. On a site with thousands of orders, this causes a performance hit.
- `get_cars_at_branch()` uses a three-condition `meta_query` with OR which can be slow at scale without a composite index.
- Admin dashboard analytics (`mpcrbm_get_current_rented_cars_count`) uses a post meta date range query with no caching.
- Taxonomy names are saved as post meta on every `save_post` (redundant denormalisation not always flushed correctly).

**Code Quality**
- `MPCRBM_Extra_Service.php` and `MPCRBM_Extra_Service_Settings.php` have overlapping responsibilities (both can save `display_mpcrbm_extra_services` and `mpcrbm_extra_services_id`).
- `MPCRBM_Taxonomies.php` references old CPT names (`crm_extra_services`) in comments — stale documentation.
- Some commented-out code remains in `MPCRBM_Price_Settings.php` from older discount methods.
- `transport_booking` CPT appears referenced as a legacy type but no registration code is visible.
- `MPCRBM_Gallery_Imges_Settings.php` — typo in class file name (`Imges` instead of `Images`).

**Usability**
- Branch multiplier and branch one-way fee are invisible to admins (UI gap).
- No UI for reviewing or exporting transfer logs in bulk.
- Customer has no "My Bookings" frontend account page built-in.
- No email notification customiser in the UI — uses WooCommerce default emails.
- Demo data importer has no "undo/remove demo data" option.

**Security (minor)**
- `mpcrbm_review_delete` and `mpcrbm_review_edit` are nopriv AJAX endpoints — they do check comment authorship, but nonce verification uses a single global nonce (`mpcrbm_review_nonce_action`) not tied to a specific review ID, making it marginally weaker.
- `filter_branch_terms_sql` uses `$wpdb->prepare()` inline in a `terms_clauses` filter — correct, but direct SQL modification.

---

## 9. Suggested New Features

### High Priority

| Feature | Why Valuable | Complexity |
|---|---|---|
| **Branch multiplier & one-way fee UI** | Meta keys already exist, logic is built — only the admin UI is missing. Completing it unlocks a core PRO pricing scenario. | Easy |
| **Customer "My Bookings" frontend page** | Customers have no self-service view of their bookings. Reduces support enquiries. Standard expectation for rental sites. | Medium |
| **Transfer log export (CSV)** | Fleet managers need audit trails for insurance and compliance. Currently only visible per-car in the editor. | Easy |
| **Order tagging backfill command** | One-time admin tool to tag all existing orders with `_mpcrbm_order_branch`. Eliminates the slow PHP fallback loop permanently. | Easy |
| **Booking modification / amendment** | Allow customers or admins to change dates/car on an existing booking. Currently requires cancel + rebook. | Hard |

### Medium Priority

| Feature | Why Valuable | Complexity |
|---|---|---|
| **Email notification templates** | Custom booking confirmation, reminder, and cancellation emails configurable in admin. Currently relies entirely on WC default emails. | Medium |
| **iCal / calendar export** | Let customers add bookings to Google Calendar / Apple Calendar. A basic ICS file generation would suffice. | Easy |
| **Admin booking calendar view** | Visual calendar showing all active bookings per car. Aids dispatch and reduces double-booking errors. | Medium |
| **Cancellation & refund workflow UI** | Semi-automated: customer requests cancellation → admin approves → deposit refund triggered. Currently manual. | Medium |
| **Availability widget / REST endpoint** | Public REST API endpoint for car availability to power mobile apps or third-party integrations. | Medium |
| **Waitlist / enquiry mode** | When a car is fully booked, offer customers a waitlist form. Notify them if a slot opens. | Medium |
| **Driver assignment** | Assign a driver user to a booking; notify driver by email; driver sees their schedule. | Medium |

### Nice-to-Have

| Feature | Why Valuable | Complexity |
|---|---|---|
| **Undo demo data import** | Reduces risk for admins who imported demo data by mistake. | Easy |
| **Bulk price update tool** | Change base price for multiple cars at once via admin list table. | Easy |
| **Car comparison page** | Side-by-side feature/price comparison for 2–4 cars. Common on rental sites. | Medium |
| **Multilingual admin labels** | Currently i18n-ready, but a language switcher in global settings would help non-English admins. | Easy |
| **Maintenance/out-of-service flag** | Mark a car as under maintenance without trashing it. Exclude from search but keep in admin. | Easy |
| **Analytics dashboard** | Revenue charts, most-booked cars, peak periods, branch performance. | Hard |
| **Dynamic pricing rules per branch** | Combined with multiplier UI: allow different rates at airport vs. downtown locations. | Medium |

---

## 10. Feature Comparison

Compared against commonly available features in competing WordPress car rental plugins:

| Feature | This Plugin | Industry Standard |
|---|---|---|
| WooCommerce payment processing | ✅ | ✅ |
| Date-range availability | ✅ | ✅ |
| Multi-fleet stock management | ✅ | ✅ |
| Extra/add-on services | ✅ | ✅ |
| Tiered pricing | ✅ | ✅ |
| Seasonal pricing | ✅ | ✅ |
| One-way rentals | ✅ | ✅ |
| Customer review system | ✅ | ✅ |
| Shortcode-based integration | ✅ | ✅ |
| Car gallery | ✅ | ✅ |
| Security deposit | ✅ | Partial |
| Multi-branch management | ✅ (PRO) | Rare |
| Branch manager role & RBAC | ✅ (PRO) | Very rare |
| Car transfer audit log | ✅ (PRO) | Very rare |
| Branch operating hours | ✅ (PRO) | Rare |
| WC product auto-sync | ✅ | Rare |
| Customer "My Bookings" page | ❌ | ✅ |
| Booking modification | ❌ | Partial in some |
| Admin booking calendar | ❌ | ✅ in premium |
| Custom email templates | ❌ (relies on WC) | ✅ in premium |
| iCal / calendar export | ❌ | Partial |
| Driver assignment | ❌ | In some |
| REST API | ❌ | In modern plugins |
| Damage inspection / condition report | ❌ | In enterprise |
| Contract / PDF generation | ❌ | In premium |
| Dynamic pricing (demand-based) | ❌ | In premium |

---

## 11. Improvement Roadmap

### Version 1.1 — Polish & Close Gaps

**Goal:** Complete existing half-implemented features and fix known issues.

- [ ] Implement admin UI for `mpcrbm_branch_multiplier` and `mpcrbm_branch_one_way_fee` on the branch edit term page
- [ ] Add one-time admin tool to backfill `_mpcrbm_order_branch` on all existing orders (eliminates PHP fallback loop)
- [ ] Fix `MPCRBM_Extra_Service.php` / `MPCRBM_Extra_Service_Settings.php` responsibility overlap
- [ ] Remove stale commented-out code from `MPCRBM_Price_Settings.php`
- [ ] Rename `MPCRBM_Gallery_Imges_Settings.php` → `MPCRBM_Gallery_Images_Settings.php`
- [ ] Add transfer log export (CSV download) on the branch dashboard
- [ ] Cache admin dashboard analytics (mpcrbm_current_rented_cars_count) with a 5-minute transient
- [ ] Add "Remove demo data" option to the import screen
- [ ] Add per-review nonce for edit/delete AJAX endpoints

### Version 1.2 — Customer Experience

**Goal:** Add customer-facing self-service features.

- [ ] **My Bookings** frontend shortcode — table of past/upcoming bookings, each with status and booking details
- [ ] **Booking cancellation request** UI — customer initiates, admin approves; deposit refund triggered
- [ ] **iCal export** — button on order confirmation and My Bookings page
- [ ] **Maintenance flag** on cars — exclude from search without trashing
- [ ] **Email notification templates** — admin-configurable HTML emails for confirmation, reminder (24h before), and cancellation
- [ ] **Car comparison** shortcode — side-by-side table for 2–4 cars

### Version 2.0 — Platform & API

**Goal:** Open the plugin to external integrations and power-user workflows.

- [ ] **REST API** — public endpoints for car availability, pricing, booking creation (for mobile apps and channel partners)
- [ ] **Admin booking calendar** — visual month/week view of all bookings per car or per branch
- [ ] **Driver assignment** — assign WP users (driver role) to bookings, email notification, driver schedule page
- [ ] **Dynamic pricing engine** — demand-based price adjustment (configure min/max multiplier and occupancy thresholds)
- [ ] **Analytics dashboard** — revenue by period, cars, branches; booking conversion funnel; peak hour analysis
- [ ] **Contract / PDF generation** — downloadable rental agreement with booking details and T&C
- [ ] **Damage inspection module** — checklist form at pickup/return, photo upload, linked to booking record

---

## 12. Final Summary

### What This Plugin Delivers

Car Rental Manager is a **mature, full-stack WordPress car rental solution** built on WooCommerce. It handles everything from vehicle catalogue management through the complete booking and payment lifecycle. The PRO branch system — with RBAC, car transfers, audit logs, and operating hours — is notably more sophisticated than anything in the free tier of competing plugins.

### All Implemented Features

✅ Vehicle CPT + 7 taxonomy classification system  
✅ 11-tab per-car settings panel  
✅ Date availability (particular + repeated modes)  
✅ Per-day time slot scheduling with off-days/dates  
✅ 4-layer pricing: tiered, day-wise, seasonal, one-way  
✅ Security deposit (fixed or %)  
✅ Extra services (global pool, per-booking or per-day)  
✅ Multi-stock fleet management with real-time availability  
✅ Image gallery per car  
✅ Car features (include/exclude), FAQ, Terms & Conditions  
✅ WooCommerce cart + checkout + order sync  
✅ Hidden WC product auto-created and synced per car  
✅ Customer review & 1–5 star rating system  
✅ `[mpcrbm_booking]`, `[mpcrbm_car_list]`, `[mpcrbm_branch_search]` shortcodes  
✅ Multi-step booking wizard with progress bar  
✅ Left-sidebar car filter  
✅ 54-field global settings panel  
✅ Admin taxonomy CRUD dashboard (AJAX)  
✅ Car duplication + bulk delete  
✅ System status + shortcode guideline pages  
✅ Demo data importer  
✅ WC auto-installer (chunked download)  
✅ Branch CRUD with address, phone, operating hours (PRO)  
✅ Car-to-branch assignment with home/current branch (PRO)  
✅ Car transfer with 50-entry audit log (PRO)  
✅ Branch dashboard (grid view, lazy-loaded cars panel) (PRO)  
✅ `mpcrbm_branch_manager` role + full RBAC data isolation (PRO)  
✅ Branch Manager admin pages: manage BMs, My Branch, Bookings (PRO)  
✅ Branch order tagging + filtered booking view (PRO)  
✅ WPML / Polylang compatibility  
✅ Google Calendar addon integration hooks  

### Features That Are Missing or Incomplete

❌ Branch multiplier / branch one-way fee admin UI (meta exists, UI absent)  
❌ Customer "My Bookings" frontend page  
❌ Booking modification / amendment by customer or admin  
❌ Admin booking calendar view  
❌ Custom email notification templates  
❌ iCal / calendar export for customers  
❌ Booking cancellation + refund workflow UI  
❌ Driver assignment to bookings  
❌ REST API for availability/booking  
❌ Order backfill tool (removes performance bottleneck in BM bookings)  
❌ "Remove demo data" undo option  
❌ Analytics / reporting dashboard  
❌ PDF contract generation  
❌ Damage inspection / condition report module  
❌ Dynamic / demand-based pricing  

### Actionable Recommendations

1. **Immediate:** Ship the branch multiplier/fee UI — the backend is complete, this is a 1-day frontend task with high PRO value.
2. **Short-term:** Add the order backfill tool to eliminate the PHP loop over all untagged orders — this is a performance fix that costs almost nothing to build.
3. **Next minor release:** Build "My Bookings" frontend page — the most common customer request for any booking plugin.
4. **Next major release:** REST API + admin calendar — these unlock the plugin as a platform for mobile apps and channel integration, repositioning it from plugin to SaaS infrastructure.
