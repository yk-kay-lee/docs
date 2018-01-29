<?php
/**
 * EloquentRepository
 *
 * PHP version 7
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */
namespace App\Services\Mileage\Repositories;

use Carbon\Carbon;
use App\Services\Mileage\MileageRepository;
use App\Services\Mileage\Models\Mileage;
use App\Services\Mileage\Models\MileageHistory;
use App\Services\Mileage\Models\MileageMaster;
use App\Models\User;

/**
 * Class EloquentRepository
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */
class EloquentRepository implements MileageRepository
{
    /**
     * Model class
     *
     * @var string
     */
    protected $model = Mileage::class;

    protected $modelHistory = MileageHistory::class;

    protected $modelMaster = MileageMaster::class;

    /**
     * EloquentRepository constructor.
     *
     */
    public function __construct()
    {
    }

    /**
     * Find user mileage
     *
     * @param string $userId        user identifier
     * @param string $accountKey    account identifier
     * @param array  $with relation
     * @return Mileage
     */
    public function find($userId, $accountKey, $with = [])
    {
        $with    = !is_array($with) ? [$with] : $with;
        $mileage = $this->createModel()->newQuery()->with($with)->where('user_id', $userId)->first();
        if (empty($mileage)) {
            $mileage = $this->createModel()
                            ->create([
                                'user_id'     => $userId,
                                'account_key' => $accountKey,
                                'mileage'     => 0,
                                'rank'        => 1,
                            ]);
        }

        return $mileage;
    }

    /**
     * Find user mileage history
     *
     * @param string $userId    user identifier
     * @param array  $with      relation
     * @return MileageHistory
     */
    public function findHistory($userId, $with = [])
    {
        $with    = !is_array($with) ? [$with] : $with;
        $history = $this->createHistoryModel()->newQuery()->with($with)->where('user_id', $userId)->get();

        return $history;
    }

    /**
     * Find user mileage sum
     *
     * @param string $userId    userId
     * @param array  $option    param option : today, month
     * @return MileageHistory
     */
    public function findHistorySum($userId, $option)
    {
        switch ($option) {
            case 'today':
                $compare = '=';
                $limit   = 1;
                $date    = Carbon::today()->toDateString();
                break;
            case 'month':
                $compare = '>=';
                $limit   = 0;
                $date    = Carbon::now()->startOfMonth();
                break;
            default:
                $compare = '=';
                $limit   = 1;
                $date    = Carbon::today()->toDateString();
                break;
        }

        $result = $this->createHistoryModel()
                       ->newQuery()
                       ->where('user_id', $userId)
                       ->where('limit', $compare, $limit)
                       ->whereDate('created_at', $compare, $date)
                       ->get()->sum('mileage');

        return $result;
    }

    /**
     * get user mileage history article
     *
     * @param string $userId     userId
     * @param string $articleId  article id
     * @return MileageHistory
     */
    public function findHistoryArticle($userId, $articleId)
    {
        $result = $this->createHistoryModel()
                       ->newQuery()
                       ->where('user_id', $userId)
                       ->where('article_id', $articleId)
                       ->whereDate('created_at', '=', Carbon::today()->toDateString())
                       ->get();
        return $result;
    }

    /**
     * get user mileage history by cause
     *
     * @param string $userId     userId
     * @param string $cause      cause
     * @return MileageHistory
     */
    public function findHistoryByCause($userId, $cause)
    {
        $result = $this->createHistoryModel()
                       ->newQuery()
                       ->where('user_id', $userId)
                       ->where('cause', $cause)
                       ->get();
        return $result;
    }

    /**
     * create user mileage history
     *
     * @param string $data          params
     * @return MileageHistory
     */
    public function createHistory(array $data = [])
    {
        User::find($data['user_id'])->touch();
        $result = $this->createHistoryModel()
                       ->create([
                           'user_id'      => $data['user_id'],
                           'account_key'  => $data['account_key'],
                           'mileage'      => $data['mileage_add'],
                           'mileage_rest' => $data['mileage_rest'],
                           'description'  => $data['description'],
                           'cause'        => $data['cause'],
                           'article_id'   => $data['article_id'],
                           'campaign_id'  => $data['campaign_id'],
                           'invite_id'    => $data['invite_id'],
                           'promotion_id' => $data['promotion_id'],
                           'limit'        => $data['limit'],
                       ]);
        return $result;
    }

    /**
     * Find mileage master
     *
     * @return MileageMaster
     */
    public function findMaster()
    {
        $master = $this->createMasterModel()->newQuery()->get();
        $result = [];
        foreach ($master as $val) {
            $result[$val->cause]['daily_limit'] = $val->daily_limit;
            $result[$val->cause]['mileage']     = $val->mileage;
        }

        return $result;
    }

    /**
     * Update user mileage
     *
     * @param string $userId    user identifier
     * @param string $data      update params data
     * @return bool
     */
    public function update($userId, array $data = [])
    {
        $result = $this->createModel()
                       ->newQuery()
                       ->where('user_id', $userId)
                       ->update($data);

        return $result;
    }

    /**
     * Create new mileage model
     *
     * @return Mileage
     */
    public function createModel()
    {
        $class = $this->model;

        return new $class;
    }

    /**
     * Create new mileage history model
     *
     * @return MileageHistory
     */
    public function createHistoryModel()
    {
        $class = $this->modelHistory;

        return new $class;
    }

    /**
     * Create new mileage master model
     *
     * @return MileageMaster
     */
    public function createMasterModel()
    {
        $class = $this->modelMaster;

        return new $class;
    }

    /**
     * Generate new key
     *
     * @return string
     */
    protected function generateNewId()
    {
        $newId = substr($this->keygen->generate(), 0, 8);

        if (!preg_match('/[^0-9]/', $newId)) {
            $newId = $this->generateNewId();
        }

        return $newId;
    }
}
