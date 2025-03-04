<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cartItems = Cart::with('Products')->where('user_id', $userId)->get();

        return response()->json($cartItems);
    }

    /**
     * Check stock availability.
     */
    public function check(Request $request)
    {
        $request->validate([
            'count' => 'required|numeric|min:1',
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($request->count > $product->stock) {
            return response()->json(['error' => 'Requested quantity exceeds available stock'], 400);
        }

        return response()->json(['message' => 'Stock is available'], 200);
    }

    /**
     * Store a newly created item in cart.
     */
    public function store(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'count' => 'required|numeric|min:1',
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($request->count > $product->stock) {
            return response()->json(['error' => 'Requested quantity exceeds available stock'], 400);
        }

        $cart = Cart::where('user_id', $userId)->where('product_id', $request->product_id)->first();

        if ($cart) {
            $newCount = $cart->count + $request->count;

            if ($newCount > $product->stock) {
                return response()->json(['error' => 'Total quantity exceeds available stock'], 400);
            }

            $cart->update(['count' => $newCount]);

            return response()->json(['message' => 'Cart updated successfully', 'cart' => $cart], 200);
        } else {
            $cart = Cart::create([
                'count' => $request->count,
                'user_id' => $userId,
                'product_id' => $request->product_id,
            ]);

            return response()->json(['message' => 'Cart created successfully', 'cart' => $cart], 201);
        }
    }

    /**
     * Update the count of an item in cart.
     */
    public function update(Request $request, $id)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'count' => 'required|numeric|min:1',
        ]);

        $cart = Cart::where('user_id', $userId)->where('id', $id)->first();

        if (!$cart) {
            return response()->json(['error' => 'Item not found in cart'], 404);
        }

        $product = Product::findOrFail($cart->product_id);

        if ($request->count > $product->stock) {
            return response()->json(['error' => 'Requested quantity exceeds available stock'], 400);
        }

        $cart->update(['count' => $request->count]);

        return response()->json(['message' => 'Cart item updated successfully', 'cart' => $cart], 200);
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy($id)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cart = Cart::where('user_id', $userId)->where('id', $id)->first();

        if (!$cart) {
            return response()->json(['error' => 'Item not found in cart'], 404);
        }

        $cart->delete();

        return response()->json(['message' => 'Cart item removed successfully'], 200);
    }
}
