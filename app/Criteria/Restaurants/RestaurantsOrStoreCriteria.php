<?php
/**
 * File name: RestaurantsOfUserCriteria.php
 * Last modified: 2020.04.30 at 08:24:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Criteria\Restaurants;

use App\Models\User;
use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class RestaurantsOfUserCriteria.
 *
 * @package namespace App\Criteria\Restaurants;
 */
class RestaurantsOrStoreCriteria implements CriteriaInterface
{

    /**
     * @var User
     */
    private $isStore;

    /**
     * RestaurantsOfUserCriteria constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
        if ($this->request->get('isStore')) {
            return $model->select('restaurants.*')->where('restaurants.information', 'S');
        } else {
            return $model->select('restaurants.*')->where('restaurants.information', 'R');
        }
    }
}
