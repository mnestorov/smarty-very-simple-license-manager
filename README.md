# SM - Very Simple License Manager

[![Licence](https://img.shields.io/badge/LICENSE-GPL2.0+-blue)](./LICENSE)

- **Developed by:** Martin Nestorov 
    - Explore more at [nestorov.dev](https://github.com/mnestorov)
- **Plugin URI:** https://github.com/mnestorov/smarty-very-simple-license-manager

## Overview

The Very Simple License Manager by Smarty Studio is a WordPress plugin designed for managing software licenses. With this plugin, you can create, track, and control licenses for various products, ensuring that only authorized users can access your software.

## Features

- **License Management:** Create, activate, deactivate, and track software licenses.
- **API Key Support:** Issue and authenticate API keys for client applications.
- **Custom Post Types:** Uses a custom post type for organizing licenses.
- **REST API Endpoint:** Provides an API endpoint to check the license status.
- **Daily Expiration** Check: Automatically checks and updates the status of expired licenses.
- **Product Taxonomy:** Categorize licenses based on associated products.

## Installation

1. Download and install the plugin from GitHub.
2. Activate the plugin through the WordPress admin panel.
2. Configure the Consumer Key and Consumer Secret in the plugin settings for secure API access.

## Usage

After activating the plugin, use the License Manager settings in the WordPress admin to manage API keys for authentication. Licenses can be added under the "Licenses" post type. Use the custom columns in the admin list to view license details at a glance.

## API Usage

The plugin provides an API endpoint to validate licenses. To access it, you must authenticate using your Consumer Key and Consumer Secret. 

**Endpoint Format**

```bash
GET https://yourwebsite.com/wp-json/smarty-vslm/v1/check-license
```

**Parameters**

| Parameter       |   Type   | Description                                      |
|-----------------|----------|--------------------------------------------------|
| `license_key`   |  string  | The license key to check.                        |
| `site_url`      |  string  | URL of the site where the license is used.       |
| `wp_version`    |  string  | WordPress version on the client site.            |
| `web_server`    |  string  | Web server type (e.g., Apache, Nginx).           |
| `server_ip`     |  string  | IP address of the server.                        |
| `php_version`   |  string  | PHP version on the client site.                  |
| `plugin_name`   |  string  | The name of the plugin associated with the key.  |
| `plugin_version`|  string  | Version of the plugin associated with the key.   |

**Example Request**

To test the API, use cURL or a tool like Postman:

1. Set the authorization to Basic Auth with your Consumer Key as the username and Consumer Secret as the password.
2. Send a GET request with the parameters outlined above.

**Example cURL**

```bash
curl -u "consumer_key:consumer_secret" \
  -G "https://yourwebsite.com/wp-json/smarty-vslm/v1/check-license" \
  --data-urlencode "license_key=your_license_key" \
  --data-urlencode "site_url=https://client-site.com" \
  --data-urlencode "wp_version=6.0" \
  --data-urlencode "web_server=Apache" \
  --data-urlencode "server_ip=123.456.78.90" \
  --data-urlencode "php_version=7.4" \
  --data-urlencode "plugin_name=Example Plugin" \
  --data-urlencode "plugin_version=1.0.0"
```

**Response**

The API returns JSON with the license status, expiration date, and other metadata.

## Functions

Below is a list of functions in the plugin along with their descriptions:

- `smarty_vslm_register_license_post_type`: Registers the custom post type vslm-licenses for licenses.
- `smarty_vslm_enqueue_admin_scripts`: Enqueues custom CSS and JS for the plugin admin area based on the current page.
- `smarty_vslm_add_license_meta_boxes`: Adds the "License Details" meta box in the license edit screen to input details like client name, email, status, and purchase details.
- `smarty_vslm_license_meta_box_title`: Generates the meta box title with a colored status dot that indicates the license's current status.
- `smarty_vslm_license_details_callback`: Callback function for displaying license details in the meta box. Shows fields for license key, client information, status, usage URL, and other metadata.
- `smarty_vslm_save_license_meta`: Saves the license metadata when the license is updated or created, ensuring data integrity with nonce verification.
- `smarty_vslm_set_numeric_slug`: Assigns a numeric slug (post ID) to each license, ensuring the slugs are unique and easy to reference.
- `smarty_vslm_register_product_taxonomy`: Registers a custom taxonomy product for associating licenses with specific products.
- `smarty_vslm_remove_view_link`: Removes the "View" link from the list of licenses in the admin list table.
- `smarty_vslm_remove_quick_edit`: Removes the "Quick Edit" option from the row actions for licenses in the admin list.
- `smarty_vslm_add_license_columns`: Adds custom columns such as License Key, Status, Purchase Date, and Expiration Date to the admin list for licenses.
- `smarty_vslm_fill_license_columns`: Populates the custom columns with data retrieved from the post meta, making license details visible at a glance in the list view.
- `smarty_vslm_sortable_license_columns`: Marks the License Key and Status columns as sortable in the licenses list table.
- `smarty_vslm_orderby_license_columns`: Modifies the WP Query to handle sorting by the custom meta fields (License Key and Status).
- `smarty_vslm_schedule_cron_job`: Sets up a daily cron job to check for expired licenses.
- `smarty_vslm_check_expired_licenses`: Cron job function that automatically marks licenses as expired when the expiration date passes.
- `smarty_vslm_license_manager_settings_page`: Creates a settings page for managing Consumer Key and Consumer Secret API credentials.
- `smarty_vslm_license_manager_settings_page_callback`: Callback for rendering the settings page with fields to generate and display API keys.
- `smarty_vslm_license_manager_register_settings`: Registers settings fields and sections for Consumer Key and Secret, displayed in the settings page.
- `smarty_vslm_generate_ck_key`: AJAX handler to generate a new Consumer Key for the API, triggered by a button on the settings page.
- `smarty_vslm_generate_cs_key`: AJAX handler to generate a new Consumer Secret for the API, also triggered by a button on the settings page.
- `smarty_vslm_register_license_status_endpoint`: Registers the REST API endpoint /check-license for license validation, protected by basic authentication.
- `smarty_vslm_basic_auth_permission_check`: Permission callback to verify Basic Auth credentials for API access.
- `smarty_vslm_check_license_status`: Callback for the REST API endpoint to check license status and return metadata, including expiration date and client details.

## Requirements

- WordPress 4.7+ or higher.
- WooCommerce 5.1.0 or higher.
- PHP 7.2+

## Changelog

For a detailed list of changes and updates made to this project, please refer to our [Changelog](./CHANGELOG.md).

## Contributing

Contributions are welcome. Please follow the WordPress coding standards and submit pull requests for any enhancements.

---

## License

This project is released under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).
