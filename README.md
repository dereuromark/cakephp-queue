# CakePHP Queue Plugin
[![Build Status](https://api.travis-ci.com/dereuromark/cakephp-queue.svg?branch=master)](https://travis-ci.com/dereuromark/cakephp-queue)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-queue/master.svg)](https://codecov.io/github/dereuromark/cakephp-queue?branch=master)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-queue/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-queue)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-queue/license)](https://packagist.org/packages/dereuromark/cakephp-queue)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-queue/d/total)](https://packagist.org/packages/dereuromark/cakephp-queue)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

This branch is for use with **CakePHP 4.0+**. For details see [version map](https://github.com/dereuromark/cakephp-queue/wiki#cakephp-version-map).


## Background

This is a very simple and minimalistic job queue (or deferred-task) system for CakePHP.
If you need a very basic PHP internal queue tool, this is definitely an option.
It is also a great tool for demo purposes on how queues work and doesn't have any dependencies.

Overall functionality is inspired by systems like Gearman, Beanstalk or dropr, but without
any illusion to compete with these more advanced Systems.

The plugin is an attempt to provide a basic, simple to use method to enable deferred job execution,
without the hassle of setting up or running an extra queue daemon, while integrating nicely into
CakePHP and also simplifying the creation of worker scripts. You can also easily provide progress and status information into your pages.

Please also read the [blog post](https://www.dereuromark.de/2013/12/22/queue-deferred-execution-in-cakephp/).
For more high-volume and robust use cases please see the [awesome list](https://github.com/FriendsOfCake/awesome-cakephp#queue).

### Why use deferred execution?

Deferred execution makes sense (especially in PHP) when your page wants to execute tasks, which are not directly related to rendering the current page.
For instance, in a BBS-type system, a new users post might require the creation of multiple personalized email messages,
notifying other users of the new content.
Creating and sending these emails is completely irrelevant to the currently active user, and should not increase page response time.
Another example would be downloading, extraction and/or analyzing an external file per request of the user.
The regular solution to these problems would be to create specialized cronjobs which use specific database states to determine which action should be done.

The Queue plugin provides a simple method to create and run such non-user-interaction-critical tasks.

Another important reason is that specific jobs can be (auto)retried if they failed.
So if the email server didn't work the first time, or the API gateway had an issue, the current job to be executed isn't lost but kept for rerun. Most of those external services should be treated as failable once every x calls, and as such a queue implementation can help reducing issues due to such failures. If a job still can't finish despite retries, you still have the option to debug its payload and why this job cannot complete. No data is lost here.

While you can run multiple workers, and can (to some extent) spread these workers to different machines via a shared database, you should consider using a more advanced system for high volume/high number of workers systems.

## Demo
See [Sandbox app](https://sandbox.dereuromark.de/sandbox/queue-examples).

## Installation and Usage
See [Documentation](docs).
