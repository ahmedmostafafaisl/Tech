<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransmittedSerial extends Model
{
    protected $fillable = ['book_id', 'tech_id', 'item_number', 'serial'];
}
