# Upgrading from older versions

## New Features
### Events for job lifecycle (v8.4+)
A new event `Queue.Job.maxAttemptsExhausted` is now dispatched when a job has failed all of its configured retry attempts. This allows you to implement custom handling for permanently failed jobs, such as sending notifications or logging to external services. See the [Events section](/reference/misc#events) for usage details.

### ExecuteTask security hardening
`Queue.Execute` now escapes the `command` and each `params` entry per token via `escapeshellarg()` instead of `escapeshellcmd()`, so arguments cannot break out into additional shell tokens (argument injection). It also enforces a `Queue.executeAllowedCommands` allow-list whenever `debug` is disabled: the `command` value MUST appear in the list verbatim, otherwise the task throws before `exec()`. With debug off and the list empty/unset, every Execute job is rejected.

Two follow-ups for callers:

- Callers that previously embedded multiple tokens inside a single `params` entry (e.g. `'params' => ['importer run']`) must split such entries across the array (e.g. `'params' => ['importer', 'run']`). Equivalent: move the leading subcommand into `command`.
- Production deployments must populate `Queue.executeAllowedCommands` in `config/app.php` (or `app_local.php`) with the exact `command` strings the app is allowed to execute. See `config/app.example.php` and the [Execute task docs](/tasks/execute).

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
