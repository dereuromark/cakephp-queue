# Basic Setup

## Installation
```
composer require dereuromark/cakephp-queue
```
Load the plugin in your `src/Application.php`'s bootstrap() using:
```php
$this->addPlugin('Queue');
```
If you don't want to also access the backend controller (just using CLI), you need to use
```php
$this->addPlugin('Queue', ['routes' => false]);
```

Important: Make sure to use authentication if you are using the backend. You do not want visitors to be able to browse it.

## Database migration

Run the following command in the CakePHP console to create the tables using the Migrations plugin:
```sh
bin/cake migrations migrate -p Queue
```

Hint: use a native *nix-like or console and not the one provided like from Git (git-bash). This may lead to a non-working `migrations` command.
It is also advised to have the `posix` PHP extension enabled.
