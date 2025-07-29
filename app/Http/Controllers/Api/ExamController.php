<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamRequest;
use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    // GET /api/exams => list đề thi
    public function index(Request $request)
    {
        $query = Exam::query()
            ->with('category')
            ->withCount('questions');

        if ($request->has('category_id') && $request->category_id != 'All') {
            $query->where('category_id', $request->category_id);
        }

        return response()->json($query->get());
    }
    // POST /api/exams => tạo đề thi
    public function store(ExamRequest $request)
    {
        $exam = Exam::create($request->validated());
        return response()->json($exam, 201);
    }

    // GET /api/exams/{id} => chi tiết đề thi + câu hỏi
    public function show($id)
    {
        $exam = Exam::with('questions')
            ->withCount(['results as enrollment_count'])
            ->findOrFail($id);
        return response()->json($exam);
    }
    // PUT /api/exams/{id} => cập nhật đề thi
    public function update(ExamRequest $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $exam->update($request->validated());
        return response()->json($exam);
    }

    // DELETE /api/exams/{id} => xóa đề thi
    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();
        return response()->json(null, 204);
    }
}
