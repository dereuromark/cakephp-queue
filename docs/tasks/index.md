# Built-in Tasks

The Queue plugin ships with three ready-to-use tasks for the most common asynchronous jobs.

| Task | When to use |
| --- | --- |
| [Execute](/tasks/execute) | Run a shell command (e.g. `bin/cake importer run`) asynchronously, with allow-listing and per-token escaping. |
| [Email](/tasks/email) | Send an email built from `Cake\Mailer\Message` settings — most flexible, supports attachments, custom headers, custom Message classes. |
| [Mailer](/tasks/mailer) | Send an email via a reusable `Cake\Mailer\Mailer` class action — best for standardized emails (welcome, password reset, etc.). |

For writing your own task class, see [Custom Tasks](/guide/custom-tasks).
