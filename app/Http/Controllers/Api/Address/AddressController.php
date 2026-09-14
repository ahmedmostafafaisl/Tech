<?php

namespace App\Http\Controllers\Api\Address;

use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Address\AddressResource;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Requests\Address\GetCustomerAddressesRequest;
use App\Repositories\Interfaces\AddressRepositoryInterface;

class AddressController extends Controller
{
    use ApiResponseHelper;

    protected $repo;

    public function __construct(AddressRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        return AddressResource::collection($this->repo->all());
    }

    public function store(StoreAddressRequest $request)
    {
        $address = $this->repo->create($request->validated());
        return new AddressResource($address);
    }

    public function show($id)
    {
        return new AddressResource($this->repo->find($id));
    }

    public function update(UpdateAddressRequest $request, $id)
    {
        $address = $this->repo->update($id, $request->validated());
        return new AddressResource($address);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function getAuthCustomerAddresses()
    {
        $response = $this->repo->getAuthCustomerAddresses();

        // If the repository method returned a JsonResponse (i.e., an error), return it as-is
        if ($response instanceof \Illuminate\Http\JsonResponse && $response->getStatusCode() !== 200) {
            return $response;
        }

        // Otherwise, assume it's a valid collection of addresses
        return AddressResource::collection($response);
    }

    public function getCustomerAddresses($id)
    {
        $response = $this->repo->getCustomerAddresses($id);

        // If the repository method returned a JsonResponse (i.e., an error), return it as-is
        if ($response instanceof \Illuminate\Http\JsonResponse && $response->getStatusCode() !== 200) {
            return $response;
        }
        return  $this->setCode(200)->setData(AddressResource::collection($response))->setMessage('Addresses retrieved successfully.')->send();
    }
}
