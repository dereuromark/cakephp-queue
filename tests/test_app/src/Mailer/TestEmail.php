<?php

namespace App\Mailer;

use Tools\Mailer\Email;

class TestEmail extends Email {

	/**
	 * @return array|null
	 */
	public function debug() {
		return $this->_debug;
	}

}
