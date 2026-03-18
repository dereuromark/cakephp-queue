<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $tasks
 */
?>
<div class="row">
	<div class="col-lg-8">
		<div class="card">
			<div class="card-header">
				<i class="fas fa-exchange-alt me-2"></i><?= __d('queue', 'Migrate v3 to v4') ?>
			</div>
			<div class="card-body">
				<div class="alert alert-info">
					<i class="fas fa-info-circle me-2"></i>
					<?= __d('queue', 'This will update job task names from the old v3 format to the new v4 format.') ?>
				</div>

				<?= $this->Form->create() ?>
				<div class="table-responsive">
					<table class="table table-hover">
						<thead>
							<tr>
								<th style="width: 50px;"><?= __d('queue', 'Migrate') ?></th>
								<th><?= __d('queue', 'Old Name') ?></th>
								<th><?= __d('queue', 'New Name') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($tasks as $name => $fullName): ?>
								<tr>
									<td>
										<?= $this->Form->checkbox('tasks.' . $name, ['default' => true, 'class' => 'form-check-input']) ?>
									</td>
									<td><code><?= h($name) ?></code></td>
									<td><code><?= h($fullName) ?></code></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="mt-3">
					<?= $this->Form->button('<i class="fas fa-sync me-1"></i>' . __d('queue', 'Migrate Selected'), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
				</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
