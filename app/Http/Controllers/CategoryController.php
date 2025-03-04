<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allCategories = Category::all();
        $categories = Category::paginate($request->input('limit', 10));
        $finalResult = $request->input('limit') ? $categories : $allCategories;
        return response()->json($finalResult);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $imagePath = asset('storage/' . $path); // تصحيح الرابط لاستخدام asset()
        }

        $category = Category::create([
            'name' => $request->name,
            'image' => $imagePath, // تخزين الرابط الصحيح
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return response()->json(Category::findOrFail($id));
    }

    /**
     * Edit the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'title' => 'required',
        ]);

        $category->title = $request->title;

        if ($request->hasFile('image')) {
            // حذف الصورة القديمة
            if ($category->image) {
                $oldImagePath = str_replace('/storage', 'public', $category->image);
                Storage::delete($oldImagePath);
            }

            // رفع الصورة الجديدة
            $file = $request->file('image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/images', $filename);

            // حفظ الرابط الجديد
            $category->image = Storage::url($path);
        }

        $category->save();

        return response()->json(['message' => 'Category updated successfully', 'category' => $category], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // حذف الصورة إذا كانت موجودة
        if ($category->image) {
            $imagePath = str_replace('/storage', 'public', $category->image);
            Storage::delete($imagePath);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }

    /**
     * Search for categories by title.
     */
    public function search(Request $request)
    {
        $query = $request->input('title');
        $results = Category::where('title', 'like', "%$query%")->get();
        return response()->json($results);
    }
}
