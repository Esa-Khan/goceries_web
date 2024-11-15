<?php
/**
 * File name: UserAPIController.php
 * Last modified: 2020.06.11 at 12:09:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Notifications\AssignedOrder;
use App\Repositories\CustomFieldRepository;
use App\Repositories\DriverRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use DateInterval;
use DateTime;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Prettus\Validator\Exceptions\ValidatorException;
use Psy\Exception\ErrorException;

class UserAPIController extends Controller
{
    private $userRepository;
    private $uploadRepository;
    private $roleRepository;
    private $customFieldRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepository $userRepository, UploadRepository $uploadRepository, RoleRepository $roleRepository, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->roleRepository = $roleRepository;
        $this->customFieldRepository = $customFieldRepo;
    }

    function login(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required',
            ]);
            if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
                // Authentication passed...
                $user = auth()->user();
                $user->device_token = $request->input('device_token', '');
                $user->save();

                if ($user->isDriver or $user->isManager) {
//                    if (!DB::table('drivers')->where('drivers.user_id', $user->id)->exists()) {
//                        echo $user;
//                    }
                    Driver::where('user_id', '=', $user['id'])->update(['available' => 1]);

//                    if (!$user['isDriver'] and !$user['isManager']) {
//                        return $this->sendError('User not driver', 401);
//                    }
                    $work_hours = Driver::select('work_hours')->where('user_id', $user->id)->get();
                    $store_ids = Driver::select('store_ids')->where('user_id', $user->id)->get();
                    $isAvailable = Driver::select('available')->where('user_id', $user->id)->get();

                    $user['work_hours'] = $work_hours[0]['work_hours'];
                    $user['store_ids'] = $store_ids[0]['store_ids'];
                    $user['available'] = $isAvailable[0]['available'];

                }
                return $this->sendResponse($user, 'User retrieved successfully');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }
        if (DB::table('users')->where('users.email', $request['email'])->exists()) {
            return $this->sendError('Incorrect Password', 500);
        }

        return '';

    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return
     */
    function register(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required|unique:users|email',
                'password' => 'required',
            ]);
            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->device_token = $request->input('device_token', '');
            $user->password = Hash::make($request->input('password'));
            $user->api_token = str_random(60);
            $user->save();

            $defaultRoles = $this->roleRepository->findByField('default', '1');
            $defaultRoles = $defaultRoles->pluck('name')->toArray();
            $user->assignRole($defaultRoles);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError($e->getMessage(), 401);
        }


        return $this->sendResponse($user, 'User retrieved successfully');
    }

    function logout(Request $request)
    {

        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();
        if (!$user) {
            return $this->sendError('User not found', 401);
        }
        try {
            User::findOrFail($user->id)->update(['device_token' => '']);
            auth()->logout();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage(), 401);
        }
        return $this->sendResponse($user['name'], 'User logout successfully');
    }

    function user(Request $request)
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();

        if (!$user) {
            return $this->sendError('User not found', 401);
        }

        return $this->sendResponse($user, 'User retrieved successfully');
    }

    function settings(Request $request)
    {
        $settings = setting()->all();
        $settings = array_intersect_key($settings,
            [
                'default_tax' => '',
                'default_currency' => '',
                'default_currency_decimal_digits' => '',
                'app_name' => '',
                'currency_right' => '',
                'enable_paypal' => '',
                'enable_stripe' => '',
                'enable_razorpay' => '',
                'main_color' => '',
                'main_dark_color' => '',
                'second_color' => '',
                'second_dark_color' => '',
                'accent_color' => '',
                'accent_dark_color' => '',
                'scaffold_dark_color' => '',
                'scaffold_color' => '',
                'google_maps_key' => '',
                'mobile_language' => '',
                'app_version' => '',
                'enable_version' => '',
                'distance_unit' => '',
                'delivery_fee_limit' => '',
                'whatsapp_number' => '',
                'facebook_url' => '',
                'instagram_url_ios' => '',
                'instagram_url_android' => '',
                'phone_number' => '',
                'debug_url' => '',
                'promo' => ''
            ]
        );

        if (!$settings) {
            return $this->sendError('Settings not found', 401);
        }

        return $this->sendResponse($settings, 'Settings retrieved successfully');
    }

    /**
     * Update the specified User in storage.
     *
     * @param int $id
     * @param Request $request
     *
     */
    public function update($id, Request $request)
    {
        $user = $this->userRepository->findWithoutFail($id);

        if (empty($user)) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'User not found');
        }
        $input = $request->except(['password', 'api_token']);
        try {
            if ($request->has('device_token')) {
                $user = $this->userRepository->update($request->only('device_token'), $id);
                $user = $this->userRepository->update($input, $id);
		

	    } else {
                $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());
                $user = $this->userRepository->update($input, $id);

                foreach (getCustomFieldsValues($customFields, $request) as $value) {
                    $user->customFieldsValues()
                        ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
                }
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage(), 401);
        }

        return $this->sendResponse($user, __('lang.updated_successfully', ['operator' => __('lang.user')]));
    }

    function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $response = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($response == Password::RESET_LINK_SENT) {
            return $this->sendResponse(true, 'Reset link was sent successfully');
        } else {
            return $this->sendError('Reset link not sent', 401);
        }

    }



    //*******************************Driver Functions****************************
    function toggleDriverAvail(int $id, bool $isAvail)
    {
        try {
            Driver::where('user_id', $id)->update(['available' => $isAvail]);
            $result['available'] = $isAvail;
            $result['work_hours'] = Driver::select('work_hours')->where('user_id', $id)->get()[0]['work_hours'];
            return $this->sendResponse($result, 'Success');
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage(), 401);
        }
    }


    function getDriverAvail(int $id)
    {
        try {
            $result['available'] = Driver::where('user_id', $id)->pluck('available')->first();
            $result['work_hours'] = Driver::where('user_id', $id)->pluck('work_hours')->first();

            return $this->sendResponse($result, 'Success');
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage(), 401);
        }
    }



    function updateDriverAvail(int $id)
    {
        $work_hours = Driver::select('work_hours')->where('user_id', $id)->get()[0]['work_hours'];
        $work_hours = explode('|', $work_hours);
        $now = date("H:i:s");
        if(date("Hi") < "1400") {

        }
        return $this->sendResponse($now, 'Success');
    }


    function setDebugger($id, $isDebugger) {
        $user = User::where('id', $id)->update(['isAdmin' => $isDebugger]);
        if ($user === 1) {
            $url = setting()->all()['debug_url'];
            return $this->sendResponse($url, 'Success');
        }

        return $this->sendResponse(null, 'Failed: Could not get user');
    }

    function getPoints(int $id){
        try {
            $points = User::where('id', $id)->pluck('points')->first();
            return $this->sendResponse($points, 'Success');
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage(), 401);
        }

    }


    function test(Request $request) {
        $user = User::find($request['user_id']);
        $points_percentage = Restaurant::where('id', $request['store_id'])->pluck('points_percentage')->first();
        $user->points += (1 + $points_percentage/100)*$request['amountWithTax'];
        $user->save();
//        $store = Restaurant::where()
//        $user->points -= $input['points_redeemed'];

        return $user->points;
    }






}
