<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'owner_id',
        'category_id',
        'title',
        'description',
        'is_premium',
        'status',
        'file_url',
        'file_type',
        'file_size',
        'thumbnail',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'file_size' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function registerMediaCollections(): void
    {
        // 🟦 PDF collection
        $this->addMediaCollection('documents')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        // 🟩 Thumbnail collection (ảnh)
        $this->addMediaCollection('thumbnails')
            ->singleFile() // chỉ giữ ảnh cuối cùng
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}