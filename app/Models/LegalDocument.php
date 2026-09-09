<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $fillable = ['slug', 'title', 'content', 'updated_by'];
}
