# Upgrading from older versions

## Coming from v6 to v7?
- Run `bin/cake migrations migrate -p Queue` to migrate DB schema for table `queued_jbos` from `failed` to `attempts`.
- The `config/app_queue.php` file is not loaded by default anymore. Either load it yourself or transfer the config to your `config/app_local.php`.
