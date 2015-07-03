<?php

namespace Cron;

/**
 * Day of week field.  Allows: / ,
 *
 * Days of the week can be represented as a number 0-7 (0|7 = Sunday)
 * or as a three letter string: SUN, MON, TUE, WED, THU, FRI, SAT.
 *
 * '/' is allowed for the day-of-week field, and must be followed by a
 * number between one and 30. It allows you to set rules like
 * 'monday and friday of every 10th week'
 */
class WeekField extends AbstractField
{
	public function isSatisfiedBy(\DateTime $date, $value) {}

    public function isSatisfiedByDay(\DateTime $initialDate, \DateTime $date, $value, $invert)
    {
        // Convert text day of the week values to integers
        $value = $this->convertLiterals($value);

		list($weekdays, $nth) = explode('/', $value);
		$weekdays = explode(',', $weekdays);

		foreach($weekdays as $key => $weekday) {
			// 0 and 7 are both Sunday, however 7 matches date('N') format ISO-8601
			if ($weekday === '0') {
				$weekdays[$key] = 7;
				continue;
			}
			$weekdays[$key] = (int)$weekday;

			// Validate the hash fields
			if ($weekdays[$key] < 0 || $weekdays[$key] > 7) {
				throw new \InvalidArgumentException("Weekday must be a value between 0 and 7. {$weekdays[$key]} given");
			}
		}

		$weekdaysDatesInitial = [];
		foreach($weekdays as $weekday) {
			$weekdayDateTime = clone $initialDate;
			$weekdayDateTime->setTime(0,0,0);
			$weekdayDiff = $weekday - $initialDate->format('N');
			if($weekdayDiff < 0) {
				$weekdayDiff += 7;
			}

			$weekdaysDatesInitial[$weekday] = $weekdayDateTime->modify(($weekdayDiff > 0 ? '+' : '') . $weekdayDiff . 'day');
		}

		while(1) {
			if(in_array($date->format('N'), $weekdays)) {
				$interval = $date->diff($weekdaysDatesInitial[$date->format('N')]);

				if($interval->days % (7 * $nth) == 0) {
					return true;
				} else {
					$this->increment($date, $invert);
				}
			} else {
				$this->increment($date, $invert);
			}
		}

		return false;
    }

    public function increment(\DateTime $date, $invert = false)
    {
        if ($invert) {
            $date->modify('-1 day');
            $date->setTime(23, 59, 0);
        } else {
            $date->modify('+1 day');
            $date->setTime(0, 0, 0);
        }

        return $this;
    }

    public function validate($value)
    {
        $value = $this->convertLiterals($value);
		return (bool) preg_match('/^(([0-7],)*([0-7])(\/[1-30]))$/', $value);
    }

    private function convertLiterals($string)
    {
        return str_ireplace(
            array('SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'),
            range(0, 6),
            $string
        );
    }
}
