<?php

namespace App\Repositories;

use App\Models\UsedPromoCode;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class OrderRepository
 * @package App\Repositories
 * @version August 31, 2019, 11:11 am UTC
 *
 * @method UsedPromoCode findWithoutFail($id, $columns = ['*'])
 * @method UsedPromoCode find($id, $columns = ['*'])
 * @method UsedPromoCode first($columns = ['*'])
*/
class UsedPromoCodeRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'user_id' => 'integer',
        'code_used' => 'string',
        'timestamp' => 'string',
        'number' => 'string',
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return UsedPromoCode::class;
    }
}
