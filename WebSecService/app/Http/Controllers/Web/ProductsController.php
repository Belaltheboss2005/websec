<?php
namespace App\Http\Controllers\Web;

use Illuminate\Support\Facades\Log;
use App\Models\BoughtProduct;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductsController extends Controller {

	use ValidatesRequests;


    public function buy($id)
    {
        $product = Product::findOrFail($id);
        $user = auth()->user();

        // Check if the product is out of stock
        if ($product->stock <= 0) {
            return redirect()->route('products_list')->withErrors(['stock' => 'This product is out of stock and cannot be purchased.']);
        }

        // Check if the user has sufficient credit
        if ($user->credit < $product->price) {
            return view('products.insufficient_credit');
            // return redirect()->route('products_list')->withErrors(['credit' => 'You do not have enough credit to purchase this product.']);
        }

        // Deduct the product price from the user's credit
        $user->credit -= $product->price;
        $user->save();

        // Decrease the product stock by 1
        $product->stock -= 1;
        $product->save();

        // Add the purchase record using the BoughtProduct model
        BoughtProduct::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        // Add a success message to the session
        return redirect()->route('products_list')->with('success', 'Product purchased successfully!');
    }



    public function boughtProducts()
    {
        $user = auth()->user();

        // Check if the user is a customer
        if ($user->hasRole('Customer')) {
            // Fetch only the products bought by the logged-in customer
            $boughtProducts = BoughtProduct::with(['user', 'product'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'DESC')
                ->get();
        } else {
            // Fetch all bought products for employees or admins
            $boughtProducts = BoughtProduct::with(['user', 'product'])
                ->orderBy('created_at', 'DESC')
                ->get();
        }

        return view('products.boughtproductlist', compact('boughtProducts'));
    }


    public function list(Request $request)
    {
        $query = Product::select("products.*");

        $query->when($request->keywords,
            fn($q) => $q->where("name", "like", "%$request->keywords%"));

        $query->when($request->min_price,
            fn($q) => $q->where("price", ">=", $request->min_price));

        $query->when($request->max_price,
            fn($q) => $q->where("price", "<=", $request->max_price));

        $query->when($request->order_by,
            fn($q) => $q->orderBy($request->order_by, $request->order_direction ?? "ASC"));

        // Exclude products with stock equal to 0
        $query->where('stock', '>', 0);

        $products = $query->get();

        return view('products.list', compact('products'));
    }

	public function edit(Request $request, Product $product = null) {

		if(!auth()->user()) return redirect('/');

        if (!auth()->user()->hasPermissionTo('edit_products')) {
            Log::warning('Unauthorized access attempt to edit product by user ID: ' . auth()->id());
            abort(401); // Unauthorized
        }

		$product = $product??new Product();

		return view('products.edit', compact('product'));
	}



    public function save(Request $request, Product $product = null) {

        if (!auth()->user()->hasPermissionTo('edit_products')) {
            Log::warning('Unauthorized access attempt to save edit product by user ID: ' . auth()->id());
            abort(401); // Unauthorized
        }

        $this->validate($request, [
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:128'],
            'model' => ['required', 'string', 'max:256'],
            'description' => ['required', 'string', 'max:1024'],
            'price' => ['required', 'numeric'],
            'stock' => ['required', 'integer', 'min:0'], // Added validation for stock
        ]);

        $product = $product ?? new Product();
        $product->fill($request->all());
        $product->save();

        return redirect()->route('products_list');
    }

	public function delete(Request $request, Product $product) {

        if (!auth()->user()->hasPermissionTo('edit_products')) {
            Log::warning('Unauthorized access attempt to delete product by user ID: ' . auth()->id());
            abort(401); // Unauthorized
        }

		if(!auth()->user()->hasPermissionTo('delete_products')) abort(401);

		$product->delete();

		return redirect()->route('products_list');
	}
}
