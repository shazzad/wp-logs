**#### Unreleased**

- [ADDED] The Logs screen warns when the debug log answers HTTP requests. Once a day at most, the site sends a HEAD request (never a GET, so the log body is not downloaded) to the log's URL, plus one to a file that does not exist beside it. The notice appears only when the log answers 200, that answer is not served as `text/html`, and the missing file gets a 403, 404 or 410. Every other outcome is recorded as "unknown" and stays silent: hosts that answer 200 for every path, custom access-denied or login pages sent with a 200, and a check for the missing file that fails, times out, is rate limited or errors all prove nothing either way, and "unknown" is never read as safe. The notice says what was observed, shows Apache and nginx deny rules, and has "Check again" and "Dismiss" links. Dismissal is stored per site and is cleared if the log is later seen refusing requests, so the notice comes back if the fix comes undone. The plugin does not write server configuration. Turn the check off with the `swpl_debug_log_exposure_check` filter; override the requested URL with `swpl_debug_log_exposure_url`.
- [CHANGED] Requires WordPress 6.2 or later. The React admin screens have used `createRoot`, which WordPress added in 6.2, so the plugin header's 4.4 and the README's 5.0 both claimed support that did not exist; they now say 6.2. PHP 7.4 is unchanged.
- [FIXED] The debug log viewer looked for the default log at `ABSPATH/wp-content/debug.log` instead of `WP_CONTENT_DIR/debug.log`, which is a different folder on sites that move `wp-content`. It also read `WP_DEBUG_LOG` set to the string `'true'` or `'1'` as a file path; WordPress treats those as the default location, and the viewer now does too.

**#### 2.1.5 2026-09-16**

- [FIXED] The admin bar's "WP Debug Log" item pointed at `wp-content/debug.log` — the raw file, served by the web server with no capability check of any kind. It only worked at all on sites where the log happens to be publicly fetchable, and it taught the administrator that it was. The item now opens the plugin's own viewer, which reads the log through a REST route behind `manage_options`, and falls back to the plugin's Logs screen when its script has not loaded.
- [FIXED] Clicking that item opened the log modal and then navigated away from the page anyway — the click handler never cancelled the link's default action, unlike every other handler in the same file.
- [FIXED] `WpDebugLog::admin_bar_menu()` had no capability check of its own. Nothing was exposed, because the parent menu group it attaches to is created by a gated callback at an earlier priority, but that made a security boundary depend on the relative priority of two callbacks in two files. The check is now explicit.

**#### 2.1.4 2026-09-08**

- [FIXED] Request logging crashed with `ValueError: str_repeat(): Argument #2 ($times) must be greater than or equal to 0` on PHP 8 whenever a logged key matching `password|secret|token|authorization|x-api-key` carried a value shorter than three characters — an empty field included. `sanitize_data()` masks the request payload, request headers, response data and response headers of every logged outgoing request, so a blank or truncated token fataled the logger on exactly the request someone had turned logging on to inspect.
- [FIXED] A value of exactly three characters was written to the log in full, labelled `(masked)`. The mask kept a three-character prefix at every length, so at that length the prefix was the whole secret. Values of three characters or fewer are now masked entirely; longer values keep the prefix as before.

**#### 2.1.3 2026-08-20**

- [UPDATED] Tested up to WordPress 7.1.

**#### 2.1.2 2026-08-19**

- [FIXED] After updating from the plugins screen, the update nag reappeared offering an "update" to the version already installed, until a second (redundant) reinstall. 2.0.10's fix ran too late: its cleanup fired on `upgrader_process_complete` at priority 20, while WordPress rebuilds the update transient on the same action at priority 10 — so the rebuild still compared the release against the pre-update version it had memoised. `shazzad/github-plugin-updater` 0.0.6 resets the memoised header at priority 5, before the rebuild.
- [FIXED] The updater's `after_install` handler ran on every plugin/theme install and update on the site, attempting to move foreign packages into this plugin's directory (harmless only because the filesystem move fails silently). It now acts only on this plugin's own updates.
- [UPDATED] `shazzad/github-plugin-updater` to 0.0.6.

**#### 2.1.1 2026-08-19**

- [FIXED] The Logs and Requests filter toolbars rendered every control at a different height — the plugin's stylesheet was squeezing the search input to 30px and the dropdowns to 32px inside their 40px `@wordpress/components` wrappers while the buttons stretched to 40px. The overrides are gone; all controls now share the standard 40px height.
- [FIXED] The filter dropdowns were forced to share the toolbar width equally, truncating their own labels ("10 per pa…") at narrower windows. Each dropdown now sizes to its content, and the toolbar wraps instead of overflowing.

**#### 2.1.0 2026-08-19**

- [CHANGED] Retention of 0 ("infinite") is no longer valid: logs and requests are now always purged, default 7 days, minimum 1. Sites that want long retention can set a high number of days.
- [FIXED] Sites that installed the plugin before 2.0.8 and updated never received the retention settings, so the hourly cleanup jobs ran but deleted nothing. Upgrades now seed the defaults and migrate stored values below 1 to 7 days; valid values are untouched.
- [FIXED] Settings save now sanitizes the retention fields — non-numeric, zero or negative input is stored as the 7-day default instead of silently disabling cleanup.
- [IMPROVEMENT] The retention fields in the settings screen are now real number inputs with a minimum of 1.

**#### 2.0.10 2026-08-05**

- [FIXED] The update notice kept showing "You have version X installed. Update to X." after a successful update, until the next full update check.
- [UPDATED] `shazzad/github-plugin-updater` to 0.0.5, and pinned it to a tagged release instead of tracking `dev-main`.

**#### 2.0.9 2026-08-05**

- [FIXED] Update notifications never appeared. `shazzad/github-plugin-updater` treated the optional `tested`, `requires` and `requires_php` fields as mandatory, so releases without them were silently discarded and no site was ever offered an update.
- [UPDATED] Bumped `shazzad/github-plugin-updater` to the release carrying that fix.
- [UPDATED] README requirements list uses `*` bullets so older installs running the unpatched updater can also resolve the metadata.

**#### 2.0.1 2025-05-04**

- [IMPROVEMENT] Trim request url before storing in database

**#### 2.0.0 2025-05-04**

- [ADDED] REST API functionality and pagination support for log retrieval
- [ADDED] HTTP request storage capability with dedicated requests table
- [ADDED] Comprehensive log management features including filtering, sorting, and bulk deletion
- [ADDED] ReactJsonView for enhanced data visualization in log and request details
- [ADDED] Per-page filtering options for Logs and Requests components
- [ADDED] Documentation for HTTP request logging in README.md
- [UPDATED] Components and styles for consistency across log and request management
- [UPDATED] Modal system with improved styling and localization
- [UPDATED] Database operations for better performance and raw message storage
- [UPDATED] Version to 2.0.0 with 'timestamp' changed to 'date_created' for clarity

**#### 1.1.0 2022-08-30**

- [REMOVED] Exception raised on failed log creation
- [IMPROVEMENT] Sanitized context data size before insert

**#### 1.1.0 2022-07-27**

- [IMPROVEMENT] Optimized delete all function

**#### 1.0.9 2021-05-25**

- [IMPROVEMENT] Fixed title action link when notice available

**#### 1.0.7 2021-05-20**

- [IMPROVEMENT] Fixed log search query

**#### 1.0.6 2021-04-03**

- [UPDATED] GitHub updater

**#### 1.0.5 2021-04-02**

- [IMPROVEMENT] Fixed min CSS/JS loading issue

**#### 1.0.4 2021-04-02**

- [UPDATED] Date timezone to GMT
- [UPDATED] Preview style

**#### 1.0.3 2021-03-25**

- [ADDED] GitHub updater

**#### 1.0.2 2021-03-25**

- [UPDATED] Modified 3rd party menu integration feature
