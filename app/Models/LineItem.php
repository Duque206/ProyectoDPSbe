<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function setQuantityAttribute($quantity)
    {
        $this->attributes['quantity'] = $quantity;
        if ($this->unit_price != null) {
            $this->item_total = $this->quantity * $this->unit_price;
        }
    }

    public function setUnitPriceAttribute($price)
    {
        $this->attributes['unit_price'] = $price;
        if ($this->quantity != null) {
            $this->item_total = $this->quantity * $this->unit_price;
        }
    }
}
