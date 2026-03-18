# Multi-Connection Support

If your application uses multiple database connections (e.g., primary database and a secondary "acme" database), you can configure the Queue plugin to manage jobs across all of them.

## Configuration

Enable multi-connection mode by defining an array of connections in your config:

```php
// In app_queue.php or your app config
$config['Queue']['connections'] = ['default', 'acme'];
```

The first connection in the array becomes the default when no specific connection is selected.

**Important:**
- Multi-connection mode is only enabled when 2 or more connections are configured
- Only connections in this whitelist can be used (security feature)
- Without this config, the plugin operates in backwards-compatible single-connection mode

## Admin Dashboard

When multi-connection mode is enabled, a connection switcher dropdown appears in the admin navigation bar.

- The dropdown shows all configured connections
- Select a connection to view jobs/workers for that specific database
- The current connection is highlighted
- The connection selection persists via URL query parameter (`?connection=acme`)

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

Jobs are stored in the database they were created against. To create a job for a specific connection, you need to get the table with that connection:

```php
use Cake\Datasource\ConnectionManager;

// Get the QueuedJobs table
$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');

// For a non-default connection, set it explicitly
$connection = ConnectionManager::get('acme');
$queuedJobsTable->setConnection($connection);

// Create the job - it will be stored in the 'acme' database
$queuedJobsTable->createJob('MyTask', $data);
```

Or using the table locator directly:

```php
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->setConnection(ConnectionManager::get('acme'));

$queuedJobsTable->createJob('MyTask', ['key' => 'value']);
```

### Helper Method Pattern

For cleaner code, consider creating a helper method or service:

```php
// In a utility class or base controller
protected function getQueueTable(string $connection = 'default'): QueuedJobsTable {
    $table = $this->fetchTable('Queue.QueuedJobs');

    if ($connection !== 'default') {
        $table->setConnection(ConnectionManager::get($connection));
    }

    return $table;
}

// Usage
$this->getQueueTable('acme')->createJob('MyTask', $data);
```

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
