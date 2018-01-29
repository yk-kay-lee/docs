<?php

namespace App\Services\Mileage\Models;

// DynamicModelはselect→read db, c-u-dはwrite dbに接続するためwarppingしたclass
use App\Services\Database\Eloquent\DynamicModel;

class MileageHistory extends DynamicModel
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
    protected $table = 'mileage_history';

    /**
     * Fillable fields for a Profile
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'account_key',
        'mileage',
        'mileage_rest',
        'description',
        'cause',
        'article_id',
        'campaign_id',
        'invite_id',
        'promotion_id',
        'limit'
    ];

}
