<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $primaryKey = 'imageID';

    protected $fillable = [
        'prdID',
        'imageLink'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'prdID', 'productID');
    }
}
