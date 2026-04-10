<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'status';

    protected $primaryKey = 'statusID';
    protected $fillable = [
        'statusValue',
        'isDeleted'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'staID', 'statusID');
    }
}
