<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpadteQuestionRequest;
use App\Models\Question;

class QuestionController extends Controller
{
    // GET /api/questions - Danh sách tất cả câu hỏi
    public function index()
    {
        return response()->json(Question::all());
    }

    // POST /api/questions - Tạo mới câu hỏi
    public function store(StoreQuestionRequest $request)
    {
        $data = $request->validated();

        $question = Question::create($data);

        // Nếu có exam_id gửi kèm thì attach vào bảng pivot
        if ($request->has('exam_id')) {
            $question->exams()->attach($request->exam_id, [
                'order' => 0, // có thể thay bằng logic sắp xếp nếu muốn
            ]);
        }

        return response()->json($question, 201);
    }

    // GET /api/questions/{id} - Xem chi tiết câu hỏi
    public function show($id)
    {
        $question = Question::findOrFail($id);
        return response()->json($question);
    }

    // PUT /api/questions/{id} - Cập nhật câu hỏi
    public function update(UpadteQuestionRequest $request, $id)
    {
        $question = Question::findOrFail($id);
        $question->update($request->validated());
        return response()->json($question);
    }

    // DELETE /api/questions/{id} - Xoá câu hỏi
    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        $question->delete();
        return response()->json(null, 204);
    }
}
