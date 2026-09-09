<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdminActivity extends Model { protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address']; public function user() { return $this->belongsTo(User::class); } }
