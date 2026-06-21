<?php
declare(strict_types=1);

namespace Queue\Model\Enum;

/**
 * Priority levels for queued jobs. 1 is the highest priority, 10 is the lowest.
 */
enum Priority: int {
	case Critical = 1;
	case Urgent = 2;
	case High = 3;
	case MediumHigh = 4;
	case Medium = 5;
	case MediumLow = 6;
	case Low = 7;
	case VeryLow = 8;
	case Deferred = 9;
	case Idle = 10;
}
