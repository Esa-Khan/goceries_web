<?php


namespace App\Criteria\Foods;


use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class FoodsOfRestaurantCriteria.
 *
 * @package namespace App\Criteria\Foods;
 */
class FoodsOfCategoryCriteria implements CriteriaInterface
{
    /**
     * @var int
     */
    private $categoryId;

    /**
     * FoodsOfRestaurantCriteria constructor.
     */
    public function __construct($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Apply criteria in query repository
     *
     * @param string $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where('category_id', '=', $this->categoryId);
    }
}
