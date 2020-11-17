<?php
/**
 * File name: CategoriesOfCuisinesCriteria.php
 * Last modified: 2020.05.04 at 09:04:18
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Criteria\Categories;

use App\Models\Category;
use App\Models\Food;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class CategoriesOfCuisinesCriteria.
 *
 * @package namespace App\Criteria\Categories;
 */
class MainCategoriesCriteria implements CriteriaInterface
{

    /**
     * @var Request
     */
    /**
     * @var Request
     */
    private Request $request;

    /**
     * CategoriesOfCuisinesCriteria constructor.
     * @param Request $request
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
     * @param $productCategory
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        //SELECT * FROM categories WHERE id IN (SELECT DISTINCT category_id FROM foods WHERE restaurant_id=20);
//        $a = $model->select('categories.*')
//            ->where('foods.restaurant_id', $this->request->get('storeID'))
//            ->join('foods', 'foods.category_id', '=', 'categories.id')
//            ->groupBy('categories.id')->merge($a);
        return $model->select('categories.*')->where('categories.id', '<', 100);


    }
}
