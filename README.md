# CakePHP Queue Plugin
[![CI](https://github.com/dereuromark/cakephp-queue/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-queue/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-queue/master.svg)](https://codecov.io/github/dereuromark/cakephp-queue/branch/master)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-queue/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-queue)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.5-8892BF.svg)](https://php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://poser.pugx.org/dereuromark/cakephp-queue/license.svg)](LICENSE)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-queue/d/total)](https://packagist.org/packages/dereuromark/cakephp-queue)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)

This development branch targets CakePHP 6. See Composer for its dependency requirements.


Queue stores background jobs in your application's database and processes them with CLI workers. Tasks support retries, delayed execution, progress, and optional admin tools.


See the [documentation](https://dereuromark.github.io/cakephp-queue/) for [installation](https://dereuromark.github.io/cakephp-queue/guide/installation), [getting started](https://dereuromark.github.io/cakephp-queue/guide/getting-started), and the full reference.

## Demo
See [Sandbox app](https://sandbox.dereuromark.de/sandbox/queue-examples).

## Installation and Usage
Full documentation: https://dereuromark.github.io/cakephp-queue/

## Cronjob based background scheduling
If you are looking for scheduling certain background jobs: Use [QueueScheduler plugin](https://github.com/dereuromark/cakephp-queue-scheduler).
