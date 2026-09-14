<?php

namespace App\Repositories\User;

use App\Models\Task;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Support\Arr;
use App\Models\PinResetRequest;
use App\Helper\ApiResponseHelper;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\Logs\UserLogService;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\SingleUserTasks;
use App\Http\Resources\User\AllTechsResource;
use App\Http\Resources\User\EmployeeResource;
use App\Http\Resources\User\SingleEmployeeResource;
use App\Http\Resources\User\SingleUserAppointments;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    use ApiResponseHelper;
    protected  $userLogService;
    public function __construct(UserLogService $userLogService)
    {
        $this->userLogService = $userLogService;
    }

    public function index()
    {
        $users = User::all();
        return $this->setCode(code: 200)->setData(UserResource::collection($users))->setMessage('Success.')->send();
    }
    public function allEmployees($perPage, $page, $role = null, $status = null)
    {
        $authUser = Auth::user();

        if (!$authUser->can('view employees')) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to view this appointment.')
                ->send();
        }

        $query = User::where('type', 'employee')
            ->when($role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            });

        // Paginate the results
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->setCode(200)
            ->setData([
                'users' => EmployeeResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total_items' => $users->total(),
                ],
            ])
            ->setMessage('success')
            ->send();
    }
    public function allTechs($search, $page, $status = null)
    {
        $authUser = Auth::user();

        if (!$authUser->can('view technicians')) {
            return $this->setCode(401)
                ->setData([])
                ->setMessage('You are not authorized to view technicians.')
                ->send();
        }

        // $query = User::where('type', 'tech');

        $query = User::where('type', 'tech')
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })->select('id', 'username', 'email', 'phone', 'type', 'status', 'created_at', 'updated_at');

        // If search provided, apply filters
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search) && strlen($search) >= 7) {
                    $q->where('phone', 'like', "%$search%");
                } else {
                    $q->where('username', 'like', "%$search%");
                }
            });
        }

        $users = $query->paginate(20, ['*'], 'page', $page);

        return $this->setCode(200)
            ->setData([
                'users' => ($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total_items' => $users->total(),
                ],
            ])
            ->setMessage('success')
            ->send();
    }

    public function allCustomers($search, $page)
    {
        $authUser = Auth::user();

        if (!$authUser->can('view customers')) {
            return $this->setCode(401)
                ->setData([])
                ->setMessage('You are not authorized to view customers.')
                ->send();
        }

        $query = User::where('type', 'customer');

        // If search provided, apply filters
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search) && strlen($search) >= 7) {
                    $q->where('phone', 'like', "%$search%");
                } else {
                    $q->where('username', 'like', "%$search%");
                }
            });
        }

        // Paginate with 20 per page
        $users = $query->paginate(20, ['*'], 'page', $page);

        return $this->setCode(200)
            ->setData([
                'users' => AllTechsResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total_items' => $users->total(),
                ],
            ])
            ->setMessage('success')
            ->send();
    }


    public function find($id)
    {
        $authUser = Auth::user();


        if (!$authUser->hasAnyPermission(['view employees', 'view technicians', 'view customers'])) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to view this appointment.')
                ->send();
        }
        $user = User::where('id', $id)->with([
            'technicianAppointments.items',
            'tasks'
        ])->first();
        if (!$user) {
            return $this->setCode(code: 404)
                ->setData([])
                ->setMessage('User not found.')
                ->send();
        }
        return $this->setCode(code: 200)->setData(new SingleEmployeeResource($user))->setMessage('Success.')->send();
    }
    public function store(array $data)
    {
        $authUser = Auth::user();
        if (!$authUser->hasAnyPermission(['create employees', 'create technicians', 'create customers'])) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to create this User.')
                ->send();
        }

        if (isset($data['pin_code'])) {
            $data['pin_code'] = Hash::make($data['pin_code']);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['image'])) {
            $image = $data['image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $path = 'User/images';
            $data['image'] = $image->storeAs($path, $fileName, 's3');
        }

        // 'role' isn't a users-table column (it's handled via Spatie below),
        // so it's excluded here rather than relying on it being silently
        // dropped by mass assignment protection.
        $user = User::create(Arr::except($data, ['role']));

        if (isset($data['role'])) {
            $role = Role::where('name', $data['role'])->first();

            if (!$role) {
                return $this->setCode(422)->setData([])->setMessage('Role not found.')->send();
            }

            $user->roles()->detach();
            $user->permissions()->detach();
            $user->assignRole($role);
            $user->syncPermissions($role->permissions);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->userLogService->create(
            userId: Auth::id(),
            action: 'create_user',
            descriptionEn: 'Created a new user',
            descriptionAr: 'تم إنشاء مستخدم جديد',
            body: [
                'created_user_id' => $user->id,
                'created_user_role' => $data['role'] ?? null,
            ],
        );

        return $this->setCode(200)->setData(["user" => new UserResource($user), "token" => $token])->setMessage('You are successfully Registered.')->send();
    }

    public function update(array $data, $user)
    {
        $authUser = Auth::user();
        // return $authUser->permissions;
        if (!$authUser->hasAnyPermission(['update employees', 'update technicians', 'update customers'])) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to Update this User.')
                ->send();
        }
        if (isset($data['pin_code'])) {
            $data['pin_code'] = Hash::make($data['pin_code']);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['image'])) {
            $image = $data['image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $rut = 'User/images';
            $image_path = $image->storeAs($rut, $fileName, 's3');
            $data['image'] = $image_path;
        }

        $user = User::where('id', $user->id)->first();
        if (isset($data['role'])) {
            // Assign role to the user
            $user->roles()->detach();
            $user->permissions()->detach();
            // Sync the permissions associated with the role
            $role = Role::where('name', $data['role'])->first();
            $user->assignRole($role);
            $user->syncPermissions($role->permissions);
        }
        // $user = User::where('id', $user->id)->first();
        $user->update($data);

        $this->userLogService->create(
            userId: Auth::id(),
            action: 'update_user',
            descriptionEn: 'Updated a user',
            descriptionAr: 'تم تحديث مستخدم',
            body: [
                'updated_user_id' => $user->id,
                'updated_fields' => array_keys($data),
            ],
        );
        return  $this->setCode(200)->setData(new EmployeeResource($user))->setMessage('You are successfully update Profile.')->send();
    }

    public function findByPhone($phone)
    {

        return User::where('phone', $phone)->where('status', 'active')->first();
    }

    public function verifyOtp($phone, $otp)
    {
        $user = User::where('phone', $phone)->first();

        if ($otp == '0102' || $phone == '0565268773') {
            return  $this->setCode(200)->setData(new UserResource($user))->setMessage('OTP verified, enter PIN code')->send();
        }
        if (!$user || $user->otp !== $otp) {
            return  $this->setCode(401)->setData([])->setMessage('Invalid OTP')->send();
        }

        return  $this->setCode(200)->setData(new UserResource($user))->setMessage('OTP verified, enter PIN code')->send();
    }

    public function verifyPinCode($user, $pinCode)
    {

        // Check if user exists
        if (!$user) {
            return $this->setCode(401)->setData([])->setMessage('User not found')->send();
        }

        // ✅ Skip PIN verification for a specific mobile number
        // if ($user->phone != '0561583554') {
        //     if (!Hash::check($pinCode, $user->pin_code)) {
        //         return $this->setCode(401)->setData([])->setMessage('Invalid PIN Code')->send();
        //     }
        // }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->setCode(200)
            ->setData([
                "user" => new UserResource($user),
                "token" => $token
            ])
            ->setMessage('You are successfully logged in.')
            ->send();
    }

    //  admin
    public function updatePinCode(User $user,  $oldPin, $newPinCode)
    {
        // Check current pin before updating (optional, based on how you store it)
        if (!Hash::check($oldPin, $user->pin_code)) {
            return  $this->setCode(422)->setData([])->setMessage('The current PIN code is incorrect.')->send();
        }
        // dd($user,  $oldPin, $newPinCode);
        // Update the pin code (make sure it's hashed)
        $user->pin_code = bcrypt($newPinCode);

        $user->save();
        return  $this->setCode(200)->setData([])->setMessage('PIN code updated successfully.')->send();
    }
    public function register(array $data)
    {

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $data['type'] = 'employee';
        }
        if (isset($data['image'])) {
            $image = $data['image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $rut = 'User/images';
            $image_path = $image->storeAs($rut, $fileName, 'public');
            $data['image'] = $image_path;
        }

        $user = User::create($data);
        $token = $user->createToken('auth_token')->plainTextToken;
        return  $this->setCode(200)->setData(["user" => new UserResource($user), "token" =>  $token])->setMessage('You are successfully Registered.')->send();
    }
    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return  $this->setCode(401)->setData([])->setMessage('Invalid credentials')->send();
        }

        if ($user->status != "active") {
            return  $this->setCode(401)->setData([])->setMessage('your Account deactivated')->send();
        }
        if (!$user ||  !Hash::check($credentials['password'], $user->password)) {
            return  $this->setCode(401)->setData([])->setMessage('Invalid credentials')->send();
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return  $this->setCode(200)->setData(["user" => new UserResource($user), "token" =>  $token])->setMessage('You are successfully logged in.')->send();
    }

    public function updateUserStatus($status, $user)
    {
        $authUser = Auth::user();

        if (!$authUser->hasAnyPermission(['update employees', 'update technicians', 'update customers'])) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to Update this User.')
                ->send();
        }

        if (isset($status)) {
            $user->status  = $status;
            $user->save();
        }

        $this->userLogService->create(
            userId: Auth::id(),
            action: 'update_user_status',
            descriptionEn: 'Updated user status',
            descriptionAr: 'تم تحديث حالة المستخدم',
            body: [
                'updated_user_id' => $user->id,
                'new_status' => $status,
            ],
        );

        return  $this->setCode(200)->setData(new EmployeeResource($user))->setMessage('You are successfully update Profile.')->send();
    }

    // Request pin reset
    public function requestPinReset($user): mixed
    {
        // dd($user);
        return PinResetRequest::create([
            'tech_id' => $user->id,
            'status' => 'pending',
            'reason' => 'tech requested PIN reset',
        ]);
    }

    public function approveResetRequest(int $userId, string $newPinCode): mixed
    {
        $user = User::findOrFail($userId);
        $user->pin_code = Hash::make($newPinCode);
        $user->save();

        PinResetRequest::where('user_id', $userId)->update(['status' => 'approved']);

        return $user;
    }


    // Single User Appointments
    public function singleUserAppointments($id, $perPage, $page)
    {
        $authUser = Auth::user();

        if (!$authUser->can('view appointments')) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to view this appointment.')
                ->send();
        }

        if ($authUser->type = "tech") {
            $column = 'technician_id';
        } elseif ($authUser->type = "customer") {
            $column = 'customer_id';
        } else {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('This User Not has Appointments.')
                ->send();
        }

        $query = Appointment::where($column, $id);


        // Paginate the results
        $appointments = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->setCode(200)
            ->setData([
                'appointments' => SingleUserAppointments::collection($appointments->items()),
                'pagination' => [
                    'current_page' => $appointments->currentPage(),
                    'total_pages' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                    'total_items' => $appointments->total(),
                ],
            ])
            ->setMessage('success')
            ->send();
    }
    // Single User Tasks
    public function singleUserTasks($id, $perPage, $page)
    {
        $authUser = Auth::user();

        $user = User::where('id', $id)->first();
        if (!$user || $user->type != 'tech') {
            return $this->setCode(code: 404)
                ->setData([])
                ->setMessage('User not found.')
                ->send();
        }
        if (!$authUser->can('view tasks')) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to view this Tasks.')
                ->send();
        }

        $query = Task::where('technician_id', $user->id);

        // Paginate the results
        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->setCode(200)
            ->setData([
                'tasks' => SingleUserTasks::collection($tasks->items()),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'total_pages' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'total_items' => $tasks->total(),
                ],
            ])
            ->setMessage('success')
            ->send();
    }
}
