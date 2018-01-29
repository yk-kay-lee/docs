<?php
/**
 * Mileage Repository
 *
 * PHP version 7
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */

namespace App\Services\Mileage;

/**
 * レポジトリで提供する機能を定義
 *
 * @category  Mileage
 * @package   App\Services\Mileage
 */
interface MileageRepository
{
    /**
     * Find user mileage
     *
     * @param string $userId        user identifier
     * @param string $accountKey    account identifier
     * @param array  $with      relation
     * @return Mileage
     */
    public function find($userId, $accountKey, $with = []);

    /**
     * Find user mileage history
     *
     * @param string $userId    user identifier
     * @param array  $with      relation
     * @return MileageHistory
     */
    public function findHistory($userId, $with = []);

    /**
     * Find user mileage sum
     *
     * @param string $userId    userId
     * @param array  $option    param options : today, month
     * @return MileageHistory
     */
    public function findHistorySum($userId, $option);

    /**
     * get user mileage article
     *
     * @param string $userId     userId
     * @param string $articleId  article id
     * @return MileageHistory
     */
    public function findHistoryArticle($userId, $articleId);

    /**
     * get user mileage history by cause
     *
     * @param string $userId     userId
     * @param string $cause      cause
     * @return MileageHistory
     */
    public function findHistoryByCause($userId, $cause);

    /**
     * get user mileage article
     *
     * @param string $data          params
     * @return MileageHistory
     */
    public function createHistory(array $data = []);

    /**
     * Find mileage master
     *
     * @return MileageMaster
     */
    public function findMaster();

    /**
     * Update user mileage
     *
     * @param string $userId    user identifier
     * @param string $data      update params data
     * @return bool
     */
    public function update($userId, array $data = []);

    /**
     * Create new mileage model
     *
     * @return Mileage
     */
    public function createModel();

    /**
     * Create new mileage history model
     *
     * @return MileageHistory
     */
    public function createHistoryModel();

    /**
     * Create new mileage master model
     *
     * @return MileageMaster
     */
    public function createMasterModel();

}
