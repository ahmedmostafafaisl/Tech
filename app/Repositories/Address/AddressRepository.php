<?php

namespace App\Repositories\Address;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\AddressRepositoryInterface;

class AddressRepository implements AddressRepositoryInterface
{
    public function all()
    {
        return Address::all();
    }

    public function find($id)
    {
        return Address::findOrFail($id);
    }

    public function create(array $data)
    {
        return Address::create($data);
    }

    public function update($id, array $data)
    {
        $address = Address::findOrFail($id);
        $address->update($data);
        return $address;
    }

    public function delete($id)
    {
        $address = Address::findOrFail($id);
        return $address->delete();
    }


    public function getAuthCustomerAddresses()
    {
        $user = Auth::user();
        // dd($user->type);
        if (!$user || strtolower($user->type) !== 'customer') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or not a customer.'
            ], 403);
        }

        return   $addresses = Address::where('user_id', $user->id)->get();
    }

    public function getCustomerAddresses($id)
    {
        return Address::where('user_id', $id)->get();
    }
}
