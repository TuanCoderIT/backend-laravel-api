<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    // GET /api/exams => list đề thi
    public function index(Request $request)
    {
        $query = Exam::query();

        if ($request->has('category_id') && $request->category_id != 'All') {
            $query->where('category_id', $request->category_id);
        }

        return response()->json($query->get());
    }

    // GET /api/exams/{id} => chi tiết đề thi + câu hỏi
    public function show($id)
    {
        $exam = Exam::with('questions')->findOrFail($id);
        return response()->json($exam);
    }
}
