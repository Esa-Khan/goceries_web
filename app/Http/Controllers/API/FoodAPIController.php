<?php
/**
 * File name: FoodAPIController.php
 * Last modified: 2020.05.04 at 09:04:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers\API;


use App\Criteria\Foods\FoodsOfCategoryCriteria;
use App\Criteria\Foods\NearCriteria;
use App\Criteria\Foods\FoodsOfCuisinesCriteria;
use App\Criteria\Foods\TrendingWeekCriteria;
use App\Criteria\Foods\FoodsOfRestaurantCriteria;
use App\Criteria\Foods\RestaurantsOrStoreCriteria;
use App\Http\Controllers\Controller;
use App\Models\Food;
use App\Repositories\CustomFieldRepository;
use App\Repositories\FoodRepository;
use App\Repositories\UploadRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use PhpOffice\PhpSpreadsheet\Exception;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class FoodController
 * @package App\Http\Controllers\API
 */
class FoodAPIController extends Controller
{
    /** @var  FoodRepository */
    private $foodRepository;
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;
    /**
     * @var UploadRepository
     */
    private $uploadRepository;


    public function __construct(FoodRepository $foodRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo)
    {
        parent::__construct();
        $this->foodRepository = $foodRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
    }

    /**
     * Display a listing of the Food.
     * GET|HEAD /foods
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

        try{
            $this->foodRepository->pushCriteria(new RequestCriteria($request));
            $this->foodRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->foodRepository->pushCriteria(new FoodsOfCuisinesCriteria($request));
	        if (isset($request['restaurant_id'])) {
                $this->foodRepository->pushCriteria(new FoodsOfRestaurantCriteria($request['restaurant_id']));
            }
            if (isset($request['category_id'])) {
                $this->foodRepository->pushCriteria(new FoodsOfCategoryCriteria($request['category_id']));
            }

            if ($request->has('isStore'))
                $this->foodRepository->pushCriteria(new RestaurantsOrStoreCriteria($request));

//            $this->foodRepository->orderBy('closed');
//            $this->foodRepository->orderBy('area');

            if (isset($request['short'])){
                $foods = $this->foodRepository->all(['id', 'name', 'price', 'discount_price', 'quantity', 'description', 'ingredients', 'weight',
                    'featured', 'deliverable', 'category_id', 'commission']);
            } else {
                $foods = $this->foodRepository->all();
            }

            if (isset($request['id'])){
                $range = explode( '-', $request['id'], 2);
                $itemsInRange = array();
                foreach ($foods as $currFood){
                    $currID = (int)$currFood['id'];
                    if ($currID > (int)$range[1]) {
                        break;
                    } else if ($currID >= (int)$range[0] && $currID <= (int)$range[1]) {
                        $itemsInRange[] = $currFood;
                    }
                }
                $foods = $itemsInRange;
             }

            foreach ($foods as $currFood){
                $this->getImageURL($currFood);
            }
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($foods, 'Foods retrieved successfully');
    }

    /**
     * Display the specified Food.
     * GET|HEAD /foods/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var Food $food */
        if (!empty($this->foodRepository)) {
            try{
                $this->foodRepository->pushCriteria(new RequestCriteria($request));
                $this->foodRepository->pushCriteria(new LimitOffsetCriteria($request));
            } catch (RepositoryException $e) {
                return $this->sendError($e->getMessage());
            }
            $food = $this->foodRepository->findWithoutFail($id);

            $this->getImageURL($food);
        }

        if (empty($food)) {
            return $this->sendError('Food not found');
        }

        return $this->sendResponse($food->toArray(), 'Food retrieved successfully');
    }

    /**
     * Store a newly created Food in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->foodRepository->model());
        try {
            $food = $this->foodRepository->create($input);
            $food->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($food, 'image');
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($food->toArray(), __('lang.saved_successfully', ['operator' => __('lang.food')]));
    }

    /**
     * Update the specified Food in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $food = $this->foodRepository->findWithoutFail($id);
//        return $request;
        if (empty($food)) {
            return $this->sendError('Food not found');
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->foodRepository->model());
        try {
            $food = $this->foodRepository->update($input, $id);
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($food, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $food->customFieldsValues()->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($food->toArray(), __('lang.updated_successfully', ['operator' => __('lang.food')]));

    }

    /**
     * Remove the specified Food from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $food = $this->foodRepository->findWithoutFail($id);
        if (empty($food)) {
            return $this->sendError('Food not found');
        }
        $food = $this->foodRepository->delete($id);

        return $this->sendResponse($food, __('lang.deleted_successfully', ['operator' => __('lang.food')]));

    }


    /**
     * Remove the specified Food from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function getSimilarItems(int $id)
    {
        try {
            $item = DB::table('foods')
                        ->select('name', 'restaurant_id')
                        ->where('foods.id', $id)
                        ->first();

            $comparison_str = explode(' ', $item->name)[0] . '%';

            $items = Food::where('foods.restaurant_id', $item->restaurant_id)
                    ->where('foods.id', '<>', $id)
                    ->where('name', 'like', $comparison_str)
                    ->get();
            $count = 5;
            $final_items = [];
            foreach ($items as $curr_item) {
                if ($count === 0) break;
                $count--;
                $this->getImageURL($curr_item);
                $curr_item->featured = $curr_item->featured === true;
                $curr_item->deliverable = $curr_item->deliverable === true;
                $final_items[] = $curr_item;
            }

            return $this->sendResponse($final_items, 'Similar Items retrieved successfully');

        } catch (Exception $e) {
            return $this->sendResponse(null, 'Similar Items retrieved unsuccessfully');
        }
    }


    function searchInSubcat(Request $request) {
        try {

            $items = Food::where('category_id', $request['id'])
                        ->where('name', 'LIKE', '%'.$request['search'].'%')->get()->toArray();
            return $this->sendResponse($items, 'Searched Items retrieved successfully');

        } catch (Exception $e) {
            return $this->sendResponse(null, 'Searched Items retrieved unsuccessfully');
        }

    }

    private function getImageURL($food): Food
    {
        $LOCAL_PATH = substr($_SERVER['DOCUMENT_ROOT'], 0, -6);
        $filename = $LOCAL_PATH."storage/app/public/foods/".$food['id'].".jpg";

        if (file_exists($filename)){
            $food['image_url'] = "http://saudagharpk.com/storage/app/public/foods/".$food['id'].".jpg" ;
        }else{
            $food['image_url'] =  "http://saudagharpk.com/images/image_default.png";
        }
        return $food;
    }


}
