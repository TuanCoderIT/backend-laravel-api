<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\Admin\CategoryRequest;

class CategoryController extends Controller
{
    // GET /api/categories => list all categories
    public function index()
    {
        return response()->json(Category::all());
    }
    // POST /api/categories => create a new category
    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());
        return response()->json($category, 201);
    }

    // GET /api/categories/{id} => show a specific category
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }
    // PUT /api/categories/{id} => update a specific category
    public function update(CategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());
        return response()->json($category);
    }

    // DELETE /api/categories/{id} => delete a specific category
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(null, 204);
    }
}
