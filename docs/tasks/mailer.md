# Using built-in Mailer task

The Mailer task is designed for sending reusable, templated emails using CakePHP's Mailer classes. This approach is ideal when you have standardized email types that you send frequently throughout your application.

## What is a Mailer?

A Mailer class in CakePHP encapsulates email logic, templates, and configuration in a reusable way. Instead of configuring emails inline, you define actions in a Mailer class that handle specific email types.

See [CakePHP Mailer documentation](https://book.cakephp.org/5/en/core-libraries/email.html#sending-emails-using-mailer) for more information about creating Mailer classes.

## When to use Mailer task vs Email task

- **Use Mailer task** when you have reusable email types defined in Mailer classes (e.g., welcome emails, password resets, notifications)
- **Use Email task** when you need more flexibility or one-off emails with dynamic configuration

## Basic usage

Create a Mailer class (if you don't have one already):

```php
// src/Mailer/UserMailer.php
namespace App\Mailer;

use Cake\Mailer\Mailer;

class UserMailer extends Mailer
{
    public function welcome($user)
    {
        $this
            ->setTo($user->email)
            ->setSubject('Welcome to our site')
            ->setTemplate('welcome') // Uses templates/email/html/welcome.php
            ->setViewVars(['user' => $user]);
    }

    public function passwordReset($user, $token)
    {
        $this
            ->setTo($user->email)
            ->setSubject('Password Reset Request')
            ->setTemplate('password_reset')
            ->setViewVars([
                'user' => $user,
                'resetLink' => \Cake\Routing\Router::url([
                    'controller' => 'Users',
                    'action' => 'resetPassword',
                    $token,
                ], true),
            ]);
    }
}
```

Queue the email:

```php
use App\Mailer\UserMailer;

$data = [
    'class' => UserMailer::class,
    'action' => 'welcome',
    'vars' => [$user], // Arguments passed to the mailer action
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Mailer', $data);
```

Since we're passing a class string and settings (not objects), this is JsonSerializer safe.

## Advanced usage

### Passing multiple arguments

The `vars` array is passed as arguments to your Mailer action:

```php
$data = [
    'class' => UserMailer::class,
    'action' => 'passwordReset',
    'vars' => [$user, $resetToken], // Passed as passwordReset($user, $resetToken)
];
$queuedJobsTable->createJob('Queue.Mailer', $data);
```

### Overriding transport

You can override the default transport for a specific email:

```php
$data = [
    'class' => UserMailer::class,
    'action' => 'welcome',
    'vars' => [$user],
    'transport' => 'smtp', // Use a different transport configuration
];
$queuedJobsTable->createJob('Queue.Mailer', $data);
```

## Benefits

- **Reusability**: Define email templates and logic once, use everywhere
- **Testability**: Easier to test Mailer classes in isolation
- **Maintainability**: Centralized email configuration and templates
- **Type safety**: Can type-hint parameters in Mailer actions
- **Auto-retry**: Failed emails are automatically retried based on your queue configuration
