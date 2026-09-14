<?php
// ===== AppointmentTransaction.php =====

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentTransaction extends Model
{
    protected $fillable = [
        'book_id',
        'rec_id',
        'tech_id',
    ];

    protected $casts = [
        'rec_id'  => 'integer',
        'tech_id' => 'integer',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(AppointmentTransactionLine::class);
    }
}
