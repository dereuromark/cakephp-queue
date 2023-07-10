# Contributing

I am looking forward to your contributions.

There are a few guidelines that I need contributors to follow:
* Coding standards (`composer cs-check` to check and `composer cs-fix` to fix)
* PHPStan (`composer phpstan`, might need `composer phpstan-setup` first)
* Passing tests (`php phpunit.phar`)


## Testing MySQL

By default, it will usually use SQLite DB (out of the box available).
Not all tests currently work with SQLite or any non MySQL db yet.

If you want to run all tests, including MySQL ones, you need to set
```
export DB_CLASS=Mysql
export DB_URL="mysql://root:yourpwd@127.0.0.1/cake_test"
```
before you actually run
```
vendor/bin/phpunit
```

Make sure such a `cake_test` database exists.

## Updating Locale POT file

Run this from your app dir to update the plugin's `queue.pot` file:
```
bin/cake i18n extract --plugin Queue --extract-core=no --merge=no --overwrite
```
