<?php
/**
 * Mileage class for Facade
 *
 * PHP version 7
 *
 * @category    Mileage
 * @package     App\Services\Mileage
 */
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * facadeで利用するためのクラス
 *
 * @category    Mileage
 * @package     App\Services\Mileage
 */
class Mileage extends Facade
{

    /**
     * facade access keyword
     *
     * @return stirng
     */
    protected static function getFacadeAccessor()
    {
        return 'point.mileage';
    }
}
