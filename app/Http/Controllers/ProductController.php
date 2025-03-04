<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $products = Product::with('Images')->where('status', 'published')->paginate($limit);

        return response()->json($products);
    }

    public function getLastSaleProducts()
    {
        $products = Product::with('Images')->where('status', 'published')
            ->where('discount', '>', 0)
            ->latest()->take(20)->get();

        return response()->json($products);
    }

    public function getLatest()
    {
        $products = Product::with('Images')->where('status', 'published')
            ->where('discount', 0)
            ->where('rating', '<', 5)
            ->latest()->take(60)->get();

        return response()->json($products);
    }

    public function getTopRated()
    {
        $products = Product::with('Images')->where('status', 'published')
            ->where('rating', 5)
            ->latest()->take(32)->get();

        return response()->json($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'discount' => 'required|numeric',
            'About' => 'required',
            'stock' => 'required|numeric',
        ]);

        $product = Product::create([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'About' => $request->About,
            'discount' => $request->discount,
            'stock' => $request->stock,
            'status' => 'published',
        ]);

        return response()->json(['message' => 'Product created successfully', 'product' => $product], 201);
    }

    /**
     * Display a specific product.
     */
    public function show($id)
    {
        $product = Product::where('id', $id)->with('Images')->where('status', 'published')->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'category' => 'required',
            'title' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'discount' => 'required|numeric',
            'stock' => 'required|numeric',
            'About' => 'required',
        ]);

        $product->update([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'About' => $request->About,
            'discount' => $request->discount,
            'stock' => $request->stock,
            'status' => 'published',
        ]);

        // Handle images upload (Use Storage for Railway instead of local files)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public'); // Store in `storage/app/public/products`
                $imageUrl = Storage::url($path); // Get the URL

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $imageUrl,
                ]);
            }
        }

        return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
    }

    /**
     * Search products by title.
     */
    public function search(Request $request)
    {
        $query = $request->input('title');
        $results = Product::with('Images')->where('title', 'like', "%$query%")->get();

        return response()->json($results);
    }

    /**
     * Remove a product from storage.
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Delete associated images
        $productImages = ProductImage::where('product_id', $id)->get();
        foreach ($productImages as $image) {
            Storage::delete(str_replace('/storage/', '', $image->image)); // Delete from storage
            $image->delete();
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
}
