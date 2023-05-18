<?php
declare(strict_types=1);


namespace Richbuilds\Orm\Model;

use DateTime;

/**
 * A date only datatype
 */
class Date extends DateTime
{
    /**
     * @inheritDoc
     */
    public function __construct(string $date = 'now', mixed $timezone = null)
    {
        // Extract the date part from the input string to ensure it only contains date information
        $date_only = (new DateTime($date, $timezone))->format('Y-m-d');
        parent::__construct($date_only);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        // Display the date in the 'Y-m-d' format
        return $this->format('Y-m-d');
    }

}