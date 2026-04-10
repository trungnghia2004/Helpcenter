<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $primaryKey = 'sizeId';
    protected $fillable = ['sizeName','isDeleted'];

    public function productDetails()
    {
        return $this->hasMany(ProductDetail::class,'sizeId','sizeId');
    }
}
