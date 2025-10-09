<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::with('latestVersion.media')->paginate(10);
        return response()->json($documents);
    }

    public function show($id)
    {
        $document = Document::with(['owner', 'versions.media'])->findOrFail($id);
        return response()->json($document);
    }
    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'is_premium'   => 'boolean',
            'price_tokens' => 'integer|min:0',
            'file'         => 'required|file|mimes:pdf,doc,docx,ppt,pptx',
        ]);

        $user = Auth::user();

        // 1. Tạo document
        $document = Document::create([
            'owner_id'     => $user->id,
            'title'        => $request->title,
            'description'  => $request->description,
            'is_premium'   => $request->is_premium ?? false,
            'price_tokens' => $request->price_tokens ?? 0,
            'status'       => 'published',
        ]);

        // 2. Upload file
        $media = $document
            ->addMedia($request->file('file'))
            ->toMediaCollection('original_files');

        // 3. Tạo version đầu tiên
        $version = DocumentVersion::create([
            'document_id'       => $document->id,
            'version'           => 'v1',
            'media_id'          => $media->id,
            'conversion_status' => 'done', // vì không convert PDF
        ]);

        return response()->json([
            'message'  => 'Document created successfully',
            'document' => $document->load('versions.media'),
        ], 201);
    }

    public function download($id)
    {
        $document = Document::with('latestVersion.media')->findOrFail($id);
        $media = $document->latestVersion?->media;

        if (!$media) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    public function addVersion(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx',
        ]);

        $document = Document::findOrFail($id);

        // Kiểm tra quyền (chỉ owner mới được update)
        if ($document->owner_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Lấy version cuối để tính version mới
        $latestVersion = $document->versions()->orderBy('id', 'desc')->first();
        $nextVersionNumber = $latestVersion
            ? intval(str_replace('v', '', $latestVersion->version)) + 1
            : 1;
        $nextVersion = 'v' . $nextVersionNumber;

        // Upload file
        $media = $document
            ->addMedia($request->file('file'))
            ->toMediaCollection('original_files');

        // Tạo version mới
        $version = DocumentVersion::create([
            'document_id'       => $document->id,
            'version'           => $nextVersion,
            'media_id'          => $media->id,
            'conversion_status' => 'done', // không convert nên done luôn
        ]);

        return response()->json([
            'message' => "Version {$nextVersion} added successfully",
            'document' => $document->load('versions.media'),
        ], 201);
    }
}
