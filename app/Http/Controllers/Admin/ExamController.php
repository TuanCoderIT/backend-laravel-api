<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'duration' => 'required|integer',
        ]);

        $exam = Exam::create($validated);
        return response()->json($exam, 201);
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $exam->update($request->only(['name', 'duration']));
        return response()->json($exam);
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
