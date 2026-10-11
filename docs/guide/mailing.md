# Mailing

Use the [Email task](/tasks/email) for message payloads or the [Mailer task](/tasks/mailer) for reusable Mailer actions. Both require a configured transport that can send mail from the worker environment.

## Using QueueTransport

QueueTransport stores an already rendered message as a job. Configure a separate delivery transport so the worker sends it instead of queuing it again:

```php
// config/app_local.php: use your SMTP provider's credentials.
'EmailTransport' => [
    'default' => [
        'className' => 'Smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => env('SMTP_USERNAME'),
        'password' => env('SMTP_PASSWORD'),
        'tls' => true,
    ],
    'queue' => [
        'className' => 'Queue.Queue',
        'transport' => 'default',
    ],
],
'Email' => [
    'default' => [
        'transport' => 'queue',
        'from' => 'no-reply@example.com',
    ],
],
```

Calls to `deliver()` using this mail configuration create jobs. A running Queue worker delivers them through `default`. Successful queuing does not confirm delivery.

### QueueTransport and SimpleQueueTransport

QueueTransport serializes the complete Message, including its rendered body. SimpleQueueTransport extracts message settings and body data for reconstruction by the Email task. Templates have already rendered before either transport receives the Message. Neither transport removes the need to fit the payload into your database's `data` column.

For SimpleQueueTransport, use `Queue.SimpleQueue` as `className`, with the same separate delivery transport.

## Creating custom email tasks

Pass record IDs when the worker should load current data and render the email later. For example, after saving a comment:

```php
use Cake\ORM\TableRegistry;

$queuedJobs = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobs->createJob('CommentNotification', ['comment_id' => $comment->id]);
```

Create `src/Queue/Task/CommentNotificationTask.php`:

```php
namespace App\Queue\Task;

use Cake\Mailer\Mailer;
use Queue\Queue\Task;

class CommentNotificationTask extends Task
{
    public function run(array $data, int $jobId): void
    {
        $comment = $this->fetchTable('Comments')->get($data['comment_id']);
        $mailer = new Mailer();
        $mailer->setTransport('default')
            ->setFrom('no-reply@example.com')
            ->setTo('admin@example.com')
            ->setSubject('New comment')
            ->setEmailFormat('html')
            ->setViewVars(['comment' => $comment]);
        $mailer->viewBuilder()->setTemplate('comment_notification');
        $mailer->deliver();
    }
}
```

Use your application's real sender, recipient and delivery transport. The task throws if the comment has been deleted; decide whether deleted records should instead be skipped in your application.

Create `templates/email/html/comment_notification.php`:

```php
<p><?= h($comment->name) ?> (<?= h($comment->email) ?>) wrote:</p>
<p><?= nl2br(h($comment->message)) ?></p>
```

A retry can send the email again if the worker fails after delivery but before marking the job complete. Account for that when designing notifications; see [limitations](/reference/limitations).
