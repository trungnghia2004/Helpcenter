<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $primaryKey = 'productID';

    protected $fillable = [
        'productName',
        'productBuyPrice',
        'productSellPrice',
        'productForGender',
        'isDeleted',
        'cateID',
        'productCode',
        'productDesc'
    ];
    public function category()
    {
        return $this->belongsTo(Category::class, 'cateID', 'categoryID');
    }
    public function productDetails()
    {
        return $this->hasMany(ProductDetail::class,'prdID','productID');
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'prdID', 'productID');
    }
    public function comments()
    {
        return $this->hasMany(CommentAndRate::class, 'productID', 'productID');
    }
    public function firstImage()
    {
        return $this->hasOne(ProductImage::class, 'prdID', 'productID')->orderBy('imageID');
    }


    public static function generateProductCode($prefix)
    {
        $prefixMap = [
            'áo thun' => 'AT',
            'quần jeans' => 'QJ',
            'quần đùi' => 'QD',
            'áo khoác' => 'AK',
            'quần short' => 'QS',
        ];

        $lastProduct = self::where('productCode', 'LIKE', $prefix . '%')
            ->orderBy('productCode', 'desc')
            ->first();

        if ($lastProduct) {
            $lastNumber = intval(substr($lastProduct->productCode, strlen($prefix)));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

}
