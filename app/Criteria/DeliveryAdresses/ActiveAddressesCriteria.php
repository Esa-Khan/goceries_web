<?php

namespace App\Criteria\DeliveryAdresses;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class EarningOfRestaurantCriteriaCriteria.
 *
 * @package namespace App\Criteria\Earnings;
 */
class ActiveAddressesCriteria implements CriteriaInterface
{
    private $request;

    /**
     * EarningOfRestaurantCriteriaCriteria constructor.
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Apply criteria in query repository
     *
     * @param string              $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where("active", 1);
    }
}
