# Using built-in Email task

The quickest and easiest way is to use the built-in Email task:
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

If you want a templated email, you need to pass view vars instead of content:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'from' => Configure::read('Config.adminEmail'),
        'subject' => $subject,
    ],
    'vars' => [
        'myEntity' => $myEntity,
        ...
    ],
];
 ```

Some keys also accept an array, e.g. to/from/cc can also include a name:
```php
        'to' => [$user->email, $user->username],
```
For some like to/cc you can even define multiple emails:
```php
        'cc' => [
            [$userOne->email, $userOne->username],
            [$userTwo->email, $userTwo->username],
            ...
        ],
```
Note that this needs an additional array nesting in this case.

You can add helpers using `helper` (single) or `helpers` (multiple) keys inside settings:
```php
$data = [
    'settings' => [
        ...
        'helpers' => [['Shim.Configure']],
    ],
    ...
];
 ```

You can also assemble a Message object manually and pass that along as serialized settings array directly:
```php
$data = [
    'class' => \Cake\Mailer\Message::class,
    'settings' => $messageObject->__serialize(),
    'serialized' => true,
];
```
You can also use the convenience method `EmailTask::serialize()` here.

It will not yet send emails here, only assemble them.
The Email Queue task triggers the `deliver()` method.

Or even pass it as serialized string:
```php
$data = [
    'class' => \Cake\Mailer\Message::class,
    'settings' => serialize($messageObject),
    'serialized' => true,
];
```
Deprecated: This is not recommended as it breaks as soon as the code changes.

Note: In this last case the object is stored PHP serialized in the DB.
This can break when upgrading your core and the underlying class changes.
So make sure to only upgrade your code when all jobs have been finished.


## Using custom Email class
If you are not using CakePHP core Email task:

The recommended way for Email task together with JsonSerializer is using the FQCN class string of the Message class:

```php
use App\Mailer\Message; // or your custom FQCN

$data = [
    'class' => Message::class,
    'settings' => $settings,
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Email', $data);
```

## Using QueueTransport

Instead of manually adding job every time you want to send mail you can use existing code ond change only EmailTransport and Email configurations in `app.php`.

```php
'EmailTransport' => [
    'default' => [
        'className' => 'Smtp',
        // The following keys are used in SMTP transports
        'host' => 'host@gmail.com',
        'port' => 587,
        'timeout' => 30,
        'username' => 'username',
        'password' => 'password',
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
        'from' => 'no-reply@host.com',
        'charset' => 'utf-8',
        'headerCharset' => 'utf-8',
    ],
],
```

This way each time with `$mailer->deliver()` it will use `QueueTransport` as main to create job and worker will use `'transport'` setting to send mail.

### Difference between QueueTransport and SimpleQueueTransport

* `QueueTransport` serializes whole email into the database and is useful when you have custom `Message` class.
* `SimpleQueueTransport` extracts all data from Message (to, bcc, template etc.) and then uses this to recreate Message inside task, this
  is useful when dealing with emails which serialization would overflow database `data` field length.
  This can only be used for non-templated emails.


## Manually assembling your emails

This is the most customizable way to generate your asynchronous emails.

Don't generate them directly in your code and pass them to the queue, instead just pass the minimum requirements, like non persistent data needed and the primary keys of the records that need to be included.
So let's say someone posted a comment, and you want to get notified.

Inside your CommentsTable class after saving the data you execute this hook:

```php
/**
 * @param \App\Model\Entity\Comment $comment
 * @return void
 */
protected function _notifyAdmin(Comment $comment)
{
    /** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
    $QueuedJobs = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
    $data = [
        'settings' => [
            'subject' => __('New comment submitted by {0}', $comment->name),
        ],
        'vars' => [
            'comment' => $comment->toArray(),
        ],
    ];
    $QueuedJobs->createJob('CommentNotification', $data);
}
```

And your `QueueAdminEmailTask::run()` method (using `MailerAwareTrait`):

```php
$this->getMailer('User');
$this->Mailer->viewBuilder()->setTemplate('comment_notification');
// ...
if (!empty($data['vars'])) {
    $this->Mailer->setViewVars($data['vars']);
}

$this->Mailer->deliver();
```

Make sure you got the template for it then, e.g.:

```php
<?= $comment->name ?> ( <?= $comment->email ?> ) wrote:

<?= $comment->message ?>

<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Comments', 'action'=> 'view', $comment['id']], true) ?>
```

This way all the generation is in the specific task and template and can be tested separately.
