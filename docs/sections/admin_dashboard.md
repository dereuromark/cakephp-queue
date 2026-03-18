# Admin Dashboard

The Queue plugin includes a modern, self-contained admin dashboard for managing your queued jobs. The dashboard is completely isolated from your application's CSS/JS, using Bootstrap 5 and Font Awesome 6 via CDN.

## Features

- **Statistics Overview**: Real-time counts of pending, scheduled, running, and failed jobs
- **Status Banner**: Visual indicator showing whether the queue is running or idle
- **Job Management**: View, reset, and remove jobs directly from the dashboard
- **Worker Management**: Monitor active workers and terminate them if needed
- **Process History**: Track all queue processes with pagination
- **Trigger Jobs**: Manually add jobs that implement `AddFromBackendInterface`
- **Configuration View**: See current runtime configuration at a glance

## Enabling the Isolated Layout

By default, the admin controllers use your application's layout. To enable the isolated Bootstrap 5 layout, add this to your configuration:

```php
// In config/app.php or config/app_local.php
'Queue' => [
    'adminLayout' => 'Queue.queue',
    // other Queue config...
],
```

## Configuration Options

### Layout Configuration

```php
'Queue' => [
    // Enable the isolated Bootstrap 5 layout
    'adminLayout' => 'Queue.queue',

    // Auto-refresh dashboard every N seconds (0 = disabled)
    'dashboardAutoRefresh' => 30,
],
```

## Accessing the Dashboard

Navigate to `/admin/queue` to access the dashboard. The main pages are:

- `/admin/queue` - Dashboard with overview statistics
- `/admin/queue/processes` - Active workers management
- `/admin/queued-jobs` - Full job listing with search
- `/admin/queue-processes` - Process history

## Customization

### Overriding Templates

You can override any template by creating the same file structure in your application's `templates/plugin/Queue/` directory:

```
templates/
└── plugin/
    └── Queue/
        ├── layout/
        │   └── queue.php
        └── Admin/
            └── Queue/
                └── index.php
```

### Custom Elements

The dashboard uses several reusable elements that you can override:

- `Queue.Queue/sidebar` - Sidebar navigation
- `Queue.Queue/stats_card` - Statistics cards
- `Queue.Queue/status_badge` - Job status badges
- `Queue.flash/success` - Success flash messages
- `Queue.flash/error` - Error flash messages
- `Queue.flash/warning` - Warning flash messages
- `Queue.flash/info` - Info flash messages

### CSS Variables

The isolated layout uses CSS variables that you can override:

```css
:root {
    --queue-primary: #0d6efd;
    --queue-success: #198754;
    --queue-warning: #ffc107;
    --queue-danger: #dc3545;
    --queue-info: #0dcaf0;
    --queue-secondary: #6c757d;
    --queue-dark: #212529;
    --queue-light: #f8f9fa;
    --queue-sidebar-bg: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
    --queue-sidebar-width: 260px;
}
```

## Screenshots

The dashboard provides:

1. **Status Banner** - Shows queue status (Running/Idle) with last activity timestamp
2. **Stats Cards** - Quick overview of job counts by status
3. **Pending Jobs Table** - List of pending/running jobs with inline actions
4. **Scheduled Jobs** - Jobs scheduled for future execution
5. **Statistics** - Aggregated statistics for completed jobs
6. **Trigger Jobs** - Buttons to manually trigger addable jobs
7. **Configuration** - Current runtime configuration display
