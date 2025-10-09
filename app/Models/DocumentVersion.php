<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id',
        'version',
        'media_id',
        'conversion_status',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
