<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait UploadsImage
{
    /**
     * Xử lý việc upload file ảnh.
     * @param Request $request Yêu cầu HTTP.
     * @param string $folder Thư mục lưu trữ (ví dụ: 'groups', 'users').
     * @param string $inputName Tên trường input chứa file (ví dụ: 'cover_image').
     * @return string|null Đường dẫn công khai tới ảnh, hoặc null.
     */
    protected function handleImageUpload(Request $request, string $folder, string $inputName = 'cover_image'): ?string
    {
        if ($request->hasFile($inputName) && $request->file($inputName)->isValid()) {
            $file = $request->file($inputName);

            // Lưu file vào thư mục cụ thể trong disk 'public'
            $path = $file->store($folder, 'public');

            // Trả về đường dẫn công khai (vd: /storage/groups/abc.jpg)
            return '/storage/' . $path;
        }
        return null;
    }

    /**
     * Xóa file ảnh vật lý khỏi server.
     * @param string|null $publicPath Đường dẫn công khai (ví dụ: /storage/groups/...).
     */
    protected function deleteImageFile(?string $publicPath): void
    {
        if ($publicPath) {
            // Chuyển đổi đường dẫn công khai thành đường dẫn trong storage
            $storagePath = str_replace('/storage/', '', $publicPath);

            // Xóa file khỏi disk 'public'
            Storage::disk('public')->delete($storagePath);
        }
    }
}