<?php
class QueuedTasksController extends AppController {
  public $name = 'QueuedTasks';
  public function progress() {
    if (isset($this->params['named'])) {
      $options['conditions'] = array_intersect_key($this->params['named'], array_flip(array('group', 'since')));
    }
    $last_checked = time();
    $tasks = $this->QueuedTask->find('progress', $options);
    $progress = compact('tasks', 'last_checked');
    $this->set(compact('progress'));
  }
}
?>