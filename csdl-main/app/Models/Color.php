<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $primaryKey = 'colorId';
    protected $fillable = ['colorName','isDeleted'];

    public function productDetails()
    {
        return $this->hasMany(ProductDetail::class,'colorId','colorId');
    }
}


