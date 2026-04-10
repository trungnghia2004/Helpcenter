<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CommentAndRateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountProgramController;
use App\Http\Controllers\DisplayProductController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\OrdersManageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


Route::get('/login',[\App\Http\Controllers\LoginController::class,'viewLoginAndRegister'])->name('login');
Route::post('/login',[\App\Http\Controllers\LoginController::class,'login'])->name('login');
Route::post('/register',[\App\Http\Controllers\LoginController::class,'register'])->name('register');
Route::post('/logout', [\App\Http\Controllers\LoginController::class,'logout'])->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('adminDashboard');

    Route::resource('categories', \App\Http\Controllers\CategoriesController::class);

    Route::resource('products', \App\Http\Controllers\ProductController::class);

    Route::resource('customer', \App\Http\Controllers\CustomerAccountController::class);

    Route::resource('discount_programs', DiscountProgramController::class);
    Route::resource('order-manage', \App\Http\Controllers\OrdersManageController::class);

    //    Status Change
    Route::post('/orders/{id}/approve', [OrdersManageController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{id}/deliver', [OrdersManageController::class, 'deliver'])->name('orders.deliver');
    Route::post('/orders/{id}/cancel', [OrdersManageController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{id}', [OrdersManageController::class, 'showDetails'])->name('orders.details');
    Route::post('/orderDetails/{id}', [OrdersManageController::class, 'addMoreDetails'])->name('ordersDetails.add');
    Route::get('/orders-filter', [OrdersManageController::class, 'filterOrders']);
    // Bo loc
    Route::get('/admin/get-sizes/{productID}', [OrdersManageController::class, 'getSizes']);
    Route::get('/admin/get-colors/{productID}/{sizeId}', [OrdersManageController::class, 'getColors']);

//    Link Product Details

    Route::get('/product-details/{product}/details', [ProductDetailController::class, 'index'])->name('product-details.index');

    Route::post('/products/{product}/details', [ProductDetailController::class, 'store'])->name('product-details.store');

    Route::delete('/product-details/{details}', [ProductDetailController::class, 'destroy'])->name('product-details.destroy');

    Route::put('/product-details/{details}', [ProductDetailController::class, 'update'])->name('product-details.update');

//    Link Image Product

    Route::get('/product-images/{product}/images', [ProductImageController::class, 'index'])->name('product-images.index');

    Route::post('/products/{product}/images', [ProductImageController::class, 'upload'])->name('product-images.upload');

    Route::delete('/product-images/{image}', [ProductImageController::class, 'destroy'])->name('product-images.destroy');

    Route::put('/product-images/{image}', [ProductImageController::class, 'update'])->name('product-images.update');

    // Route::get('admin/vnpay_return', [OrdersManageController::class, 'vnpayReturn'])->name('vnpay.adminReturn');


});

Route::get('/', [\App\Http\Controllers\DisplayProductController::class, 'customerPage'])->name('customerPage');



Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/updatePassword', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');

    // Hien thi san pham
    Route::get('/product-details/{product}', [DisplayProductController::class, 'productDetails'])->name('productDetails');

//    Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/update/{id}', [CartController::class, 'updateQuantity'])->name('cart.update');
    Route::post('/cart/remove/{id}', [CartController::class, 'removeItem'])->name('cart.remove');

    Route::post('/get-colors-by-size', [ProductController::class, 'getColorsBySize']);
    Route::get('/shop', [ProductController::class, 'findByPrice'])->name('products.findByPrice');
//    Checkout Control
    Route::get('/checkout', [OrdersController::class, 'index'])->name('checkout.index');
    Route::post('/orders', [OrdersController::class, 'storeFromCustomer'])->name('orders.storeFromCustomer');
    Route::get('/order-list', [OrdersController::class, 'showOrders'])->name('orders.showOrders');
    Route::get('/order-details{order}', [OrdersController::class, 'showDetails'])->name('orders.showDetails');
    Route::post('/orderDelivered/{id}/', [OrdersController::class, 'delivered'])->name('orders.delivered');
    Route::post('/ordersCus/{id}/cancel', [OrdersController::class, 'cancel'])->name('orderCus.cancel');

    // Route::get('/vnpay_return', [OrdersController::class, 'vnpayReturn'])->name('vnpay.return');
    Route::get('/discount/{id}', [App\Http\Controllers\CartController::class, 'getDiscount']);

    Route::post('/comments', [CommentAndRateController::class, 'store'])->name('comments.store');
    Route::put('/comments/{id}', [CommentAndRateController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{id}', [CommentAndRateController::class, 'destroy'])->name('comments.destroy');
});
Route::get('/showProduct', [\App\Http\Controllers\DisplayProductController::class, 'index'])->name('showProduct');
Route::get('/products/category/{cateID}', [ProductController::class, 'filterByCategory'])->name('products.byCategory');
Route::get('/products/gender/{genderID}', [ProductController::class, 'filterByGender'])->name('products.byGender');
