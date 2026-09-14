<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'warehouse_id',
        'tech_id',
        'date',
        'status',
        'technician_status',
        'transfer_rec_id',
        'transfer_id',
        'from_warehouse',
        'to_warehouse',
        'from_warehouse_rec',
        'to_warehouse_rec',
        'dy_response',
        'flag',
        'from_technician_status',
    ];



    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'tech_id');
    }

    public function lines()
    {
        return $this->hasMany(TransferOrderLines::class);
    }
}
