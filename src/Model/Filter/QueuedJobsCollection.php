<?php
declare(strict_types=1);

namespace Queue\Model\Filter;

use Cake\Http\Exception\NotImplementedException;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Search\Model\Filter\FilterCollection;

class QueuedJobsCollection extends FilterCollection {

	/**
	 * @return void
	 */
	public function initialize(): void {
		$this
			->value('job_task')
			->like('search', [
				'before' => true,
				'after' => true,
				'field' => ['job_group', 'reference', 'status'],
			])
			->add('status', 'Search.Callback', [
				'callback' => function (SelectQuery $query, array $args, $filter) {
					$status = $args['status'];
					if ($status === 'completed') {
						$query->where(['completed IS NOT' => null]);

						return true;
					}
					if ($status === 'in_progress') {
						$query->where([
							'completed IS' => null,
							'OR' => [
								'notbefore <=' => new DateTime(),
								'notbefore IS' => null,
							],
						]);

						return true;
					}
					if ($status === 'scheduled') {
						$query->where(['completed IS' => null, 'notbefore >' => new DateTime()]);

						return true;
					}

					throw new NotImplementedException('Invalid status type');
				},
			]);
	}

}
