# Real-Time Progress with Mercure

This guide explains how to display real-time queue job progress in the browser using [Mercure](https://mercure.rocks/) and Server-Sent Events (SSE). This is particularly useful with [FrankenPHP](https://frankenphp.dev/) which has Mercure built-in.

## Overview

Instead of polling the server or requiring page refreshes, the queue worker pushes updates directly to the browser:

1. User triggers a background job
2. Job runs in queue worker process
3. Each progress step publishes a Mercure update
4. Browser receives updates instantly via EventSource

## Requirements

- [josbeir/cakephp-mercure](https://github.com/josbeir/cakephp-mercure) plugin
- Mercure hub (standalone or built into FrankenPHP)

## Setup

### 1. Install the Mercure Plugin

```bash
composer require josbeir/cakephp-mercure
```

Load the plugin:

```php
// config/plugins.php
return [
    'Mercure' => [],
    // ...
];
```

### 2. Configure Mercure

Create `config/app_mercure.php`:

```php
<?php
use Cake\Http\Cookie\CookieInterface;

return [
    'Mercure' => [
        // Internal URL for server-side publishing (inside container/server)
        'url' => 'http://localhost/.well-known/mercure',

        // External URL for browser EventSource connections
        'public_url' => 'https://your-domain.com/.well-known/mercure',

        'jwt' => [
            'secret' => 'your-mercure-jwt-secret',
            'algorithm' => 'HS256',
            'publish' => ['*'],
            'subscribe' => [],
        ],

        'cookie' => [
            'name' => 'mercureAuthorization',
            'secure' => true,
            'httponly' => true,
            'samesite' => CookieInterface::SAMESITE_LAX,
        ],
    ],
];
```

Load it in `config/bootstrap.php`:

```php
Configure::load('app_mercure');
```

### 3. FrankenPHP with Mercure

If using FrankenPHP, add Mercure to your Caddyfile or environment:

```
CADDY_SERVER_EXTRA_DIRECTIVES=
mercure {
    publisher_jwt your-mercure-jwt-secret
    subscriber_jwt your-mercure-jwt-secret
    anonymous
    cors_origins *
}
```

For DDEV, create `.ddev/docker-compose.mercure.yaml`:

```yaml
services:
  web:
    environment:
      - |-
        CADDY_SERVER_EXTRA_DIRECTIVES=
        mercure {
            publisher_jwt your-mercure-jwt-secret
            subscriber_jwt your-mercure-jwt-secret
            anonymous
            cors_origins *
        }
```

## Creating a Queue Task with Mercure Updates

```php
<?php
declare(strict_types=1);

namespace App\Queue\Task;

use Cake\Core\Configure;
use Mercure\Publisher;
use Mercure\Update\JsonUpdate;
use Queue\Queue\Task;

class MyProgressTask extends Task {

    public ?int $timeout = 120;

    public function run(array $data, int $jobId): void {
        $topic = $data['topic'] ?? '/jobs/' . $jobId;
        $steps = 10;

        // Check if Mercure is configured
        $mercureConfigured = (bool)Configure::read('Mercure.url');

        // Publish start event
        if ($mercureConfigured) {
            $this->publishUpdate($topic, [
                'status' => 'started',
                'progress' => 0,
                'message' => 'Job started',
                'jobId' => $jobId,
            ]);
        }

        for ($i = 1; $i <= $steps; $i++) {
            // Do actual work here...
            sleep(1);

            $progress = (int)(($i / $steps) * 100);

            // Update queue progress (for DB tracking)
            $this->QueuedJobs->updateProgress($jobId, $i / $steps, "Step {$i} of {$steps}");

            // Publish Mercure update (for real-time UI)
            if ($mercureConfigured) {
                $this->publishUpdate($topic, [
                    'status' => 'progress',
                    'progress' => $progress,
                    'step' => $i,
                    'totalSteps' => $steps,
                    'message' => "Processing step {$i} of {$steps}",
                    'jobId' => $jobId,
                ]);
            }
        }

        // Publish completion event
        if ($mercureConfigured) {
            $this->publishUpdate($topic, [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Job completed successfully!',
                'jobId' => $jobId,
            ]);
        }
    }

    protected function publishUpdate(string $topic, array $data): void {
        try {
            Publisher::publish(JsonUpdate::create(
                topics: $topic,
                data: $data,
            ));
        } catch (\Exception $e) {
            $this->io->error('Mercure publish failed: ' . $e->getMessage());
        }
    }
}
```

## Controller

```php
<?php
namespace App\Controller;

use Cake\Core\Configure;

class JobsController extends AppController {

    public function progress(): void {
        $sid = $this->request->getSession()->id();
        $topic = '/jobs/user/' . $sid;

        $this->set('topic', $topic);
        $this->set('mercurePublicUrl', Configure::read('Mercure.public_url'));
    }

    public function startJob() {
        $this->request->allowMethod('post');

        $queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
        $sid = $this->request->getSession()->id();
        $topic = '/jobs/user/' . $sid;

        $queuedJobsTable->createJob(
            'MyProgress',
            ['topic' => $topic],
            ['reference' => 'user-job-' . $sid],
        );

        $this->Flash->success('Job started!');
        return $this->redirect(['action' => 'progress']);
    }
}
```

## Template with EventSource

```php
<?php
// templates/Jobs/progress.php
$topic = $topic ?? '/jobs/default';
$mercurePublicUrl = $mercurePublicUrl ?? null;
?>

<div id="progress-container">
    <div class="progress">
        <div id="progress-bar" class="progress-bar" style="width: 0%">0%</div>
    </div>
    <p id="status-message">Waiting for job...</p>
</div>

<?php if ($mercurePublicUrl): ?>
<script>
(function() {
    const topic = <?= json_encode($topic) ?>;
    const mercureUrl = <?= json_encode($mercurePublicUrl) ?>;

    const url = new URL(mercureUrl);
    url.searchParams.append('topic', topic);

    const eventSource = new EventSource(url, { withCredentials: true });

    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);

        document.getElementById('progress-bar').style.width = data.progress + '%';
        document.getElementById('progress-bar').textContent = data.progress + '%';
        document.getElementById('status-message').textContent = data.message;

        if (data.status === 'completed') {
            document.getElementById('progress-bar').classList.add('bg-success');
        }
    };

    eventSource.onerror = function() {
        console.log('Connection error, will auto-reconnect...');
    };
})();
</script>
<?php endif; ?>
```

## Running Workers

### Development (DDEV with FrankenPHP)

Add to `.ddev/config.frankenphp.yaml`:

```yaml
web_extra_daemons:
  - name: "frankenphp"
    command: "frankenphp run --config /etc/frankenphp/Caddyfile --adapter=caddyfile"
    directory: /var/www/html
  - name: "queue-worker"
    command: "bash -c 'sleep 5 && DDEV_PROJECT=myproject bin/cake queue run -v'"
    directory: /var/www/html
```

### Production (systemd)

Create `/etc/systemd/system/myapp-queue.service`:

```ini
[Unit]
Description=CakePHP Queue Worker
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/myapp
ExecStart=/usr/bin/php bin/cake queue run
Restart=always
RestartSec=5
Environment=APP_ENV=production

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable myapp-queue
sudo systemctl start myapp-queue
```

### Production (supervisor)

Create `/etc/supervisor/conf.d/myapp-queue.conf`:

```ini
[program:myapp-queue]
command=/usr/bin/php bin/cake queue run
directory=/var/www/myapp
user=www-data
autostart=true
autorestart=true
numprocs=2
process_name=%(program_name)s_%(process_num)02d
stderr_logfile=/var/log/myapp/queue-error.log
stdout_logfile=/var/log/myapp/queue.log
```

### Production (Docker / docker-compose)

When running queue workers in Docker containers, there are specific considerations:

**The PID 1 Problem**: In Docker, the main process always runs as PID 1. The queue plugin tracks workers by `PID + server hostname`. If a container crashes and restarts, it tries to register with the same PID but the old record still exists, causing a duplicate key error.

**Solution**: Set a stable hostname and clean up stale processes on startup:

```yaml
services:
  app:
    image: your-app:latest
    # ... your main app config

  queue:
    image: your-app:latest
    hostname: queue-worker  # Stable hostname across restarts
    restart: unless-stopped
    depends_on:
      - app
    volumes:
      - ./:/app
    working_dir: /app
    command: sh -c "php bin/cake.php queue worker end all 2>/dev/null || true; php bin/cake.php queue run"
    environment:
      - APP_ENV=production
```

Key points:
- `hostname: queue-worker` ensures the server name stays consistent across container restarts (instead of using the random container ID)
- The startup command first cleans up any stale process records, then starts the worker
- `|| true` ensures the worker starts even if there are no stale processes to clean

**Crontab-style with short-lived workers**: For behavior similar to traditional crontab (fresh workers, natural scaling), use `--max-runtime`:

```yaml
  queue:
    image: your-app:latest
    hostname: queue-worker
    restart: always  # Always restart after clean exit
    command: sh -c "php bin/cake.php queue worker end all 2>/dev/null || true; php bin/cake.php queue run --max-runtime=300"
```

This runs workers for 5 minutes, exits cleanly, and Docker restarts them. Scale with:

```bash
docker compose up -d --scale queue=3
```

### Scaling Workers

The `maxworkers` config limits concurrent workers across all servers:

```php
'Queue' => [
    'maxworkers' => 4,
],
```

Scale horizontally by running workers on multiple servers - they share the same database queue and respect `maxworkers`.

For longer-running production workers, increase `workerLifetime`:

```php
'Queue' => [
    'workerLifetime' => 3600, // 1 hour (0 = unlimited)
],
```

## Testing

Mock the Mercure Publisher in tests to prevent HTTP requests:

```php
use Mercure\Publisher;
use Mercure\TestSuite\MockPublisher;

public function setUp(): void {
    parent::setUp();
    Publisher::setInstance(new MockPublisher());
}

public function tearDown(): void {
    parent::tearDown();
    Publisher::clear();
}
```

## See Also

- [josbeir/cakephp-mercure](https://github.com/josbeir/cakephp-mercure) - CakePHP Mercure plugin
- [Mercure Protocol](https://mercure.rocks/) - Real-time protocol
- [FrankenPHP](https://frankenphp.dev/) - PHP app server with built-in Mercure
