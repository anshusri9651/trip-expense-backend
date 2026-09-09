<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseSplit extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'trip_member_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function member()
    {
        return $this->belongsTo(TripMember::class, 'trip_member_id');
    }
}
