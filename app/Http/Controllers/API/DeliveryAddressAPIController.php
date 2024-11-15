<?php

namespace App\Http\Controllers\API;


use App\Criteria\DeliveryAdresses\ActiveAddressesCriteria;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAddress;
use App\Repositories\DeliveryAddressRepository;
use Flash;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class DeliveryAddressController
 * @package App\Http\Controllers\API
 */
class DeliveryAddressAPIController extends Controller
{
    /** @var  DeliveryAddressRepository */
    private $deliveryAddressRepository;

    public function __construct(DeliveryAddressRepository $deliveryAddressRepo)
    {
        $this->deliveryAddressRepository = $deliveryAddressRepo;
    }

    /**
     * Display a listing of the DeliveryAddress.
     * GET|HEAD /deliveryAddresses
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->deliveryAddressRepository->pushCriteria(new RequestCriteria($request));
            $this->deliveryAddressRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->deliveryAddressRepository->pushCriteria(new ActiveAddressesCriteria($request));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        $deliveryAddresses = $this->deliveryAddressRepository->all();

        return $this->sendResponse($deliveryAddresses->toArray(), 'Delivery Addresses retrieved successfully');
    }

    /**
     * Display the specified DeliveryAddress.
     * GET|HEAD /deliveryAddresses/{id}
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var DeliveryAddress $deliveryAddress */
        if (!empty($this->deliveryAddressRepository)) {
            $deliveryAddress = $this->deliveryAddressRepository->findWithoutFail($id);
        }

        if (empty($deliveryAddress)) {
            return $this->sendError('Delivery address not found');
        }

        return $this->sendResponse($deliveryAddress->toArray(), 'Delivery Address retrieved successfully');
    }

    /**
     * Store a newly created DeliveryAddress in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request['active'] = true;
        $uniqueInput = $request->only("address");
        $otherInput = $request->except("address");
        try {
            $deliveryAddress = $this->deliveryAddressRepository->updateOrCreate($uniqueInput, $otherInput);

        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($deliveryAddress->toArray(), __('lang.saved_successfully', ['operator' => __('lang.delivery_address')]));
    }

    /**
     * Update the specified DeliveryAddress in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $deliveryAddress = $this->deliveryAddressRepository->findWithoutFail($id);

        if (empty($deliveryAddress)) {
            return $this->sendError('Delivery address not found');
        }
        $input = $request->all();
        if ($input['is_default'] == true){
            $this->deliveryAddressRepository->initIsDefault($input['user_id']);
        }
        try {
            $deliveryAddress = $this->deliveryAddressRepository->update($input, $id);
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($deliveryAddress->toArray(), __('lang.updated_successfully', ['operator' => __('lang.delivery_address')]));

    }

    /**
     * Remove the specified Address from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $address = $this->deliveryAddressRepository->findWithoutFail($id);

        if (empty($address)) {
            return $this->sendError('Delivery Address Not found');

        }

        $this->deliveryAddressRepository->delete($id);

        return $this->sendResponse($address, __('lang.deleted_successfully',['operator' => __('lang.delivery_address')]));

    }

    public function deactivate($id)
    {
        $deliveryAddress = $this->deliveryAddressRepository->findWithoutFail($id);
        if (empty($deliveryAddress)) {
            return $this->sendError('Delivery address not found');
        }
        try {
            $deliveryAddress = $this->deliveryAddressRepository->update(['active' => 0], $id);
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($deliveryAddress->toArray(), 'Delivery addresses deactivated successfully');
    }
}