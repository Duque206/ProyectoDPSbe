<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    public $timestamps = false;

    protected $guarded = [
        'id',
        'uuid'
    ];

    protected $hidden = [
        'description'
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class)->withPivot('quantity');
    }

    public function line_items()
    {
        return $this->hasMany(LineItem::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'line_items');
    }

    public function getQuantityAttribute()
    {
        if (Auth::user()->warehouse != null) {
            return $this->warehouses->where('id', Auth::user()->warehouse->id)->first()?->pivot->quantity;
        }
    }
}
