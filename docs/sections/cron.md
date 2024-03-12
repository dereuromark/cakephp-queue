# Setting up the trigger cronjob

As outlined in the [book](https://book.cakephp.org/5/en/console-commands/cron-jobs.html#running-shells-as-cron-jobs) you can easily set up a cronjob
to start a new worker.

The following example uses "crontab":

    */10  *  *  *  *  cd /full/path/to/app && bin/cake queue run -q

Make sure you use `crontab -e -u www-data` to set it up as `www-data` user, and not as root etc.

This would start a new worker every 10 minutes. If you configure your max lifetime of a worker to 15 minutes, you
got a small overlap where two workers would run simultaneously. If you lower the 10 minutes and raise the lifetime, you
get quite a few overlapping workers and thus more "parallel" processing power.
Play around with it, but just don't shoot over the top.

Also don't forget to set Configure key `'Queue.maxworkers'` to a reasonable value per server.
If, for any reason, some of the jobs should take way longer, you want to avoid additional x workers to be started.
It will then just not start new ones beyond this count until the already running ones are finished.
This is an important server protection to avoid overloading.
