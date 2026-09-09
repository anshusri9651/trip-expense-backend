<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'paid_by_member_id',
        'title',
        'amount',
        'category',
        'expense_date',
        'expense_time',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'expense_date' => 'date',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(TripMember::class, 'paid_by_member_id');
    }

    public function splits()
    {
        return $this->hasMany(ExpenseSplit::class);
    }
}
