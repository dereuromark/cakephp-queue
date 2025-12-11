<?php
declare(strict_types=1);

namespace Queue\Queue;

use Cake\Core\Configure;
use Queue\Model\Entity\QueuedJob;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\SpanStatus;
use Sentry\Tracing\Transaction;
use Sentry\Tracing\TransactionContext;
use Throwable;

/**
 * Sentry integration helper for queue monitoring.
 *
 * Provides automatic Sentry span creation when the Sentry SDK is available
 * and enabled via configuration.
 *
 * To enable, set `Queue.sentry` to `true` in your configuration:
 * ```php
 * Configure::write('Queue.sentry', true);
 * ```
 *
 * If Sentry is not installed or not enabled, all methods are no-ops.
 *
 * @see https://docs.sentry.io/platforms/php/tracing/instrumentation/queues-module/
 */
class SentryIntegration {

	/**
	 * @var \Sentry\Tracing\Transaction|null Current consumer transaction
	 */
	protected static ?Transaction $currentTransaction = null;

	/**
	 * @var float|null Job start time for latency calculation
	 */
	protected static ?float $jobStartTime = null;

	/**
	 * Check if Sentry SDK is available and enabled.
	 *
	 * @return bool
	 */
	public static function isAvailable(): bool {
		return Configure::read('Queue.sentry') === true && class_exists(SentrySdk::class);
	}

	/**
	 * Create a producer span when a job is added to the queue.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job The created job
     *
	 * @return void
	 */
	public static function startProducerSpan(QueuedJob $job): void {
		if (!static::isAvailable()) {
			return;
		}

		try {
			$hub = SentrySdk::getCurrentHub();
			$parentSpan = $hub->getSpan();

			if ($parentSpan === null) {
				return;
			}

			$context = SpanContext::make()
				->setOp('queue.publish')
				->setDescription($job->job_task);

			$span = $parentSpan->startChild($context);
			$hub->setSpan($span);

			$span->setData([
				'messaging.message.id' => (string)$job->id,
				'messaging.destination.name' => $job->job_task,
				'messaging.message.body.size' => static::getPayloadSize($job),
			]);

			$span->finish();
			$hub->setSpan($parentSpan);
		} catch (Throwable $e) {
			// Silently ignore Sentry errors to not disrupt queue operations
		}
	}

	/**
	 * Start a consumer transaction when a job begins processing.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job The job being processed
     *
	 * @return void
	 */
	public static function startConsumerTransaction(QueuedJob $job): void {
		if (!static::isAvailable()) {
			return;
		}

		try {
			static::$jobStartTime = microtime(true);

			// Try to continue trace from job data if available
			$sentryTrace = null;
			$baggage = null;
			if (is_array($job->data)) {
				$sentryTrace = $job->data['_sentry_trace'] ?? null;
				$baggage = $job->data['_sentry_baggage'] ?? null;
			}

			if ($sentryTrace !== null) {
				$context = \Sentry\continueTrace($sentryTrace, $baggage ?? '');
			} else {
				$context = TransactionContext::make();
			}

			$context->setOp('queue.process');
			$context->setName($job->job_task);

			$transaction = \Sentry\startTransaction($context);
			SentrySdk::getCurrentHub()->setSpan($transaction);

			static::$currentTransaction = $transaction;
		} catch (Throwable $e) {
			// Silently ignore Sentry errors
			static::$currentTransaction = null;
		}
	}

	/**
	 * Finish the consumer transaction when a job completes successfully.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job The completed job
     *
	 * @return void
	 */
	public static function finishConsumerSuccess(QueuedJob $job): void {
		static::finishConsumerTransaction($job, true);
	}

	/**
	 * Finish the consumer transaction when a job fails.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job The failed job
	 * @param string|null $failureMessage The error message
     *
	 * @return void
	 */
	public static function finishConsumerFailure(QueuedJob $job, ?string $failureMessage = null): void {
		static::finishConsumerTransaction($job, false, $failureMessage);
	}

	/**
	 * Finish the consumer transaction.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job The job
	 * @param bool $success Whether the job succeeded
	 * @param string|null $failureMessage Optional failure message
     *
	 * @return void
	 */
	protected static function finishConsumerTransaction(
		QueuedJob $job,
		bool $success,
		?string $failureMessage = null,
	): void {
		if (!static::isAvailable() || static::$currentTransaction === null) {
			return;
		}

		try {
			$transaction = static::$currentTransaction;

			if (!$success && class_exists('\Sentry\Tracing\SpanStatus')) {
				$transaction->setStatus(SpanStatus::internalError());
			}

			$receiveLatency = null;
			if ($job->fetched !== null && $job->created !== null) {
				$scheduledTime = $job->notbefore ?? $job->created;
				$receiveLatency = (float)$job->fetched->getTimestamp() - (float)$scheduledTime->getTimestamp();
			}

			$data = [
				'messaging.message.id' => (string)$job->id,
				'messaging.destination.name' => $job->job_task,
				'messaging.message.body.size' => static::getPayloadSize($job),
				'messaging.message.retry.count' => $job->attempts,
			];

			if ($receiveLatency !== null) {
				$data['messaging.message.receive.latency'] = $receiveLatency * 1000; // milliseconds
			}

			$transaction->setData($data);
			$transaction->finish();
		} catch (Throwable $e) {
			// Silently ignore Sentry errors
		} finally {
			static::$currentTransaction = null;
			static::$jobStartTime = null;
		}
	}

	/**
	 * Get trace headers to include in job data for trace propagation.
	 *
	 * @return array<string, string>
	 */
	public static function getTraceHeaders(): array {
		if (!static::isAvailable()) {
			return [];
		}

		try {
			$hub = SentrySdk::getCurrentHub();
			$span = $hub->getSpan();

			if ($span === null) {
				return [];
			}

			return [
				'_sentry_trace' => \Sentry\getTraceparent(),
				'_sentry_baggage' => \Sentry\getBaggage(),
			];
		} catch (Throwable $e) {
			return [];
		}
	}

	/**
	 * Calculate payload size in bytes.
	 *
	 * @param \Queue\Model\Entity\QueuedJob $job
     *
	 * @return int
	 */
	protected static function getPayloadSize(QueuedJob $job): int {
		if ($job->data === null) {
			return 0;
		}

		$encoded = json_encode($job->data);

		return $encoded !== false ? strlen($encoded) : 0;
	}

}
