<?php
/**
 * MileageManager
 *
 * PHP version 7
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */

namespace App\Services\Mileage;

use App\Services\Mileage\Exceptions\InvalidArgumentException;

/**
 * Class MileageManager
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */
class MileageManager
{
    /**
     * MileageRepository instance
     *
     * @var MileageRepository
     */
    protected $repo;

    /**
     * MileageManager constructor.
     *
     * @param MileageRepository  $repo    MileageRepository instance
     */
    public function __construct(MileageRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * mileage update
     *
     * @param string $actionId          action id
     * @param string $articleId         article id
     * @param string $campaignId        campaign id
     * @param string $userId            user identifier
     * @param string $accountKey        account identifier
     * @param string $promotionValue    promotion code用mileage
     * @return array
     */
    public function updateMileage($actionId, $articleId, $campaignId, $userId, $accountKey, $promotionValue = 0)
    {
        // Logic
    }

    /**
     * get point rank
     *
     * @param int     $point          current point
     * @return int
     */
    public function getMileageRank($point)
    {
        // Logic
    }

    /**
     * get point next rank
     *
     * @param int     $point          current point
     * @return int
     */
    public function getMileageNextRank($point)
    {
        // Logic
    }

    /**
     * get user mileage
     *
     * @param string $userId        user identifier
     * @param string $accountKey    account identifier
     * @return Mileage
     */
    public function getUser($userId, $accountKey)
    {
        // Logic
    }

    /**
     * get user mileage sum
     *
     * @param string $userId     userId
     * @param string $option     option : today, month
     * @return MileageHistory
     */
    public function getHistorySum($userId, $option)
    {
        $result = $this->repo->findHistorySum($userId, $option);
        return $result;
    }

    /**
     * get user mileage article
     *
     * @param string $userId     userId
     * @param string $articleId  article id
     * @return MileageHistory
     */
    public function getHistoryArticle($userId, $articleId)
    {
        if (!$userId || !$articleId) {
            throw new InvalidArgumentException(['name' => 'parameters']);
        }
        $result = $this->repo->findHistoryArticle($userId, $articleId);
        return $result;
    }

    /**
     * get user mileage history by cause
     *
     * @param string $userId     userId
     * @param string $cause      cause
     * @return MileageHistory
     */
    public function getHistoryByCause($userId, $cause)
    {
        // Logic
    }

    /**
     * get user mileage history
     *
     * @param string $userId     userId
     * @return Mileage
     */
    public function getHistory($userId)
    {
        // Logic
    }

    /**
     * get user mileage master
     *
     * @return MileageMaster
     */
    public function getMaster()
    {
        // Logic
    }

    /**
     * update user mileage
     *
     * @param string $userId    user identifier
     * @param array $data      update params data
     * @return bool
     */
    public function putUser($userId, array $data)
    {
        // Logic
    }

    /**
     * insert user mileage history
     *
     * @param array $data      insert params data
     * @return MileageHistory
     */
    public function putUserMileageHistory(array $data)
    {
        // Logic
    }

}
