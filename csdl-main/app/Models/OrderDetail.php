<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $fillable = [
        'orderID',
        'productDetailID',
        'orderQuantity',
        'unitPrice'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }

    public function productDetail()
    {
        return $this->belongsTo(ProductDetail::class, 'productDetailID', 'id');
    }
}
