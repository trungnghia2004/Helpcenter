<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'cart';
    protected $primaryKey = 'cartID';

    protected $fillable = [
        'userID',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userID');
    }

    public function cartDetails()
    {
        return $this->hasMany(CartDetail::class, 'cartID', 'cartID');
    }
}
