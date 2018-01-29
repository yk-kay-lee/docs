<?php
/**
 * CacheDecorator
 *
 * PHP version 7
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */
namespace App\Services\Mileage\Repositories;

use App\Services\Mileage\MileageRepository;
use App\Services\Support\CacheInterface;

/**
 * Class CacheDecorator
 * レポジトリをラッピングしてcacheにある情報は
 * レポジトリに要請されず、cacheで返すようにする
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */
class CacheDecorator implements MileageRepository
{
    /**
     * MileageRepository instance
     *
     * @var MileageRepository
     */
    protected $repo;

    /**
     * Cache instance
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Prefix for cache key
     *
     * @var string
     */
    protected $prefix = 'mileage';

    /**
     * CACHE Constant Key
     *
     * @var string
     */
    const CACHE_KEY_USER = '@';

    const CACHE_KEY_HISTORY = '_history@';

    const CACHE_KEY_MASTER = '_master';

    const CACHE_KEY_HISTORY_SUM_TODAY = '_history_sum_today@';

    const CACHE_KEY_HISTORY_SUM_MONTH = '_history_sum_month@';

    const CACHE_KEY_HISTORY_ARTICLE = '_history_article@';

    const CACHE_KEY_HISTORY_CAUSE = '_history_cause@';

    /**
     * CacheDecorator constructor.
     *
     * @param MileageRepository $repo  MileageRepository instance
     * @param CacheInterface    $cache Cache instance
     */
    public function __construct(MileageRepository $repo, CacheInterface $cache)
    {
        $this->repo  = $repo;
        $this->cache = $cache;
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
        $key    = $this->getCacheKey(self::CACHE_KEY_USER, $userId);
        $subKey = $this->getWithKey($with);

        $data = $this->cache->get($key);

        if (!$data || !isset($data[$subKey])) {
            if ($mileage = $this->repo->find($userId, $accountKey, $with)) {
                $data          = $data ?: [];
                $data[$subKey] = $mileage;
                $this->cache->put($key, $data);
            }
        } else {
            $mileage = $data[$subKey];
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
        $key    = $this->getCacheKey(self::CACHE_KEY_HISTORY, $userId);
        $subKey = $this->getWithKey($with);

        $data = $this->cache->get($key);

        if (!$data || !isset($data[$subKey])) {
            if ($history = $this->repo->findHistory($userId, $with)) {
                $data          = $data ?: [];
                $data[$subKey] = $history;
                $this->cache->put($key, $data);
            }
        } else {
            $history = $data[$subKey];
        }
        //dump('cache');
        //dump($history);
        return $history;
    }

    /**
     * Find user mileage sum
     *
     * @param string $userId    userId
     * @param array  $option    param option
     * @return MileageHistory
     */
    public function findHistorySum($userId, $option)
    {
        switch ($option) {
            case 'today':
                $coupler = self::CACHE_KEY_HISTORY_SUM_TODAY;
                break;
            case 'month':
                $coupler = self::CACHE_KEY_HISTORY_SUM_MONTH;
                break;
            default:
                $coupler = self::CACHE_KEY_HISTORY_SUM_TODAY;
                break;
        }
        $keyword = $userId . '_' . $this->getDateString('Y-m-d');
        // $this->cache->forget($this->getCacheKey(self::CACHE_KEY_HISTORY_SUM_TODAY, $keyword));
        // $this->cache->forget($this->getCacheKey(self::CACHE_KEY_HISTORY_SUM_MONTH, $keyword));
        $key = $this->getCacheKey($coupler, $keyword);

        $data = $this->cache->get($key);

        if (!$data) {
            if ($history = $this->repo->findHistorySum($userId, $option)) {
                $data = $data ?: [];
                $data = $history;
                $this->cache->put($key, $data);
            }
        } else {
            $history = $data;
        }

        return $history;
    }

    /**
     * get user mileage article
     *
     * @param string $userId     userId
     * @param string $articleId  article id
     * @return MileageHistory
     */
    public function findHistoryArticle($userId, $articleId)
    {
        $keyword = $userId . '_' . $this->getDateString('Y-m-d');
        $key  = $this->getCacheKey(self::CACHE_KEY_HISTORY_ARTICLE . $articleId, $keyword);
        $data = $this->cache->get($key);

        if (!$data) {
            if ($history = $this->repo->findHistoryArticle($userId, $articleId)) {
                $data = $data ?: [];
                $data = $history;
                $this->cache->put($key, $data);
            }
        } else {
            $history = $data;
        }

        return $history;
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
        $keyword = $userId . '_' . $this->getDateString('Y-m-d');
        $key  = $this->getCacheKey(self::CACHE_KEY_HISTORY_CAUSE . $cause, $keyword);
        $data = $this->cache->get($key);

        if (!$data) {
            if ($history = $this->repo->findHistoryByCause($userId, $cause)) {
                $data = $data ?: [];
                $data = $history;
                $this->cache->put($key, $data);
            }
        } else {
            $history = $data;
        }

        return $history;
    }

    /**
     * get user mileage article
     *
     * @param string $data          params
     * @return MileageHistory
     */
    public function createHistory(array $data = [])
    {
        $keyword = $data['user_id'] . '_' . $this->getDateString('Y-m-d');
        $this->cache->forget($this->getCacheKey(self::CACHE_KEY_HISTORY_ARTICLE . $data['article_id'], $keyword));
        $this->cache->forget($this->getCacheKey(self::CACHE_KEY_HISTORY_CAUSE . $data['cause'], $keyword));
        $this->cache->forget($this->getCacheKey(self::CACHE_KEY_HISTORY_SUM_TODAY, $keyword));
        $this->cache->forget($this->getCacheKey(self::CACHE_KEY_HISTORY_SUM_MONTH, $keyword));
        $result = $this->repo->createHistory($data);
        return $result;
    }

    /**
     * Find mileage master
     *
     * @return MileageMaster
     */
    public function findMaster()
    {
        $key  = $this->getCacheKey(self::CACHE_KEY_MASTER);
        $data = $this->cache->get($key);

        if (!$data) {
            if ($master = $this->repo->findMaster()) {
                $data = $data ?: [];
                $data = $master;
                $this->cache->put($key, $data);
            }
        } else {
            $master = $data;
        }

        return $master;
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
        $this->cache->forget($this->getCacheKey(self::CACHE_KEY_USER, $userId));

        return $this->repo->update($userId, $data);
    }

    /**
     * Create new mileage model
     *
     * @return Mileage
     */
    public function createModel()
    {
        return $this->repo->createModel();
    }

    /**
     * Create new mileage history model
     *
     * @return MileageHistory
     */
    public function createHistoryModel()
    {
        return $this->repo->createHistoryModel();
    }

    /**
     * Create new mileage master model
     *
     * @return MileageMaster
     */
    public function createMasterModel()
    {
        return $this->repo->createMasterModel();
    }

    /**
     * String for mileage cache key
     *
     * @param string $coupler coupler
     * @param string $keyword keyword
     * @return string
     */
    protected function getCacheKey($coupler, $keyword = '')
    {
        return $this->prefix . $coupler . $keyword;
    }

    /**
     * Get sub key by relationship
     *
     * @param array $with relationships
     * @return string
     */
    private function getWithKey($with = [])
    {
        $with = !is_array($with) ? [$with] : $with;

        if (empty($with)) {
            return '_alone';
        }

        return implode('.', $with);
    }

    /**
     * Get date string by format
     *
     * @param array $format ex : Y-m-d
     * @return date
     */
    private function getDateString($format)
    {
        return date($format);
    }
}
