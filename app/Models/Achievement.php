<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'type',
        'rarity',
        'target_value',
        'xp_reward',
        'token_reward',
        'reward_title',
        'reward_trophy',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'target_value' => 'integer',
        'xp_reward' => 'integer',
        'token_reward' => 'integer',
    ];

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
