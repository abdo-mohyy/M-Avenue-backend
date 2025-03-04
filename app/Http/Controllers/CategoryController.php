<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
        return $finalResult;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'image' => 'required|image'
        ]);

        $category = new Category();
        $category->title = $request->title;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = date('YmdHis') . '.' . $file->getClientOriginalExtension();

            // حفظ الصورة داخل storage/app/public/images
            $path = $file->storeAs('public/images', $filename);

            // تخزين رابط الوصول للصورة
            $category->image = Storage::url($path);
        }

        $category->save();

        return response()->json(['message' => 'Category created successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category, $id)
    {
        return Category::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category, $id, Request $request)
{
    $category = Category::findOrFail($id);

    $request->validate([
        'title' => 'required',
    ]);

    $category->title = $request->title;

    if ($request->hasFile('image')) {
        // حذف الصورة القديمة إذا كانت موجودة
        $oldpath = public_path('images/' . basename($category->image));
        if (File::exists($oldpath)) {
            File::delete($oldpath);
        }

        // رفع الصورة الجديدة
        $file = $request->file('image');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images'), $filename);
        $category->image = url('images/' . $filename);
    }

    $category->save();

    return response()->json(['message' => 'Category updated successfully', 'category' => $category], 200);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

     // Search On Users
     public function search(Request $request)
     {
            $query = $request->input('title');
            $results = Category::where('title', 'like', "%$query%")->get();
            return response()->json($results);
     }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category, $id)
{
    $category = Category::findOrFail($id);

    // حذف الصورة إذا كانت موجودة
    $path = public_path('images/' . basename($category->image));
    if (File::exists($path)) {
        File::delete($path);
    }

    $category->delete();

    return response()->json(['message' => 'Category deleted successfully'], 200);
}
}
// ===
