<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDetail extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = ['prdID', 'sizeId', 'colorId', 'productQuantity','isDeleted'];

    public function product()
    {
        return $this->belongsTo(Product::class,'prdID','productID');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'sizeId', 'sizeId');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'colorId', 'colorId');
    }

}

