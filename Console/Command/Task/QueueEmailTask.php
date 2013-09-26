<?php
/**
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @uses Tools.EmailLib
 */
App::uses('EmailLib', 'Tools.Lib');
App::uses('AppShell', 'Console/Command');

class QueueEmailTask extends AppShell {

	/**
	 * List of default variables for EmailComponent
	 *
	 * @var array
	 */
	public $defaults = array(
		'to' => null,
		'from' => null,
	);
	public $timeout = 120;

	public $retries = 0;

	public $Email;

	public function add() {
		$this->err('Queue Email Task cannot be added via Console.');
		$this->out('Please use createJob() on the QueuedTask Model to create a Proper Email Task.');
		$this->out('The Data Array should look something like this:');
		$this->out(var_export(array(
			'settings' => array(
				'to' => 'email@example.com',
				'subject' => 'Email Subject',
				'from' => 'system@example.com',
				'template' => 'sometemplate'
			),
			'vars' => array(
				'content' => 'hello world',
			)
		), true));
	}

	public function run($data) {
		$this->Email = new EmailLib();

		if (!isset($data['settings'])) {
			$this->err('Queue Email task called without settings data.');
			return false;
		}
		$settings = array_merge($this->defaults, $data['settings']);
		foreach ($settings as $method => $setting) {
			call_user_func_array(array($this->Email, $method), (array)$setting);
		}
		$message = null;
		if (!empty($data['vars'])) {
			if (isset($data['vars']['content'])) {
				$message = $data['vars']['content'];
			}
			$this->Email->viewVars($data['vars']);
		}
		return $this->Email->send($message);
	}

}
