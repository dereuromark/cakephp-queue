# Installation

Composer checks the package's supported PHP and CakePHP versions.

```sh
composer require dereuromark/cakephp-queue:dev-cake6
bin/cake plugin load Queue
```

For CLI-only use, load the plugin with `--no-routes` instead. If you load plugins in `Application::bootstrap()`, use `$this->addPlugin('Queue')` after the parent call instead of the load command.

## Database schema

The plugin needs `queued_jobs` and `queue_processes` tables. If Migrations is not already installed:

```sh
composer require cakephp/migrations:dev-fix/6.x-cakephp-compatibility
bin/cake plugin load Migrations
```

Apply all Queue migrations to your application's default connection:

```sh
bin/cake migrations migrate -p Queue
```

For a separate queue database, migrate that connection with `-c connection_name` and follow [Multi-connection](./multi-connection). Run migrations during deployment and after plugin upgrades.

The web application and CLI worker must use the same database configuration. POSIX and PCNTL extensions enable process signals on supported systems; check those extensions when using worker termination or wake-up controls.

Continue with [Getting started](./getting-started). The optional [admin interface](../admin/) denies access until you configure its callback.
