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

This will send a plain email. Address settings are passed to the Mailer. Template, layout, theme and helper settings configure its view builder; other settings call the matching `set...()` method.

### Sending a templated email

For templated emails, pass view vars instead of content:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'from' => Configure::read('Config.adminEmail'),
        'subject' => $subject,
        'template' => 'my_template',
        'emailFormat' => 'html', // Uses templates/email/html/my_template.php
    ],
    'vars' => [
        'user' => $user->toArray(),
        'myEntity' => $myEntity->toArray(),
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
        'user1@example.com' => 'User One',
        'user2@example.com' => 'User Two',
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
    'vars' => ['message' => 'Hello'],
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
    'vars' => ['message' => 'Hello'],
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
    'settings' => ['to' => 'reader@example.com', 'from' => 'admin@example.com'],
    'content' => $content,
    'transport' => 'smtp', // Use a different transport configuration
];
```

## Using Message objects

### Passing a serialized Message object (recommended)

You can assemble a Message object manually and pass it as a serialized array:
```php
use Cake\Mailer\Message;
use Cake\ORM\TableRegistry;
use Queue\Queue\Task\EmailTask;

$message = new Message();
$message->setTo($user->email)
    ->setFrom('admin@example.com')
    ->setSubject('Subject')
    ->setBodyText('Message body');

$data = [
    'class' => Message::class,
    'settings' => EmailTask::serialize($message), // or $message->__serialize()
    'serialized' => true,
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Email', $data);
```

The message is stored as an array. Process pending messages before framework upgrades that change the serialized message format. Template variables also become JSON data; use arrays and scalar values rather than expecting entity objects in the worker.

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


## Using a custom Message class

Build the complete message before serializing it. When `class` is supplied, the task sends the Message directly through the transport; separate `content`, `vars` and `headers` payload keys are not applied.

```php
use App\Mailer\Message;
use Cake\ORM\TableRegistry;
use Queue\Queue\Task\EmailTask;

$message = new Message();
$message->setTo('reader@example.com')
    ->setFrom('admin@example.com')
    ->setSubject('Notification')
    ->setBodyText('Your notification is ready.');

$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Email', [
    'class' => Message::class,
    'settings' => EmailTask::serialize($message),
    'serialized' => true,
]);
```

## See also

- [QueueTransport configuration](/guide/mailing#using-queuetransport) - Automatically queue all emails by changing transport configuration
- [Creating custom email tasks](/guide/mailing#creating-custom-email-tasks) - For more complex email workflows
