<?php
/**
 * File name: OrdersOfUserCriteria.php
 * Last modified: 2020.04.30 at 08:24:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Criteria\Orders;

use App\Models\User;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class OrdersOfUserCriteria.
 *
 * @package namespace App\Criteria\Orders;
 */
class OrdersHistoryCriteria implements CriteriaInterface
{
    /**
     * @var User
     */
    protected Request $request;

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

        $isManager = DB::table('users')
            ->where('users.id', $this->request->get('driver_id'))
            ->pluck('users.isManager')
            ->first();

        $date = new DateTime("now");
        $date->sub(new DateInterval('P1D'));

        if ($isManager) {
            return $model->where('orders.order_status_id', 5)
                            ->whereDate('updated_at', '>', $date )
                            ->orderBy('orders.id', 'asc')
                            ->select('orders.*');

        } else {
            return $model->where('orders.order_status_id', 5)
                            ->where('orders.driver_id', $this->request->get('driver_id'))
                            ->orderBy('orders.id', 'asc')
                            ->select('orders.*');
        }



    }
}
