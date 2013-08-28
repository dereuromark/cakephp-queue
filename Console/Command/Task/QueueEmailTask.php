<?php
App::uses('EmailLib', 'Tools.Lib');
App::uses('AppShell', 'Console/Command');

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Tasks
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 * @see http://bakery.cakephp.org/articles/view/emailcomponent-in-a-cake-shell
 */
class QueueEmailTask extends AppShell {

	/**
	 * List of default variables for EmailComponent
	 *
	 * @var array
	 */
	public $defaults = array(
		'to' => null,
		'subject' => null,
		'charset' => 'UTF-8',
		'from' => null,
		'sendAs' => 'html',
		'template' => null,
		'debug' => false,
		'additionalParams' => '',
		'layout' => 'default'
	);

	public $timeout = 120;

	public $retries = 0;

	/**
	 * Controller class
	 *
	 * @var Controller
	 */
	public $Controller;

	/**
	 * EmailComponent
	 *
	 * @var EmailComponent
	 */
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
				'text' => 'hello world'
			)
		), true));
	}

	/**
	 * QueueEmailTask::run()
	 *
	 * @param array $data
	 * @return boolean Success
	 */
	public function run($data) {
		$this->Email = new EmailLib();

		# prep
		if (array_key_exists('settings', $data)) {
			foreach ($data as $key => $val) {
				if (method_exists($this->Email, $key)) {
					$this->Email->{$key}($val);
				}
				//$this->Email->set(array_filter(array_merge($this->defaults, $data['settings'])));
			}
		}
		if (array_key_exists('vars', $data)) {
			$this->Email->viewVars($data['vars']);
		}

		if (array_key_exists('settings', $data)) {
			return $this->Email->send();
		}
		$this->err('Queue Email task called without settings data.');
		return false;
	}

}
