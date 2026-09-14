<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Services\TaqnyatSmsService;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\User\UserResource;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\RegisterUserRequest;
use App\Http\Requests\User\UpdatePinCodeRequest;
use App\Http\Requests\User\VerifyPinCodeRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserController extends Controller
{
    use ApiResponseHelper;
    protected $userRepository;
    protected $smsService;

    public function __construct(UserRepositoryInterface $userRepository, TaqnyatSmsService $smsService)
    {
        $this->userRepository = $userRepository;
        $this->smsService = $smsService;
    }
    public function allEmployees(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('current_page', 1);
        $status = $request->get("status");
        $role = $request->get("role");
        return $this->userRepository->allEmployees($perPage, $page, $role, $status);
    }
    public function allTechs(Request $request)
    {
        $page = $request->input('current_page', 1);
        $search = $request->get("search", '');
        $status = $request->get("status");
        return $this->userRepository->allTechs($search, $page, $status);
    }

    public function allCustomers(Request $request)
    {
        $page = $request->input('current_page', 1);
        $search = $request->get("search", '');
        return $this->userRepository->allCustomers($search, $page);
    }


    public function find(string $id)
    {
        return $this->userRepository->find($id);
    }


    public function store(StoreUserRequest $request)
    {
        return   $user = $this->userRepository->store($request->validated());
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        return   $user = $this->userRepository->update($request->validated(), $user);
    }
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return $this->setCode(code: 200)->setData(new UserResource($user))->setMessage('Success.')->send();
    }


    public function updateUserStatus(UpdateUserStatusRequest $request, User $user)
    {
        return  $user = $this->userRepository->updateUserStatus($request->status, $user);
    }
    public function sendOtp(Request $request)
    {
        $user = $this->userRepository->findByPhone($request->phone);
        if (!$user) return response()->json(['message' => 'User not found'], 404);
        $otp = rand(1000, 9999);
        $user->update(['otp' => $otp]);
        $response = $this->smsService->sendOtp($request->phone, $otp);

        $email = $user->email;
        // if ($email) {
        //     Mail::to($email)->send(new \App\Mail\SendOtpMail($otp));
        // }

        return  $this->setCode(200)->setData($email)->setMessage('OTP sent successfully.')->send();
    }

    public function verifyOtp(Request $request)
    {
        $user = $this->userRepository->findByPhone($request->phone);
        if (!$user) return response()->json(['message' => 'User not found'], 404);
        return     $user = $this->userRepository->verifyOtp($request->phone, $request->otp);
    }

    public function verifyPinCode(VerifyPinCodeRequest $request)
    {
        if ($request->update_version != 5 && $request->update_version != '1.3.5') {
            return $this->setCode(404)
                ->setNeedUpdate(true)
                ->setMaintenanceMode(false)
                ->setMessage('you must update your app')
                ->send();
        }
        // Use auth user if available
        $user = auth('api')->user();
        if (!$user) {
            $user = $this->userRepository->findByPhone($request->phone);
            if (!$user) {
                return  $this->setCode(404)->setData([])->setMessage('User not Found')->send();
            }
        }
        $response = $this->userRepository->verifyPinCode($user, $request->pin_code);

        $data = $response->getData(true); // Convert JSON response to array
        $data['need_update'] = $request->update_version != '1.3.5';
        $data['maintenance_mode'] = false;
        $response->setData($data);
        return $response;
    }
    // update pin code
    public function updatePinCode(UpdatePinCodeRequest $request)
    {
        $user = Auth::user();
        if (!$user) {

            return  $this->setCode(404)->setData([])->setMessage('User not Found')->send();
        }
        if ($user->type !== 'tech' && !$user->hasPermissionTo('update technicians')) {
            return  $this->setCode(401)->setData([])->setMessage('User not Auth')->send();
        }

        return   $user = $this->userRepository->updatePinCode($user, $request->current_pin, $request->new_pin_code);
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        return   $user = $this->userRepository->login($credentials);
    }

    public function register(RegisterUserRequest $request)
    {
        return   $user = $this->userRepository->register($request->validated());
    }

    // request pin reset
    public function requestPinReset(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);
            $user = User::findOrFail($request->user_id);
        }
        $reset = $this->userRepository->requestPinReset($user);
        return response()->json(['message' => 'Reset request sent.', 'data' => $reset], 200);
    }

    public function approveResetRequest(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'new_pin_code' => 'required|string|min:4|max:4',
        ]);

        $user = $this->userRepository->approveResetRequest($request->user_id, $request->new_pin_code);
        return response()->json(['message' => 'PIN updated successfully.', 'data' => $user], 200);
    }

    // Single User Appointments
    public function singleUserAppointments(Request $request, $id)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('current_page', 1);

        return $this->userRepository->singleUserAppointments($id, $perPage, $page);
    }

    // Single User Tasks
    public function singleUserTasks(Request $request, $id)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('current_page', 1);

        return $this->userRepository->singleUserTasks($id, $perPage, $page);
    }
}
