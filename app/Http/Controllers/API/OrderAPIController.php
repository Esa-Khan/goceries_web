<?php
/**
 * File name: OrderAPIController.php
 * Last modified: 2020.06.08 at 20:36:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 */

namespace App\Http\Controllers\API;


use App\Criteria\Orders\OrdersHistoryCriteria;
use App\Criteria\Orders\OrdersNotDelivered;
use App\Criteria\Orders\OrdersOfDriverCriteria;
use App\Events\OrderChangedEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\AssignedOrder;
use App\Notifications\CancelledOrder;
use App\Notifications\NewOrder;
use App\Notifications\StatusChangedOrder;
use App\Repositories\CartRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\FoodOrderRepository;
use App\Repositories\UsedPromoCodeRepository;
use App\Repositories\UserRepository;
use App\Repositories\DriverRepository;
use Braintree\Gateway;
use DateTime;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use Stripe\Token;

/**
 * Class OrderController
 * @package App\Http\Controllers\API
 */
class OrderAPIController extends Controller
{
    /** @var  OrderRepository */
    private $orderRepository;
    /** @var  FoodOrderRepository */
    private $foodOrderRepository;
    /** @var  CartRepository */
    private $cartRepository;
    /** @var  UserRepository */
    private $userRepository;
    /** @var  DriverRepository */
    private $driverRepository;
    /** @var  PaymentRepository */
    private $paymentRepository;
    /** @var  NotificationRepository */
    private $notificationRepository;
    /** @var UsedPromoCodeRepository */
    private $usedPromoCodeRepository;

    /**
     * OrderAPIController constructor.
     * @param OrderRepository $orderRepo
     * @param FoodOrderRepository $foodOrderRepository
     * @param CartRepository $cartRepo
     * @param PaymentRepository $paymentRepo
     * @param NotificationRepository $notificationRepo
     * @param UserRepository $userRepository
     * @param UsedPromoCodeRepository $usedPromoCodeRepository
     * @param DriverRepository $driverRepo
     */
    public function __construct(OrderRepository $orderRepo,
                                FoodOrderRepository $foodOrderRepository,
                                CartRepository $cartRepo,
                                PaymentRepository $paymentRepo,
                                NotificationRepository $notificationRepo,
                                UserRepository $userRepository,
                                UsedPromoCodeRepository $usedPromoCodeRepository,
                                DriverRepository $driverRepo)
    {
        $this->orderRepository = $orderRepo;
        $this->foodOrderRepository = $foodOrderRepository;
        $this->cartRepository = $cartRepo;
        $this->userRepository = $userRepository;
        $this->usedPromoCodeRepository = $usedPromoCodeRepository;
        $this->driverRepository = $driverRepo;
        $this->paymentRepository = $paymentRepo;
        $this->notificationRepository = $notificationRepo;
    }

    /**
     * Display a listing of the Order.
     * GET|HEAD /orders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->orderRepository->pushCriteria(new RequestCriteria($request));
            $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
            if (isset($request['driver_id'])) {
                $this->orderRepository->pushCriteria(new OrdersOfDriverCriteria($request));
            }
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }




        $orders = $this->orderRepository->all();
        return $this->sendResponse($orders->toArray(), 'Orders retrieved successfully');
    }

    // GET order history for manager/driver
    /**
     * Get order history.
     * GET|HEAD /orderhistory
     *
     * @param Request $request - driver_id
     * @return \Illuminate\Http\JsonResponse - orders
     */
    public function getOrderHistory(Request $request) {
        try {
            $this->orderRepository->pushCriteria(new RequestCriteria($request));
            $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->orderRepository->pushCriteria(new OrdersHistoryCriteria($request));

        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        $orders = $this->orderRepository->all();

        return $this->sendResponse($orders->toArray(), 'Orders retrieved successfully');
    }


    /**
     * Display the specified Order.
     * GET|HEAD /orders/{id}
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var Order $order */
        if (!empty($this->orderRepository)) {
            try {
                $this->orderRepository->pushCriteria(new RequestCriteria($request));
                $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
            } catch (RepositoryException $e) {
                Flash::error($e->getMessage());
            }
            $order = $this->orderRepository->findWithoutFail($id);

            if (isset($request['isDriverOrder'])) {
                $order['driver'] = $order->driver()->get(['name', 'number'])[0];
            }
        }

        if (empty($order)) {
            return $this->sendError('Order not found');
        }

        return $this->sendResponse($order->toArray(), 'Order retrieved successfully');


    }

    public function checkCode(Request $request) {
        $code_used = $this->usedPromoCodeRepository->findByField('user_id', [$request['user_id']])->pluck('code_used')->toArray();
        $response['isUsed'] = 'false';
        foreach ($code_used as $code) {
            if (strcmp($code, $request['code']) === 0) {
                $response['isUsed'] = 'true';
                break;
            }
        }

        return $this->sendResponse($response, 'Check retrieved successfully');

//        return $this->usedPromoCodeRepository->all();
    }

    /**
     * Store a newly created Order in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        $payment = $request->only('payment');
        if (isset($payment['payment']) && $payment['payment']['method']) {
            if ($payment['payment']['method'] == "Credit Card (Stripe Gateway)") {
                return $this->stripPayment($request);
            } else {
                return $this->cashPayment($request);

            }
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function stripPayment(Request $request)
    {
        $input = $request->all();
        $amount = 0;

        try {
//            return substr($_ENV['APP_DEBUG'], 0, 4);

            $user = $this->userRepository->findWithoutFail($input['user_id']);
            if (empty($user)) {
                return $this->sendError('User not found');
            }
            $stripeToken = Token::create(array(
                "card" => array(
                    "number" => $input['stripe_number'],
                    "exp_month" => $input['stripe_exp_month'],
                    "exp_year" => $input['stripe_exp_year'],
                    "cvc" => $input['stripe_cvc'],
                    "name" => $user->name,
                )
            ));

            if (isset($request['code'])) {
                try {
                    $this->usedPromoCodeRepository->create([
                        "user_id" => $request['user_id'],
                        "code_used" => $request['code'],
                    ]);
                } catch (ValidatorException $e) {
                    echo $e;
                }
            }

            if ($stripeToken->created > 0) {
                if (empty($input['delivery_address_id'])) {

                    $order = $this->orderRepository->create(
                        $request->only('user_id', 'order_status_id', 'tax', 'hint', 'store_id')
                    );
                } else {

                    $order = $this->orderRepository->create(
                        $request->only('user_id', 'order_status_id', 'tax', 'delivery_address_id',
                            'delivery_fee', 'hint', 'scheduled_time', 'store_id')
                    );
                }

                foreach ($input['foods'] as $foodOrder) {
                    $foodOrder['order_id'] = $order->id;
                    $amount += $foodOrder['price'] * $foodOrder['quantity'];
                    $this->foodOrderRepository->create($foodOrder);
                }

                $amount += $order->delivery_fee;
                $amountWithTax = $amount - $order->tax;
                $charge = $user->charge((int)($amountWithTax * 100), ['source' => $stripeToken]);
                if ($charge == 'Error: Card Declined')
                    return 'Error: Card Declined';

                $payment = $this->paymentRepository->create([
                    "user_id" => $input['user_id'],
                    "description" => trans("lang.payment_order_done"),
                    "price" => $amountWithTax,
                    "status" => $charge->status, // $charge->status
                    "method" => $input['payment']['method'],
                ]);

                $this->paymentRepository->update(['price' => $payment['price'] - $payment['price']*$order->foodOrders[0]->food->restaurant->default_tax], $payment->id);

                $this->orderRepository->update(['payment_id' => $payment->id], $order->id);

                $this->cartRepository->deleteWhere(['user_id' => $order->user_id]);



                $temp_order['user_id'] = $order->user_id;
                $temp_order['order_status_id'] = $order->order_status_id;
                $temp_order['status'] = $payment->status;
                $temp_order['tax'] = $order->tax;
                $temp_order['hint'] = $order->hint;
                $temp_order['delivery_address_id'] = $order->delivery_address_id;
                $temp_order['payment_id'] = $payment->id;
                $temp_order['delivery_fee'] = $order->delivery_fee;
                $temp_order['driver_id'] = 1;
                $this->orderRepository->update($temp_order, $order->id);

                if ($_ENV['APP_DEBUG'] == 'true' || (isset($request['DEBUG']) && $request['DEBUG'])) {
//                    $driver = $this->driverRepository->find(3, ['user_id']);
//                    foreach ($drivers as $currDriver) {
//                        $driver = $this->userRepository->findWithoutFail($currDriver->user_id);
//                        Notification::send([$driver], new AssignedOrder($order));
//                    }
//                    $manager = DB::table('users')
//                        ->where('users.isManager', true)
//                        ->where('users.id', 1)
//                        ->get();
//                    Notification::send($driver, new NewOrder($order));
                    $dev = $this->userRepository->find(1);
                    Notification::send($dev, new NewOrder($order));

                } else {
//                Notification::send($order->foodOrders[0]->food->restaurant->users, new NewOrder($order));
                    $drivers = $this->driverRepository->all();
                    foreach ($drivers as $currDriver) {
                        $driver = $this->userRepository->findWithoutFail($currDriver->user_id);
                        Notification::send([$driver], new AssignedOrder($order));
                    }

                }


            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function cashPayment(Request $request)
    {
        $input = $request->all();
        $amount = 0;
        try {
            $order = $this->orderRepository->create(
		        $request->only('user_id', 'order_status_id', 'tax', 'delivery_address_id',
                    'delivery_fee', 'hint', 'scheduled_time', 'store_id')
	        );

            foreach ($input['foods'] as $foodOrder) {
                $foodOrder['order_id'] = $order->id;
                $amount += $foodOrder['price'] * $foodOrder['quantity'];
                $this->foodOrderRepository->create($foodOrder);
            }
            $amount += $order->delivery_fee;
            $amountWithTax = $amount - $order->tax;
            $payment = $this->paymentRepository->create([
                "user_id" => $input['user_id'],
                "description" => trans("lang.payment_order_waiting"),
                "price" => $amountWithTax,
                "status" => 'Waiting for Client',
                "method" => $input['payment']['method'],
            ]);

            if (isset($request['code'])) {
                try {
                    $this->usedPromoCodeRepository->create([
                        "user_id" => $request['user_id'],
                        "code_used" => $request['code'],
                    ]);
                } catch (ValidatorException $e) {
                    echo $e;
                }
            }

            $this->orderRepository->update(['payment_id' => $payment->id], $order->id);

            $this->cartRepository->deleteWhere(['user_id' => $order->user_id]);

            Notification::send($order->foodOrders[0]->food->restaurant->users, new NewOrder($order));

            $temp_order['user_id'] = $order->user_id;
            $temp_order['order_status_id'] = $order->order_status_id;
            $temp_order['status'] = $payment->status;
            $temp_order['tax'] = $order->tax;
            $temp_order['hint'] = $order->hint;
            $temp_order['delivery_address_id'] = $order->delivery_address_id;
            $temp_order['payment_id'] = $payment->id;
            $temp_order['delivery_fee'] = $order->delivery_fee;
            $temp_order['driver_id'] = 1;
            $this->orderRepository->update($temp_order, $order->id);


            if ($_ENV['APP_DEBUG'] == 'true' || (isset($request['DEBUG']) && $request['DEBUG'])) {
                return 'True';
//                    $driver = $this->driverRepository->find(3, ['user_id']);
//                    foreach ($drivers as $currDriver) {
//                        $driver = $this->userRepository->findWithoutFail($currDriver->user_id);
//                        Notification::send([$driver], new AssignedOrder($order));
//                    }
//                    $manager = DB::table('users')
//                        ->where('users.isManager', true)
//                        ->where('users.id', 1)
//                        ->get();
//                    Notification::send($driver, new NewOrder($order));
                $dev = $this->userRepository->find(1);
                Notification::send($dev, new NewOrder($order));

            } else {
//                Notification::send($order->foodOrders[0]->food->restaurant->users, new NewOrder($order));
                $drivers = $this->driverRepository->all();
                foreach ($drivers as $currDriver) {
                    $driver = $this->userRepository->findWithoutFail($currDriver->user_id);
                    Notification::send([$driver], new AssignedOrder($order));
                }

            }

//            $drivers = $this->driverRepository->all();
//            foreach ($drivers as $currDriver) {
//                $driver = $this->userRepository->findWithoutFail($currDriver->user_id);
//                Notification::send([$driver], new AssignedOrder($order));
//            }


        } catch
        (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }


    /**
     * Update the specified Order in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $oldOrder = $this->orderRepository->findWithoutFail($id);
        if (empty($oldOrder)) {
            return $this->sendError('Order not found');
        }
        $oldStatus = $oldOrder->payment->status;

        $input = $request->all();
        if (isset($input['check_approval']) && $oldOrder['driver_id'] != 1 && $input['current_driver'] != $oldOrder['driver_id']) {
            return "Order already assigned";
        }

        try {
            $order = $this->orderRepository->update($input, $id);
            if (isset($input['active'])) {
                $this->orderRepository->update(['active' => 0], $order->id);
                if ($order->driver_id != 1){
                    $driver = $this->userRepository->findWithoutFail($order->driver_id);
                    Notification::send([$driver], new CancelledOrder($order));
                } else {
                    $drivers = $this->driverRepository->all();
                    foreach ($drivers as $currDriver) {
                        $driver = $this->userRepository->findWithoutFail($currDriver->user_id);
                        Notification::send([$driver], new CancelledOrder($order));
                    }
                }
            }
            if (isset($input['order_status_id']) && $input['order_status_id'] == 5 && !empty($order)) {
                $this->paymentRepository->update(['status' => 'Paid'], $order['payment_id']);
            }
            event(new OrderChangedEvent($oldStatus, $order));

            if (setting('enable_notifications', false)) {
                if (isset($input['order_status_id']) && $input['order_status_id'] != $oldOrder->order_status_id) {
                    Notification::send([$order->user], new StatusChangedOrder($order));
                }
            }

        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

}
