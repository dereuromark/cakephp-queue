# Multi-Connection Support

If your application uses multiple database connections (e.g., primary database and a secondary "acme" database), you can configure the Queue plugin to manage jobs across all of them.

## Configuration

Enable multi-connection mode by defining an array of connections in your config:

```php
// In app_queue.php or your app config
$config['Queue']['connections'] = ['default', 'acme'];
```

The first configured connection is the default for the admin interface and the worker and job commands when no connection is selected.

**Important:**
- Multi-connection mode is only enabled when 2 or more connections are configured
- The admin selector and worker and job commands only accept connections from this list
- Without this config, the plugin operates in backwards-compatible single-connection mode

## Admin Dashboard

When multi-connection mode is enabled, a connection switcher dropdown appears in the admin navigation bar.

- The dropdown shows all configured connections
- Select a connection to view jobs/workers for that specific database
- The current connection is highlighted
- The selection is stored in the session; a URL query parameter (`?connection=acme`) overrides it

All admin pages (Dashboard, Jobs, Workers, Processes) will show data for the selected connection.

## CLI Usage

### Running Workers

Use the `--connection` option to start a worker for a specific connection:

```bash
# Run worker for default connection
bin/cake queue run

# Run worker for 'acme' connection
bin/cake queue run --connection acme
```

### Managing Jobs

The `queue job` command also supports the `--connection` option:

```bash
# View jobs on default connection
bin/cake queue job view 123

# View jobs on 'acme' connection
bin/cake queue job view 123 --connection acme

# Reset failed jobs on 'acme' connection
bin/cake queue job reset all --connection acme
```

## Creating Jobs for Specific Connections

Jobs belong to the connection used to create them. Use a separate table instance for each connection so later calls to the shared table do not inherit a changed connection:

```php
use Cake\Datasource\ConnectionManager;
use Queue\Model\Table\QueuedJobsTable;

$queuedJobs = new QueuedJobsTable([
    'alias' => 'AcmeQueuedJobs',
    'connection' => ConnectionManager::get('acme'),
]);
$queuedJobs->createJob('MyTask', ['key' => 'value']);
```

Do not switch a shared table's connection and assume the next caller will restore it. Use matching workers and install the Queue schema on each database.

## Running Workers in Production

For production environments with multiple connections, you'll need separate workers for each connection:

### Cron Setup

```bash
# Worker for default connection
* * * * * cd /var/www/app && bin/cake queue run

# Worker for acme connection
* * * * * cd /var/www/app && bin/cake queue run --connection acme
```

### Supervisor Configuration

```ini
[program:queue-default]
command=bin/cake queue run
directory=/var/www/app
autostart=true
autorestart=true

[program:queue-acme]
command=bin/cake queue run --connection acme
directory=/var/www/app
autostart=true
autorestart=true
```

## Database Setup

Each connection needs the queue tables (queued_jobs, queue_processes). Run migrations for each connection:

```bash
# Default connection
bin/cake migrations migrate --plugin Queue

# Acme connection
bin/cake migrations migrate --plugin Queue -c acme
```

## Troubleshooting

### Jobs not being picked up

Ensure you're running a worker for each configured connection. A worker only processes jobs from its assigned connection.

### Invalid connection error

If you see "Invalid connection: xyz", verify the connection is:
1. Listed in `Queue.connections` config
2. Properly configured in your database config

### Jobs created in wrong database

When creating jobs, always explicitly set the connection if targeting a non-default database. The table remembers its connection until explicitly changed.
