# Mailer task

`Queue.Mailer` runs an action on a CakePHP Mailer class. Queue a class name, action, and JSON-safe arguments; the worker calls the action and sends the resulting mail.

## Define the mailer

Create `src/Mailer/UserMailer.php`:

```php
namespace App\Mailer;

use Cake\Mailer\Mailer;

class UserMailer extends Mailer {
    public function welcome(array $user): void {
        $this->setFrom('no-reply@example.com')
            ->setTo($user['email'])
            ->setSubject('Welcome')
            ->setEmailFormat('html')
            ->setViewVars(['user' => $user]);
        $this->viewBuilder()->setTemplate('welcome');
    }

    public function passwordReset(array $user, string $resetUrl): void {
        $this->setFrom('no-reply@example.com')
            ->setTo($user['email'])
            ->setSubject('Reset your password')
            ->setEmailFormat('html')
            ->setViewVars(['user' => $user, 'resetUrl' => $resetUrl]);
        $this->viewBuilder()->setTemplate('password_reset');
    }
}
```

Use your application's sender address. Create the corresponding templates in `templates/email/html/`, and configure the transport used by the worker. Template variables in this example are arrays, not entities.

## Queue an email

From a controller:

```php
use App\Mailer\UserMailer;

$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Mailer', [
    'class' => UserMailer::class,
    'action' => 'welcome',
    'vars' => [['email' => $user->email, 'name' => $user->name]],
]);
```

`vars` is a positional argument list. The example's first argument is a plain array. Do not rely on an entity object retaining its class after JSON storage. For data that must be current when sending, pass an ID and fetch the record in your mailer or a custom task.

## Multiple arguments and transport

```php
$queuedJobsTable->createJob('Queue.Mailer', [
    'class' => UserMailer::class,
    'action' => 'passwordReset',
    'vars' => [
        ['email' => $user->email],
        $resetUrl,
    ],
    'transport' => 'smtp',
]);
```

Generate `$resetUrl` with your application logic and configure the named `smtp` transport. Without an override, the task selects `default` before running the mailer action; the action can change it.

Failures throw back to the worker and follow the configured task retry policy. A delivery can succeed before a worker crashes, so a retry can send the message again. See [Limitations](../reference/limitations).

For a message assembled from settings or serialized data, use the [Email task](./email). For queue transports, see [Mailing](../guide/mailing).
