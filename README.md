# LPMS - Lempeng Pisang Management System

LPMS is a responsive PHP 8 and MySQL 8 web application for managing a small lempeng pisang business. It uses Bootstrap 5, jQuery, DataTables, Chart.js, PDO prepared statements, CSRF protection, password hashing, and session timeout handling.

## Features

- Admin login with session-based authentication
- Dashboard metrics for daily and monthly sales, expenses, profit, pieces sold, and pending orders
- Last 7 days sales trend chart
- Sales by category chart
- Sales entry with editable transaction price and automatic total
- Expense tracking by category
- Tempahan order tracking with status filtering and quick status updates
- Reports for today, yesterday, this week, this month, and custom date ranges
- CSV export and printable reports
- Business name and default price settings
- Admin password change
- Mobile-friendly Bootstrap admin layout

## Requirements

- PHP 8.0 or newer
- MySQL 8.0 or newer
- PHP PDO MySQL extension enabled
- Shared hosting with HTTPS recommended

## Installation

1. Create a MySQL database named `lpms`.
2. Import `database.sql` using phpMyAdmin or your hosting control panel.
3. Upload all project files to your hosting public web folder.
4. Edit `config/config.php` and set your database values:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lpms');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

5. Open the site in your browser and log in.

## Sample Admin Account

- Username: `admin`
- Password: `password`

Change the password after first login from Settings.

## Recommended Production Step

After importing `database.sql`, keep a private copy and remove `database.sql` from the public hosting folder if your host serves it as a downloadable file.

## Deployment Notes

- The app has no Laravel or Node.js requirement.
- Bootstrap, jQuery, DataTables, Bootstrap Icons, and Chart.js are loaded from public CDNs.
- Use HTTPS in production so session cookies are protected in transit.
- Keep `config/config.php` readable only by the hosting account where possible.

## Future Modules

The include-based structure is intentionally simple so new pages can be added for frozen stock management, catering cost tracking, analytics, and multi-user roles later.
