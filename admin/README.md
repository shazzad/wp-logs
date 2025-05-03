# WordPress Logs Management System - Project Documentation

## Overview

The WordPress Logs Management System is a custom admin interface built using React and WordPress REST API. It provides a robust interface for viewing, filtering, sorting, and managing log entries stored in a custom WordPress database table. The application follows modern React practices and WordPress development standards to deliver a seamless admin experience.

## Core Features

### 1. Log Viewing Interface

- Displays logs in a paginated table format
- Shows key log information: ID, date, level, source, and message
- View detailed log information in a modal popup
- Responsive design that works across all screen sizes

### 2. Advanced Filtering

- Search functionality across all log fields
- Filter by log level (debug, info, warning, error)
- Filter by log source (configurable sources)
- Multiple filters can be combined
- Reset filters option

### 3. Sorting Capabilities

- Sort by ID (ascending/descending)
- Sort by date (ascending/descending)
- Visual indicators for current sort field and direction

### 4. Pagination

- Configurable number of logs per page (default: 10)
- Navigate between pages
- Displays total number of logs and current page information

### 5. Log Management

- Select individual logs using checkboxes
- Bulk selection of all logs on current page
- Delete selected logs
- Delete all logs
- Confirmation dialogs for destructive actions

### 6. Detailed Log Information

- Modal popup shows complete log details
- Formatted display of log context data
- Log context is loaded on demand to improve performance

## Technical Implementation

### Architecture

- Component-based architecture using React
- State management using React hooks
- REST API integration for data retrieval and manipulation
- Responsive design using CSS/SCSS

### Components Structure

1. **Main Container Component** (`Logs.js`):

   - Manages state and API communication
   - Coordinates child components
   - Handles data loading and error states

2. **LogFilters Component**:

   - Handles search and filter inputs
   - Manages filter state and applying filters

3. **LogTable Component**:

   - Displays log data in tabular format
   - Handles log selection and sorting
   - Shows loading indicators when needed

4. **LogsBulkActions Component**:

   - Provides controls for bulk operations
   - Shows selection count and action buttons

5. **LogsDeleteConfirmationModal Component**:

   - Confirmation dialog for delete operations
   - Prevents accidental data loss

6. **LogDetailsModal Component**:

   - Displays detailed log information
   - Loads log details on demand
   - Formats complex log context data

7. **SimplePagination Component**:
   - Handles page navigation
   - Shows current page status

### REST API Integration

- Endpoint: `/wp/v2/logs`
- Supports GET, DELETE methods
- Query parameters:
  - `page`: Current page number
  - `per_page`: Items per page
  - `search`: Search term
  - `level`: Filter by log level
  - `source`: Filter by log source
  - `orderby`: Sort field
  - `order`: Sort direction (asc/desc)
- DELETE method supports:
  - Deleting all logs
  - Deleting specific logs by ID

### WordPress Integration

- Custom REST API endpoints registered in WordPress
- Data stored in a custom database table
- Admin page registered in WordPress admin menu
- Script localization for passing PHP data to JavaScript
- WordPress nonce integration for security

### Styling

- SCSS for enhanced CSS capabilities
- Variables for consistent colors and spacing
- Responsive design using media queries
- WordPress admin UI styling consistency
- BEM-like naming conventions for CSS classes

## Configuration

The application receives configuration from WordPress via `wp_localize_script`:

```php
wp_localize_script(
    'swpl-admin-app',
    'swplAdminAppSettings',
    array(
        'root'       => esc_url_raw( rest_url() ),
        'nonce'      => wp_create_nonce( 'wp_rest' ),
        'levels'     => swpl_get_levels(),
        'logSources' => swpl_get_sources(),
    )
);
```

### Where:

- root: The WordPress REST API base URL
- nonce: Security token for API requests
- levels: Available log levels (debug, info, warning, error, etc.)
- logSources: Available log sources (configurable)

### Log Data Structure

#### Each log entry contains:

- id: Unique identifier
- date: Datetime of the log event
- level: Severity level (debug, info, warning, error)
- source: Component or feature that generated the log
- message: Primary log message
- context: Additional data in JSON format (shown in details view)

### Future Enhancements

- Date range filtering
- Log level statistics and visualization
- Real-time log updates
- Log retention policies
- Custom views and saved filters
