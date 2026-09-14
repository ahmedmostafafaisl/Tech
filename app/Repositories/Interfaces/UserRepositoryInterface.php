<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface UserRepositoryInterface
{

    public function allEmployees($perPage, $page, $role, $status);
    public function allTechs($search, $page, $status);
    public function allCustomers($search, $page);
    public function index();
    public function find($id);
    public function updateUserStatus($status, $user);

    public function store(array $data);
    public function update(array $data, $user);
    public function findByPhone($phone);
    public function verifyOtp($phone, $otp);
    public function verifyPinCode($user, $pinCode);
    public function updatePinCode(User $user,  $oldPin,  $newPinCode);
    public function login(array $credentials);
    public function register(array $data);

    public function requestPinReset($user): mixed;
    public function approveResetRequest(int $userId, string $newPinCode): mixed;

    // Single User Appointments
    public function singleUserAppointments($id, $perPage, $page);
    // Single User Tasks
    public function singleUserTasks($id, $perPage, $page);
}
