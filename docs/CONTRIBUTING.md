## Testing MySQL

By default it will usually use SQLite DB (out of the box available).
Not all tests currently work with SQLite or any non MySQL db yet.

If you want to run all tests, including MySQL ones, you need to set
```
export db_dsn="mysql://root:yourpwd@127.0.0.1/cake_test"
```
before you actually run
```
php phpunit.phar
```

Make sure such a `cake_test` database exists.
