<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'is_premium',
        'price_tokens',
        'status',
    ];

    protected $appends = ['latest_file_url'];

    public function getLatestFileUrlAttribute()
    {
        $media = $this->latestVersion?->media;
        return $media ? $media->getUrl() : null;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function latestVersion()
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany();
    }
}
