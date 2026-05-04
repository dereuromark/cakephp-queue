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

## Layout Configuration

By default, the admin dashboard uses the isolated Bootstrap 5 layout (`Queue.queue`). This ensures the dashboard works independently of your application's styles.

### Configuration Options

```php
'Queue' => [
    // Layout for admin pages:
    // - null (default): Uses 'Queue.queue' isolated Bootstrap 5 layout
    // - false: Disables plugin layout, uses app's default layout
    // - string: Uses specified layout
    'adminLayout' => null,

    // Auto-refresh dashboard every N seconds (0 = disabled)
    'dashboardAutoRefresh' => 30,

    // Standalone mode (opt-in):
    // - false (default): Extends App\Controller\AppController, inherits app auth/components
    // - true: Isolated admin, skips app's AppController setup
    'standalone' => false,
],
```

### Controller Inheritance

By default, the Queue admin controllers extend your application's `AppController`. This means they inherit your authentication, authorization, components, and other controller configuration.

If you want the Queue admin to be completely isolated and not depend on your app's controller setup:

```php
'Queue' => [
    'standalone' => true,
],
```

This is useful when:
- Your app's `AppController` has complex authentication that you want to bypass for the Queue admin
- You want to use the Queue admin without configuring your app's authentication first
- You're using the plugin in a minimal setup without a full application

### Authorization (`Queue.adminAccess`, required)

The admin UI can trigger jobs (via `AddFromBackendInterface` tasks),
reset/remove queued jobs, and terminate workers — operational damage if
exposed. The plugin therefore **fails closed by default**: every request
to `/admin/queue/...` is rejected with `403` until the host app
explicitly configures access.

Set `Queue.adminAccess` to a `Closure` that receives the current request
and returns literal `true` to grant access. Anything else (unset,
non-`Closure`, returns `false`, returns a truthy non-bool, throws)
yields a `403`.

```php
use Cake\Core\Configure;
use Cake\Http\ServerRequest;

// Example — admin role check (cakephp/authentication identity):
Configure::write('Queue.adminAccess', function (ServerRequest $request): bool {
    $identity = $request->getAttribute('identity');
    return $identity !== null && in_array('admin', (array)$identity->roles, true);
});
```

The gate runs in `beforeFilter` for every admin controller in the plugin
and plays nicely with the cakephp/authorization plugin (it calls
`skipAuthorization()` so the policy layer doesn't double-reject).
`ForbiddenException` raised inside the Closure is respected as-is so
callers can short-circuit with their own message; other throwables are
logged via `Cake\Log\Log` and converted to a generic `403`.

`Queue.adminAccess` is independent of `Queue.standalone` — the access
gate runs in both modes.

### Using Your Application's Layout

To use your application's default layout instead of the isolated Bootstrap 5 layout:

```php
'Queue' => [
    'adminLayout' => false,
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
