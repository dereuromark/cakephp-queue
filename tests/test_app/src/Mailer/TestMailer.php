<?php
declare(strict_types=1);

namespace TestApp\Mailer;

use Tools\Mailer\Mailer;

class TestMailer extends Mailer {

	/**
	 * @param bool $isTest
	 * @return $this
	 */
	public function testAction(bool $isTest) {
		$this
			->setViewVars([
				'isTest' => $isTest,
			])
			->viewBuilder()
			->setTemplate('default');

		return $this;
	}

}
