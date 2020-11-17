<?php

namespace App\Repositories;

use App\Models\SubCategory;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class CategoryRepository
 * @package App\Repositories
 * @version April 11, 2020, 1:57 pm UTC
 *
 * @method SubCategory findWithoutFail($id, $columns = ['*'])
 * @method SubCategory find($id, $columns = ['*'])
 * @method SubCategory first($columns = ['*'])
*/
class SubCategoryRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'description',
	    'category_id'
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return SubCategory::class;
    }
}
