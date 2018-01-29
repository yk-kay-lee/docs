<?php

namespace App\Services\Mileage\Models;

// DynamicModelはselect→read db, c-u-dはwrite dbに接続するためwarppingしたclass
use App\Services\Database\Eloquent\DynamicModel;

class MileageMaster extends DynamicModel
{
    /**
     * The connection name for the model.
     * Virtual connection name.
     *
     * @var string
     */
    protected $connection = 'mileage';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'mileage_master';

    /**
     * Fillable fields for a Profile
     *
     * @var array
     */
    protected $fillable = [
        'cause',
        'article_id',
        'description',
        'option',
        'mileage',
        'daily_limit',
    ];

}
