<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamRequest;
use App\Models\Exam;
use Illuminate\Http\Request;
use App\Models\TokenPricing;

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

        $exams = $query->get();
        // Lấy giá token cho từng quiz
        $exams->map(function ($exam) {
            $exam->price_token = TokenPricing::where('target_type', 'quiz')
                ->where('target_id', $exam->id)
                ->value('price_token') ?? 0;
            return $exam;
        });

        return response()->json($exams);
    }
    // POST /api/exams => tạo đề thi
    public function store(ExamRequest $request)
    {
        // Bỏ price_token ra khỏi dữ liệu lưu exams
        $examData = $request->except('price_token');
        $exam = Exam::create($examData);

        // Lưu token pricing
        TokenPricing::updateOrCreate(
            [
                'target_type' => 'quiz',
                'target_id'   => $exam->id
            ],
            [
                'price_token' => $request->price_token ?? 0
            ]
        );

        return response()->json($exam, 201);
    }

    // GET /api/exams/{id} => chi tiết đề thi + câu hỏi
    public function show($id)
    {
        $exam = Exam::with('questions')
            ->withCount(['results as enrollment_count'])
            ->findOrFail($id);

        // Lấy giá token từ bảng token_pricings
        $price = TokenPricing::where('target_type', 'quiz')
            ->where('target_id', $exam->id)
            ->value('price_token') ?? 0;

        // Gắn trực tiếp vào object
        $exam->price_token = $price;

        return response()->json($exam);
    }

    // PUT /api/exams/{id} => cập nhật đề thi
    public function update(ExamRequest $request, $id)
    {
        $exam = Exam::findOrFail($id);

        // Cập nhật quiz
        $examData = $request->except('price_token');
        $exam->update($examData);
        // Cập nhật giá token
        TokenPricing::updateOrCreate(
            [
                'target_type' => 'quiz',
                'target_id'   => $exam->id
            ],
            [
                'price_token' => $request->price_token
            ]
        );

        return response()->json($exam);
    }

    // DELETE /api/exams/{id} => xóa đề thi
    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();

        TokenPricing::where('target_type', 'quiz')
            ->where('target_id', $id)
            ->delete();

        return response()->json(null, 204);
    }
}
