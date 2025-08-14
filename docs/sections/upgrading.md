# Upgrading from older versions

## New Features
### Events for job lifecycle (v8.4+)
A new event `Queue.Job.maxAttemptsExhausted` is now dispatched when a job has failed all of its configured retry attempts. This allows you to implement custom handling for permanently failed jobs, such as sending notifications or logging to external services. See the [Events section](misc.md#events) for usage details.

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
