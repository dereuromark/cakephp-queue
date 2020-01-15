<?php

namespace App\Mailer;

use Tools\Mailer\Mailer;

class TestMailer extends Mailer {

	/**
	 * @return array|null
	 */
	public function debug() {
		return $this->_debug;
	}

}
