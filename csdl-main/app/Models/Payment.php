<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'paymentID';

    protected $fillable = [
        'payMethod',
    ];


}
