# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin (PHP + React) that stores and displays runtime logs and HTTP request data in custom database tables. Logs are viewed in a React SPA at **WP Admin > Logs**.

- **PHP namespace:** `Shazzad\WpLogs\` (PSR-4 autoloaded from `includes/`)
- **Requirements:** WordPress 5.0+, PHP 7.4+

## Build Commands

```bash
# Install dependencies
npm install && composer install

# Build React admin interface (outputs to admin/build/)
npm run build-admin

# Start React dev server with hot reload
npm run start-admin

# Full distribution build (updates version string + creates build/shazzad-wp-logs.v{version}.zip)
npm run build
```

The React app uses `@wordpress/scripts` for build tooling. Grunt handles version stamping and zip compression for releases.

## Architecture

### Layered structure

```
React SPA (admin/src/)
    ↕ @wordpress/api-fetch
REST Controllers (includes/RestController/)
    ↕
Data Models + Query Builders (includes/Log/, includes/Request/)
    ↕ extends Abstracts (CrudApi, Query, Data)
DbAdapter (includes/DbAdapter.php) → WordPress $wpdb
```

### Key components

- **`shazzad-wp-logs.php`** — Entry point. Creates `Plugin` singleton, registers activation hook, registers WP-CLI commands.
- **`Plugin.php`** — Singleton that loads autoloader, initializes Mustache engine, triggers setup of all subsystems on `init`.
- **`Hooks.php`** — Registers the `swpl_log` action and the `http_api_debug` filter for capturing HTTP requests. Sanitizes sensitive data (passwords, tokens, keys).
- **`Installer.php`** — Creates/upgrades `{prefix}swpl_logs` and `{prefix}swpl_requests` database tables. Called on activation and on version change via `Plugin::maybe_upgrade_db()`.
- **`RestApi.php`** — Registers REST routes under namespace `swpl/v1`. All endpoints require `manage_options` capability.
- **`Abstracts/`** — Base classes (`CrudApi`, `Query`, `Data`, `Settings`) that `Log/` and `Request/` models extend.
- **`SettingsRepository.php`** — Central settings management backed by WordPress options.

### React admin (admin/src/)

- **`App.js`** — HashRouter with tabs: Logs, Requests, Plugin Settings.
- **Pages:** `Logs.js`, `Requests.js`, `PluginSettings.js` — each communicates with the REST API.
- Uses `react-toastify` for notifications and `@microlink/react-json-view` for context data display.

### Database tables

- **`swpl_logs`** — id, date_created, source, level, message_raw, message, context
- **`swpl_requests`** — id, date_created, request_method, request_url, request_hostname, request/response headers/payload/data, response_code, response_size, response_time

### External integration points

- **Logging:** `do_action('swpl_log', $source, $message, $context, $level)` — uses Mustache templates for message rendering.
- **HTTP request filtering:** `add_filter('swpl_log_request', $callback, 10, 2)` — return `true` to log a request by URL.
- **WP-CLI:** `commands/GenerateLogsCommand.php` — generates test log data (uses fakerphp/faker).

### Version management

Version is maintained in `package.json`. The `grunt version` task syncs it to the `Version:` header and `SWPL_VERSION` constant in `shazzad-wp-logs.php`.
