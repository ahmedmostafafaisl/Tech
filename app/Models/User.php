<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRoles, HasApiTokens, Notifiable;
    protected $guard_name = 'web';

    protected $fillable = [
        'tech_id',
        'username',
        'email',
        'phone',
        'pin_code',
        'otp',
        'password',
        'type',
        'image',
        'role',
        'status',
        'fcm_token',
        'warehouse_id',
        'personnel_number',
        'technician_rec_id',
        'account_number',
        'address',
        'main_warehouse_id',
    ];


    protected $hidden = [
        'password',
        'remember_token',
        'pin_code',
        // 'otp'
    ];

    public function customerAppointments()
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }


    public function technicianAppointments()
    {
        return $this->hasMany(Appointment::class, 'technician_id');
    }

    public function stock()
    {
        return $this->hasOne(UserStock::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'technician_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    public function warehouseTransfers()
    {
        return $this->hasMany(WarehouseTransfer::class, 'tech_id');
    }

    // user logs

    public function logs()
    {
        return $this->hasMany(UserLog::class);
    }
    // user pin reset requests
    public function pinResetRequests()
    {
        return $this->hasMany(PinResetRequest::class);
    }

    // transfer orders
    public function transferOrders()
    {
        return $this->hasMany(TransferOrder::class, 'tech_id');
    }

    // appointment change status requests
    public function appointmentChangeStatusRequests()
    {
        return $this->hasMany(AppointmentChangeStatusRequest::class, 'technician_id');
    }

    // warehouses
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouses', 'user_id', 'warehouse_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
    protected function getUserWarehouses(User $user)
    {
        return $user->warehouses()->get();
    }

    // technician logs
    public function technicianLogs()
    {
        return $this->hasMany(TechnicianLog::class, 'tech_id', 'tech_id');
    }

    // direct appointments as technician
    public function directAppointments()
    {
        return $this->hasMany(DirectAppointment::class, 'tech_id', 'tech_id');
    }

    // favorite appointments
    public function favoriteAppointments()
    {
        return $this->hasMany(FavoriteAppointment::class);
    }

    // tech notifications
    public function techNotifications()
    {
        return $this->hasMany(TechNotification::class);
    }
}
