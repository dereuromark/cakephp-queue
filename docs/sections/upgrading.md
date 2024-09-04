# Upgrading from older versions

## Coming from v7 to v8?
- Make sure you ran `bin/cake migrations migrate -p Queue` to migrate DB schema for all previous migrations before upgrading to v8.
- Once upgraded also run it once more, there should be now only 1 migration left.
- Make sure you are not using PHP serialize anymore, it is now all JSON. It is also happening automatically behind the scenes, so remove your
  manual calls where they are not needed anymore.

  That includes the config
  ```
  'serializerClass' => ..., // FQCN
  ```
  etc

## Coming from before v7?
If you are upgrading from Cake3/4 and v5/v6, make sure to install v7 firs, run all migrations,
then jump to v8.
