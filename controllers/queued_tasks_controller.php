<?php
class QueuedTasksController extends AppController {
  public $name = 'QueuedTasks';
  public function progress() {
    if (isset($this->params['named'])) {
      $options['conditions'] = array_intersect_key($this->params['named'], array_flip(array('group', 'exclude')));
    }
    $tasks = $this->QueuedTask->find('progress', $options);
    $this->set(compact('tasks'));
  }
}
?>