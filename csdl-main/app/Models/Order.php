<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $primaryKey = 'orderID';
    protected $fillable = [
        'cusID',
        'adminID',
        'staID',
        'payID',
        'orderPhoneNumber',
        'shipping_street',
        'shipping_ward',
        'shipping_district',
        'shipping_city',
        'totalPrice',
        'discount_program_id'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'cusID','id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'adminID','id');
    }
    public function discount()
    {
        return $this->belongsTo(DiscountProgram::class, 'discount_program_id','id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'staID', 'statusID');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'orderID', 'orderID');
    }
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payID', 'paymentID');
    }
}
