<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'gender',
        'status',
        'avatar',
        'phone_number',
        'date_of_birth',
        'bio',
        'last_login'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'datetime',
            'last_login' => 'datetime',
        ];
    }

    /**
     * Kiểm tra xem user có phải là admin không
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // --- Relations ---
    public function courseProgress()
    {
        return $this->hasMany(CourseProgress::class);
    }

    public function unlockedLessons()
    {
        return $this->hasMany(CourseUnlock::class);
    }

    // Kiểm tra bài đã mở khóa
    public function hasUnlocked($lessonId)
    {
        return $this->unlockedLessons()->where('lesson_id', $lessonId)->exists();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function groupMemberships()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function groupsOwned()
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class);
    }
}
