<?php

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/products/search', function (Request $request) {
    $q = trim((string) $request->query('q', ''));

    if ($q === '') {
        return collect();
    }

    return DB::table('products as p')
        ->leftJoin('categories as c', 'c.categoryID', '=', 'p.cateID')
        ->where('p.isDeleted', 0)
        ->where(function ($query) use ($q) {
            $query->where('p.productName', 'like', "%{$q}%")
                ->orWhere('p.productCode', 'like', "%{$q}%")
                ->orWhere('c.categoryName', 'like', "%{$q}%");
        })
        ->select(
            'p.productID',
            'p.productCode',
            'p.productName',
            'p.productSellPrice',
            'p.productForGender',
            'c.categoryName'
        )
        ->orderBy('p.productID', 'desc')
        ->take(12)
        ->get();
});

Route::get('/products/by-code/{code}', function (string $code) {
    return Product::where('isDeleted', 0)
        ->whereRaw('UPPER(productCode) = ?', [strtoupper($code)])
        ->select('productID','productCode','productName','productSellPrice','productDesc','productForGender')
        ->firstOrFail();
});

Route::get('/products/by-category', function (Request $request) {
    $q = trim((string) $request->query('q', ''));

    if ($q === '') {
        return collect();
    }

    return DB::table('products as p')
        ->join('categories as c', 'c.categoryID', '=', 'p.cateID')
        ->where('p.isDeleted', 0)
        ->where('c.isDeleted', 0)
        ->where('c.categoryName', 'like', "%{$q}%")
        ->select(
            'p.productID',
            'p.productCode',
            'p.productName',
            'p.productSellPrice',
            'p.productForGender',
            'c.categoryName'
        )
        ->orderBy('p.productID', 'desc')
        ->take(20)
        ->get();
});

Route::get('/products/{id}/variants', function ($id) {
    $rows = DB::table('product_details as pd')
        ->join('sizes as s', 's.sizeId', '=', 'pd.sizeId')
        ->join('colors as c', 'c.colorId', '=', 'pd.colorId')
        ->where('pd.prdID', $id)
        ->where('pd.isDeleted', 0)
        ->where('s.isDeleted', 0)
        ->where('c.isDeleted', 0)
        ->select('pd.id','s.sizeName','c.colorName','pd.productQuantity')
        ->orderBy('s.sizeName')
        ->orderBy('c.colorName')
        ->get();

    return $rows;
});
