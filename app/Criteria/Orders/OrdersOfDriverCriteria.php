<?php
/**
 * File name: OrdersOfUserCriteria.php
 * Last modified: 2020.04.30 at 08:24:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Criteria\Orders;

use App\Models\Driver;
use App\Models\User;
use DateInterval;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Prettus\Validator\Exceptions\ValidatorException;

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

        $isManager = User::where('users.id', $this->request->get('driver_id'))
                                ->pluck('isManager')
                                ->first();

        $work_hours = Driver::where('user_id', $this->request->get('driver_id'))
                                ->pluck('work_hours', 'store_ids')
                                ->first();

        try {
            if ($work_hours == '24/7') {
                $isWorking = true;
            } else {
                $current_time = new DateTime("now");
                $work_hours = explode('|', $work_hours);
                $start_time = new DateTime($work_hours[0]);

                $time = explode(':', $work_hours[1]);
                $end_time = new DateTime($work_hours[0]);
                $end_time->add(new DateInterval('PT' . $time[0] . 'H' . $time[1] . 'M' . $time[2] . 'S'));

                if ($current_time >= $start_time && $current_time <= $end_time) {
                    $isWorking = true;
                } else {
                    $start_time->sub(new DateInterval('P1D'));
                    $end_time->sub(new DateInterval('P1D'));
                    if ($current_time >= $start_time && $current_time <= $end_time) {
                        $isWorking = true;
                    } else {
                        $isWorking = false;
                    }
                }
            }
        } catch (\Exception $e) {
            return $model;
        }

        Driver::where('user_id', $this->request->get('driver_id'))
                    ->update(['available' => $isWorking]);


        if ($isWorking) {
            if ($isManager) {
                return $model->where('orders.order_status_id', '<', 5)
                                ->where('orders.active', 1)
                                ->orderBy('orders.order_status_id', 'desc')
                                ->orderBy('orders.id', 'asc')
                                ->select('orders.*');

            } else {
                return $model->where([
                                    ['orders.driver_id', '=', $this->request->get('driver_id')],
                                    ['orders.active', 1]])
                                ->orWhere([
                                    ['orders.order_status_id', '<', 5],
                                    ['orders.active', 1]])
                                ->orderBy('orders.order_status_id', 'desc')
                                ->orderBy('orders.id', 'asc')
                                ->select('orders.*');

            }
        } else {
            if ($isManager) {
                return $model->whereIn('orders.order_status_id', [2,3,4])
                                ->where('orders.active', 1)
                                ->orderBy('orders.order_status_id', 'desc')
                                ->orderBy('orders.id', 'asc')
                                ->select('orders.*');

            } else {
                return $model->where([
                                    ['orders.driver_id', '=', $this->request->get('driver_id')],
                                    ['orders.order_status_id', '<>', 5],
                                    ['orders.order_status_id', '<>', 1],
                                    ['orders.active', 1],
                                ])
                                ->orderBy('orders.order_status_id', 'desc')
                                ->orderBy('orders.id', 'asc')
                                ->select('orders.*');
            }
        }





    }
}
