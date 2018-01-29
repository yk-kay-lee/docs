<?php
/**
 * InvalidArgumentException
 *
 * PHP version 7
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */

namespace App\Services\Mileage\Exceptions;

use App\Services\Mileage\MileageException;

/**
 * Class InvalidArgumentException
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */
class InvalidArgumentException extends MileageException
{
    protected $message = '[:name] is invalid argument';
}
