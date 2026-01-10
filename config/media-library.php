<?php

return [

    /*
     * Disk mặc định lưu media (public / s3 / cloudinary)
     */
    'disk_name' => env('MEDIA_DISK', 'cloudinary'),

    /*
     * Model media mặc định
     */
    'media_model' => Spatie\MediaLibrary\MediaCollections\Models\Media::class,

    /*
     * Thư mục con trong storage (ví dụ: storage/app/public/media)
     */
    'prefix' => 'media',

    /*
     * Giới hạn kích thước file (10MB)
     */
    'max_file_size' => 10 * 1024 * 1024,

    /*
     * Queue cho image conversions
     */
    'queue_conversions_by_default' => true,

    /*
     * Cho phép responsive images (ảnh nhiều kích thước)
     */
    'responsive_images' => [
        'enabled' => true,
    ],

    /*
     * Khi xóa model => xóa media đi kèm
     */
    'remove_media_on_model_delete' => true,

    /*
     * Không giữ media khi soft delete
     */
    'delete_preserving_media' => false,
];
