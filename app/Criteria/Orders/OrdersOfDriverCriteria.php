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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class OrdersOfUserCriteria.
 *
 * @package namespace App\Criteria\Orders;
 */
class OrdersOfDriverCriteria implements CriteriaInterface
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

        $isWorking = false;
        $isManager = DB::table('users')
            ->where('users.id', $this->request->get('driver_id'))
            ->pluck('users.isManager')
            ->first();
        if (isset($request['check_workhours']) && $request['check_workhours'] == false) {
            $isWorking = true;
        } else {
            $work_hours = DB::table('drivers')
                ->where('drivers.user_id', $this->request->get('driver_id'))
                ->pluck('drivers.work_hours', 'drivers.store_ids')
                ->first();


            if ($work_hours == '24/7') {
                $isWorking = true;
            } else {
                $current_time = date("H:i:s");
                $work_hours = explode('|', $work_hours);
                $start_time = date('H:i:s',strtotime($work_hours[0]));
                $end_time = date('H:i:s',strtotime($work_hours[1]));

                if ($current_time >= $start_time && $current_time <= $end_time) {
                    $isWorking = true;
                }
            }
        }

        if ($isWorking) {
            if ($isManager) {
                return $model->where('orders.order_status_id', '<', 5)
                                ->orderBy('orders.order_status_id', 'desc')
                                ->orderBy('orders.id', 'asc')
                                ->select('orders.*');

            } else {
                return $model->where([
                                    ['orders.driver_id', '=', $this->request->get('driver_id')],
                                    ['orders.order_status_id', '<', 5]
                                ])
                                ->orWhere('orders.driver_id', '1')
                                ->orderBy('orders.order_status_id', 'desc')
                                ->orderBy('orders.id', 'asc')
                                ->select('orders.*');
            }
        } else {
            if ($isManager) {
                return $model->whereIn('orders.order_status_id', [2,3,4])
                                ->orderBy('orders.order_status_id', 'desc')
                                ->orderBy('orders.id', 'asc')
                                ->select('orders.*');

            } else {
                return $model->where([
                                    ['orders.driver_id', '=', $this->request->get('driver_id')],
                                    ['orders.order_status_id', '<>', 5],
                                    ['orders.order_status_id', '<>', 1],
                                ])
                                ->orderBy('orders.order_status_id', 'desc')
                                ->orderBy('orders.id', 'asc')
                                ->select('orders.*');
            }
        }





    }
}
