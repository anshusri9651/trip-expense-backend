<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'trip_name',
        'group_name',
        'description',
        'status',
        'currency',
        'currency_symbol',
        'start_date',
        'end_date',
        'cover_color',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function members()
    {
        return $this->hasMany(TripMember::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }

    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }
}
