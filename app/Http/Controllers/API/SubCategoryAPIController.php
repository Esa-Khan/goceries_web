<?php
/**
 * File name: CategoryAPIController.php
 * Last modified: 2020.05.04 at 09:04:18
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Repositories\CategoryRepository;
use App\Repositories\SubCategoryRepository;
use Flash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class CategoryController
 * @package App\Http\Controllers\API
 */
class SubCategoryAPIController extends Controller
{
    /** @var  CategoryRepository */
    private $subcategoryRepository;

    public function __construct(SubCategoryRepository $subcategoryRepo)
    {
        $this->subcategoryRepository = $subcategoryRepo;
    }

    /**
     * Display a listing of the Category.
     * GET|HEAD /subcategories
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $subcategories = $this->subcategoryRepository->all();

        return $this->sendResponse($subcategories->toArray(), 'Sub-Categories retrieved successfully');
    }

    /**
     * Display the specified Category.
     * GET|HEAD /categories/{id}
     *
     * @param  int $id
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        /** @var Category $category */
        if (!empty($this->subcategoryRepository)) {
            $category = $this->subcategoryRepository->findWithoutFail($id);
        }

        if ($category === null) {
            return $this->sendError('Category not found');
        }

        return $this->sendResponse($category->toArray(), 'Category retrieved successfully');
    }

    /**
     * Store a newly created Category in storage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->subcategoryRepository->model());
        try {
            $category = $this->subcategoryRepository->create($input);
            $category->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($category, 'image');
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        return $this->sendResponse($category->toArray(), __('lang.saved_successfully',['operator' => __('lang.category')]));
    }

    /**
     * Update the specified Category in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function update($id, Request $request)
    {
        $category = $this->subcategoryRepository->findWithoutFail($id);

        if (empty($category)) {
            return $this->sendError('Category not found');
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->subcategoryRepository->model());
        try {
            $category = $this->subcategoryRepository->update($input, $id);

            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($category, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $category->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($category->toArray(), __('lang.updated_successfully',['operator' => __('lang.category')]));

    }

    /**
     * Remove the specified Category from storage.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $category = $this->subcategoryRepository->findWithoutFail($id);

        if (empty($category)) {
            return $this->sendError('Category not found');
        }

        $category = $this->subcategoryRepository->delete($id);

        return $this->sendResponse($category, __('lang.deleted_successfully',['operator' => __('lang.category')]));
    }

//***********************************************************************

    /**
     * Display the specified Category.
     * GET|HEAD /categories/{id}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function getSubcatFromCat(int $id, Request $request)
    {
        $storeID = $request['storeID'];

        $used_cat_ids = DB::table('categories')
                            ->where('foods.restaurant_id', $storeID)
                            ->join('foods', 'foods.category_id', '=', 'categories.id')
                            ->groupBy('categories.id')
                            ->pluck('categories.id')
                            ->toArray();

        $subcategories = SubCategory::whereIn('id', $used_cat_ids)
                                        ->where('category_id', $id)
                                        ->get()
                                        ->toArray();


        if ($subcategories === null || count($subcategories) === 0) {
            return $this->sendError('Subcategory not found');
        }

        return $this->sendResponse($subcategories, 'SubCategory retrieved successfully');
    }

}
