<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class XpLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'source_type',
        'source_id',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
