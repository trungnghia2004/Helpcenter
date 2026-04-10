<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountProgram extends Model
{
    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'start_date',
        'end_date',
    ];
    public function order()
    {
        return $this->hasMany(Order::class, 'discount_program_id', 'id');
    }
    public function isActive()
    {
        $now = now();
        return $this->start_date <= $now && $this->end_date >= $now;
    }

    public static function getActivePrograms()
    {
        $now = now();
        return self::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }


    public function calculateDiscount($originalPrice)
    {
        if ($this->discount_type === 'percent') {
            $discount = $originalPrice * ($this->discount_value / 100);
        } else {
            $discount = $this->discount_value;
        }

        if ($this->max_discount !== null) {
            $discount = min($discount, $this->max_discount);
        }

        return round($discount, 2);
    }
}
