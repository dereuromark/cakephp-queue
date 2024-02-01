# Mailing

## Using built-in tasks
* [Email](tasks/email.md) using Message class
* [Mailer](tasks/mailer.md) using Mailer class

## Using QueueTransport

Instead of manually adding job every time you want to send mail
you can use existing code ond change only EmailTransport and Email configurations in `app.php`.

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
