<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'finished' => 'bool',
        'canceled' => 'bool',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function line_items()
    {
        return $this->hasMany(LineItem::class);
    }
}
