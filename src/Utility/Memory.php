<?php

namespace Queue\Utility;

class Memory {

	/**
	 * @return int
	 */
	public static function usage(): int {
		return (int)(memory_get_peak_usage(true) / (1024 * 1024));
	}

}
