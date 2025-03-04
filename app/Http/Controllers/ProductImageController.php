<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Store a newly created image.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'product_id' => 'required|exists:products,id',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public'); // تخزين الصورة في storage/app/public/products
            $imageUrl = Storage::url($path); // الحصول على رابط الصورة

            $productImage = ProductImage::create([
                'product_id' => $request->product_id,
                'image' => $imageUrl,
            ]);

            return response()->json([
                'message' => 'Image uploaded successfully',
                'image' => $productImage,
            ], 201);
        }

        return response()->json(['error' => 'No image uploaded'], 400);
    }

    /**
     * Remove the specified image from storage.
     */
    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);

        // حذف الصورة من التخزين
        if ($image->image) {
            Storage::delete(str_replace('/storage/', '', $image->image)); // حذف الملف من storage
        }

        $image->delete();

        return response()->json(['message' => 'Image deleted successfully'], 200);
    }
}
