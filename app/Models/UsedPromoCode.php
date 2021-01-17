<?php
namespace App\Models;

use DateTime;
use Eloquent as Model;

/**
 * Class Order
 * @package App\Models
 * @version August 31, 2019, 11:11 am UTC
 *
 * @property string id
 * @property int user_id
 * @property string code_used
 * @property dateTime timestamp
 */
class UsedPromoCode extends Model
{

    public $table = 'used_promo_codes';

    public $fillable = [
        'user_id',
        'code_used',
        'phone_number',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'code_used' => 'string',
        'timestamp' => 'string',
        'phone_number' => 'string',
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }


}
