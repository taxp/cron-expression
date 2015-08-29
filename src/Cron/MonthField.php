<?php

namespace Cron;

use DateTime;

/**
 * Month field.  Allows: * , / -
 */
class MonthField extends AbstractField
{
    public function isSatisfiedBy(DateTime $date, $value)
    {
        // Convert text month values to integers
        $value = str_ireplace(
            array(
                'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
                'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'
            ),
            range(1, 12),
            $value
        );

        return $this->isSatisfied($date->format('m'), $value);
    }

	public function isSatisfiedByDay(\DateTime $initialDate, \DateTime $date, $value, $invert)
	{
		list(, $nth) = explode('/', $value);

		while(1) {
			$currentDate = clone $date;
			$initDate = clone $initialDate;

			$m = $currentDate->format('m');
			$Y = $currentDate->format('Y');
			$currentDate->setDate($Y , $m , 1);
			$currentDate->setTime(0, 0);

			$m = $initDate->format('m');
			$Y = $initDate->format('Y');
			$initDate->setDate($Y , $m , 1);
			$initDate->setTime(0, 0);

			$interval = $currentDate->diff($initialDate);

			if((($interval->y * 12) + $interval->m) % $nth == 0) {
				return true;
			} else {
				$this->increment($date, $invert);
			}
		}

		return false;
	}

	public function increment(DateTime $date, $invert = false)
    {
        if ($invert) {
            $date->modify('last day of previous month');
            $date->setTime(23, 59);
        } else {
            $date->modify('first day of next month');
            $date->setTime(0, 0);
        }

        return $this;
    }

    public function validate($value)
    {
        return (bool) preg_match('/^[\*,\/\-0-9A-Z]+$/', $value);
    }
}
