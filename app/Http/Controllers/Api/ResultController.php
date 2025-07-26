<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()?->id ?? $request->query('user_id');

        if (!$userId) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $results = Result::with('exam')
            ->where('user_id', $userId)
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json($results);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'score' => 'required|integer',
            'total' => 'required|integer',
            'percentage' => 'required|integer',
            'time_spent' => 'required|integer',
            'completed_at' => 'required|date',
        ]);

        $result = Result::create([
            'user_id' => $request->user()->id, // ✅ lấy từ token
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Result saved successfully',
            'result' => $result,
        ], 201);
    }
}
