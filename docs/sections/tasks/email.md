# Using built-in Email task

The Email task provides a flexible way to send emails asynchronously. It uses CakePHP's Message class and supports both simple and templated emails.

## Basic usage

### Sending a plain text email

```php
$data = [
    'settings' => [
        'to' => $user->email,
        'from' => Configure::read('Config.adminEmail'),
        'subject' => $subject,
    ],
    'content' => $content,
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Email', $data);
```

This will send a plain email. Each settings key must have a matching setter method on the Message class.
The prefix `set` will be auto-added here when calling it.

### Sending a templated email

For templated emails, pass view vars instead of content:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'from' => Configure::read('Config.adminEmail'),
        'subject' => $subject,
        'template' => 'my_template', // Uses templates/email/html/my_template.php
    ],
    'vars' => [
        'user' => $user,
        'myEntity' => $myEntity,
    ],
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Email', $data);
```

## Advanced settings

### Recipients with names

Email addresses can be specified with names:
```php
'settings' => [
    'to' => [$user->email, $user->username],
    'from' => ['admin@example.com', 'Site Admin'],
],
```

### Multiple recipients

For fields like `to` and `cc`, you can specify multiple recipients:
```php
'settings' => [
    'cc' => [
        ['user1@example.com', 'User One'],
        ['user2@example.com', 'User Two'],
    ],
],
```
Or using associative arrays:
```php
'settings' => [
    'cc' => [
        [
            'copy@test.de' => 'Your Name',
            'copy-other@test.de' => 'Your Other Name',
        ],
    ],
],
```

### Adding helpers

Add view helpers using the `helper` (single) or `helpers` (multiple) keys:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'subject' => $subject,
        'template' => 'my_template',
        'helpers' => [['Shim.Configure']],
    ],
    'vars' => [...],
];
```

### Custom layout and theme

You can customize the layout and theme for your email templates:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'subject' => $subject,
        'template' => 'my_template',
        'layout' => 'custom_layout',
        'theme' => 'MyTheme',
    ],
    'vars' => [...],
];
```

### Adding attachments

Attachments can be added using the `attachments` key:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'subject' => 'Document attached',
        'attachments' => [
            'filename.pdf' => [
                'file' => '/path/to/file.pdf',
                'mimetype' => 'application/pdf',
            ],
            'another.txt' => '/path/to/another.txt', // Mimetype auto-detected
        ],
    ],
    'content' => 'Please see attached files.',
];
```

### Custom headers

Add custom email headers:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'subject' => $subject,
    ],
    'headers' => [
        'X-Custom-Header' => 'value',
        'X-Priority' => '1',
    ],
    'content' => $content,
];
```

### Overriding transport

You can override the default transport for a specific email:
```php
$data = [
    'settings' => [...],
    'content' => $content,
    'transport' => 'smtp', // Use a different transport configuration
];
```

## Using Message objects

### Passing a serialized Message object (recommended)

You can assemble a Message object manually and pass it as a serialized array:
```php
use Cake\Mailer\Message;
use Queue\Queue\Task\EmailTask;

$message = new Message();
$message->setTo($user->email)
    ->setFrom('admin@example.com')
    ->setSubject('Subject')
    ->setBody('Message body');

$data = [
    'class' => Message::class,
    'settings' => EmailTask::serialize($message), // or $message->__serialize()
    'serialized' => true,
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Email', $data);
```

The Message object is serialized to an array, which is safe for database storage and survives code changes.

### Passing a PHP-serialized Message object (deprecated)

You can also pass the message as a PHP-serialized string:
```php
$data = [
    'class' => Message::class,
    'settings' => serialize($messageObject),
    'serialized' => true,
];
```

**Warning:** This is not recommended as it breaks when the underlying class changes during framework upgrades. Make sure all jobs are processed before upgrading your CakePHP version.


## Using custom Message class

If you have a custom Message class (extending `Cake\Mailer\Message`), you can use it by specifying the class:

```php
use App\Mailer\Message; // Your custom Message class

$data = [
    'class' => Message::class,
    'settings' => [
        'to' => $user->email,
        'from' => 'admin@example.com',
        'subject' => $subject,
    ],
    'content' => $content,
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Email', $data);
```

This approach is JsonSerializer safe and works well with the queue system.

## See also

- [QueueTransport configuration](../mailing.md#using-queuetransport) - Automatically queue all emails by changing transport configuration
- [Creating custom email tasks](../mailing.md#creating-custom-email-tasks) - For more complex email workflows
