<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentAndRate extends Model
{
    use HasFactory;

    protected $table = 'comment_and_rate';
    protected $primaryKey = 'idComment';

    protected $fillable = [
        'cusID', 'productID', 'contentComment', 'rate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'cusID');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productID', 'productID');
    }
}
