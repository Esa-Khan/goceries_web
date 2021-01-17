<?php
/**
 * File name: RestaurantsOfUserCriteria.php
 * Last modified: 2020.04.30 at 08:24:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Criteria\Foods;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $store_ids = [];
        if ($this->request->get('isStore') == 'true') {
            $store_ids = DB::table('restaurants')
                ->where('restaurants.information', 'S')
                ->pluck('restaurants.id')
                ->toArray();
        } else {
            $store_ids = DB::table('restaurants')
                ->where('restaurants.information', 'R')
                ->pluck('restaurants.id')
                ->toArray();
        }

        return $model->select('foods.*')->whereIn('foods.restaurant_id', $store_ids);
    }
}
