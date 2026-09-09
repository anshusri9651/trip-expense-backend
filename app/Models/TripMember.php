<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'name',
        'avatar_color',
        'is_owner',
    ];

    protected $casts = [
        'is_owner' => 'boolean',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expensesPaid()
    {
        return $this->hasMany(Expense::class, 'paid_by_member_id');
    }

    public function expenseSplits()
    {
        return $this->hasMany(ExpenseSplit::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->expensesPaid()->sum('amount');
    }

    public function getTotalShareAttribute(): float
    {
        return $this->expenseSplits()->sum('amount');
    }

    public function getNetBalanceAttribute(): float
    {
        return $this->total_paid - $this->total_share;
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        return $initials ?: 'U';
    }
}
