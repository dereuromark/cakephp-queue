# Real-time progress with Mercure

Queue progress is stored in the database. To push updates to browsers, your task can also publish through the optional [Mercure plugin](https://github.com/josbeir/cakephp-mercure). Queue does not publish Mercure events automatically.

## Set up the hub and plugin

Install and load the optional plugin:

```sh
composer require josbeir/cakephp-mercure
bin/cake plugin load Mercure
```

Run a Mercure hub using its [deployment documentation](https://mercure.rocks/docs/hub/install). Configure the hub's publisher and subscriber signing keys, and make the same secret available to CakePHP as `MERCURE_JWT_SECRET`. For HS256, use a secret of at least 32 bytes; `openssl rand -hex 32` generates one.

This example uses the application's HTTPS origin for browser subscriptions. Put the hub endpoint behind that origin so its authorization cookie applies to requests. For a separate origin, configure cookie scope and the hub's allowed origins according to the Mercure plugin docs.

```php
use Cake\Http\Cookie\CookieInterface;

return [
    'Mercure' => [
        'url' => 'http://mercure/.well-known/mercure',
        'public_url' => 'https://app.example.com/.well-known/mercure',
        'jwt' => [
            'secret' => env('MERCURE_JWT_SECRET'),
            'algorithm' => 'HS256',
            'publish' => ['https://app.example.com/jobs/{id}'],
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

Replace the URLs for your deployment. Load this configuration in both the web application and CLI worker. Use the [plugin's configuration reference](https://github.com/josbeir/cakephp-mercure/blob/master/config/mercure.php) for other signing strategies.

## Publish private updates from a task

Create `src/Queue/Task/MyProgressTask.php`:

```php
namespace App\Queue\Task;

use Mercure\Publisher;
use Mercure\Update\JsonUpdate;
use Queue\Queue\Task;

class MyProgressTask extends Task {
    public ?int $timeout = 120;

    public function run(array $data, int $jobId): void {
        $topic = $data['topic'];
        $steps = 10;
        for ($step = 1; $step <= $steps; $step++) {
            // Replace this delay with your application's work.
            sleep(1);
            $progress = $step / $steps;
            $this->QueuedJobs->updateProgress($jobId, $progress);
            Publisher::publish((new JsonUpdate($topic))
                ->data([
                    'jobId' => $jobId,
                    'progress' => (int)round($progress * 100),
                    'status' => $step === $steps ? 'completed' : 'progress',
                ])
                ->private()
                ->build());
        }
    }
}
```

A publishing failure throws and can cause a task retry. Decide whether that is appropriate for your work. For progress that is optional, catch publishing errors separately after recording database progress; keep task failures visible to the worker.

## Authorize the browser's topic

The controller must enforce your application's access rules before granting a Mercure subscription. This example gives the current session a separate random progress topic; it does not publish the PHP session ID. For jobs shared across requests or devices, store an operation ID and verify its ownership instead.

```php
namespace App\Controller;

use Cake\Core\Configure;

class JobsController extends AppController {
    public function initialize(): void {
        parent::initialize();
        $this->loadComponent('Mercure.Mercure');
    }

    public function progress(): void {
        $topic = $this->progressTopic();
        $this->Mercure->authorize([$topic]);
        $this->set('topic', $topic);
        $this->set('mercurePublicUrl', Configure::read('Mercure.public_url'));
    }

    public function startJob() {
        $this->request->allowMethod('post');
        $topic = $this->progressTopic();
        $this->fetchTable('Queue.QueuedJobs')->createJob('MyProgress', ['topic' => $topic]);

        return $this->redirect(['action' => 'progress']);
    }

    protected function progressTopic(): string {
        $session = $this->request->getSession();
        $topic = $session->read('QueueProgress.topic');
        if (!is_string($topic) || $topic === '') {
            $topic = 'https://app.example.com/jobs/' . bin2hex(random_bytes(16));
            $session->write('QueueProgress.topic', $topic);
        }

        return $topic;
    }
}
```

Use your existing authenticated and authorized `AppController`. Keep CSRF protection on the POST that queues work. `authorize()` sets the subscriber cookie for this topic; private updates are not made public by knowing their topic name.

## Subscribe in the template

Place this in `templates/Jobs/progress.php`:

```php
<p id="job-progress">Waiting for progress</p>
<script>
const topic = <?= json_encode($topic, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const hub = <?= json_encode($mercurePublicUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const url = new URL(hub);
url.searchParams.append('topic', topic);
const source = new EventSource(url, { withCredentials: true });
source.onmessage = function (event) {
    const update = JSON.parse(event.data);
    document.getElementById('job-progress').textContent = update.progress + '%';
    if (update.status === 'completed') {
        source.close();
    }
};
</script>
```

Mercure updates are not a substitute for querying the stored job status. A visitor can open this page after the job has finished, or miss updates during a reconnect. Add a status endpoint that checks job ownership and reads the job's current state.

## Run and test workers

Use [Getting started](./getting-started) to run a worker, and [Operations](./operations) to keep it running in production. `maxworkers` is a per-server cap. Give each container a distinct server identity; assigning the same hostname to every scaled container can cause PID collisions.

The Mercure plugin provides `MockPublisher` for tests:

```php
use Mercure\Publisher;
use Mercure\TestSuite\MockPublisher;

Publisher::setInstance(new MockPublisher());
```

Reset the publisher with `Publisher::clear()` in teardown. Test both database progress and the update payload, and check the authorized browser subscription against a real hub before deploying.
