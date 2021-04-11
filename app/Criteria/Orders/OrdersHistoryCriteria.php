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

        $start_date = '';
        $end_date = '';
        if ($this->request->get('start_date') !== null && $this->request->get('end_date') !== null) {
            $start_date = new DateTime($this->request->get('start_date'));
            $start_date->setTime(0, 0, 0);

            $end_date = new DateTime($this->request->get('end_date'));
            $end_date->add(new DateInterval('P1D'));
            $end_date->setTime(0, 0, 0);
        } else {
            $start_date = '2000/01/01';
            $end_date = '3000/01/01';
        }

        if ($isManager) {
            return $model->where([
                                ['orders.order_status_id', 5],
                                ['created_at', '>', $start_date],
                                ['created_at', '<', $end_date],
                            ])
                            ->orWhere([
                                ['orders.active', 0],
                                ['created_at', '>', $start_date],
                                ['created_at', '<', $end_date],
                            ])
                            ->orderBy('orders.id', 'desc')
                            ->select('orders.*');

        } else {
            return $model->where([
                                ['orders.driver_id', $this->request->get('driver_id')],
                                ['created_at', '>', $start_date],
                                ['created_at', '<', $end_date],
                            ])
                            ->orWhere([
                                ['orders.active', 0],
                                ['created_at', '>', $start_date],
                                ['created_at', '<', $end_date],
                            ])
                            ->orderBy('orders.id', 'asc')
                            ->select('orders.*');
        }



    }
}
