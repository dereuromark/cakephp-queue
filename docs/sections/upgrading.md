# Upgrading from older versions

## Coming from v5 to v6?
- Migration here is provided using `bin/cake queue migrate_tasks`
  for the task classes.
  They will be renamed and moved to the new location.
  Also some upgrades of internals will be applied.
- `->getTableLocator()` usage has to be fixed to `->loadModel()` calls.
- Then run `bin/cake migrations migrate -p Queue` to migrate DB schema.
- Finally, go to `/admin/queue/queued-jobs/migrate` backend and fix up any old name to new one.

Don't forget to replace the crontab worker command to `bin/cake queue run`.
Same for ending currently running workers on deploy: `bin/cake queue worker end server`.
