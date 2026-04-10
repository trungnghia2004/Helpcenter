<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartDetail extends Model
{
    use HasFactory;

    protected $table = 'cart_details';

    protected $fillable = [
        'cartID',
        'productID',
        'productDetailID',
        'quantity',
        'isDeleted',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cartID');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productID');
    }
    public function productDetail()
    {
        return $this->belongsTo(ProductDetail::class, 'productDetailID','id');
    }

}
