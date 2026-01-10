<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocumentStoreRequest;
use App\Http\Requests\Admin\DocumentUpdateRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\TokenPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Document::with(['owner', 'category']);

        if ($request->category_id && $request->category_id !== 'All') {
            $query->where('category_id', $request->category_id);
        }

        // Only admin can see draft
        if (!Auth::user() || Auth::user()->role !== 'admin') {
            $query->where('status', 'published');
        }

        $documents = $query->get();

        // Attach token price
        foreach ($documents as $doc) {
            $doc->price_token = TokenPricing::where('target_type', 'document')
                ->where('target_id', $doc->id)
                ->value('price_token') ?? 0;
        }

        return response()->json([
            'data' => DocumentResource::collection($documents),
        ]);
    }

    public function show($id): JsonResponse
    {
        $document = Document::with(['owner', 'category'])->findOrFail($id);

        if (!Auth::user() || Auth::user()->role !== 'admin') {
            if ($document->status !== 'published') {
                return response()->json(['message' => 'Document not found'], 404);
            }
        }

        $document->price_token = TokenPricing::where('target_type', 'document')
            ->where('target_id', $document->id)
            ->value('price_token') ?? 0;

        return response()->json([
            'data' => new DocumentResource($document),
        ]);
    }

    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Not authenticated'], 401);
        }
        $documentData = $request->except('price_token');
        $documentData['owner_id'] = $user->id;
        $document = Document::create($documentData);

        // Lưu token pricing
        TokenPricing::updateOrCreate(
            [
                'target_type' => 'document',
                'target_id'   => $document->id
            ],
            [
                'price_token' => $request->price_token ?? 0
            ]
        );

        /// 🟦 Upload PDF
        if ($request->hasFile('file')) {
            $pdfMedia = $document->addMedia($request->file('file'))
                ->toMediaCollection('documents');
            $document->file_url = $pdfMedia->getUrl();
            $document->file_type = $pdfMedia->mime_type;
            $document->file_size = $pdfMedia->size;
        }

         // 🟩 Upload thumbnail
        if ($request->hasFile('thumbnail')) {
            $thumbMedia = $document->addMedia($request->file('thumbnail'))
                ->toMediaCollection('thumbnails');
            $document->thumbnail = $thumbMedia->getUrl();
        }
        $document->save();
        $document->load(['owner', 'category']);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => new DocumentResource($document),
        ], 201);
    }

    public function update(DocumentUpdateRequest $request, $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        $documentData = $request->except('price_token');
        $document->update($documentData);
        // Cập nhật giá token
        TokenPricing::updateOrCreate(
            [
                'target_type' => 'document',
                'target_id'   => $document->id
            ],
            [
                'price_token' => $request->price_token
            ]
        );

        // Replace PDF file if new one uploaded
        if ($request->hasFile('file')) {
            $document->clearMediaCollection('documents');
            $document->addMedia($request->file('file'))
                ->toMediaCollection('documents');
        }

        // Replace thumbnail if new one uploaded
        if ($request->hasFile('thumbnail')) {
            $document->clearMediaCollection('thumbnails');
            $document->addMedia($request->file('thumbnail'))
                ->toMediaCollection('thumbnails');
        }

        $document->load(['owner', 'category']);

        return response()->json([
            'message' => 'Document updated successfully',
            'data' => new DocumentResource($document),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $document = Document::findOrFail($id);

        // Remove media
        $document->clearMediaCollection('documents');
        $document->clearMediaCollection('thumbnails');

        // Remove token pricing
        TokenPricing::where('target_type', 'document')
            ->where('target_id', $id)
            ->delete();

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }

    public function download($id)
    {
        $document = Document::findOrFail($id);

        $media = $document->getFirstMedia('documents');
        if (!$media) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }


}
