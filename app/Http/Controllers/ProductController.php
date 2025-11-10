<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk dengan dukungan pencarian dan pengurutan.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('unit');

        // Filter berdasarkan kata kunci
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        // Urutan berdasarkan parameter 'sort'
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'A-Z':
                    $query->orderBy('product_name', 'asc');
                    break;
                case 'Z-A':
                    $query->orderBy('product_name', 'desc');
                    break;
                case 'stok-terbanyak':
                    $query->orderBy('stock_quantity', 'desc');
                    break;
                case 'stok-sedikit':
                    $query->orderBy('stock_quantity', 'asc');
                    break;
                default:
                    $query->latest('product_id');
            }
        } else {
            $query->latest('product_id');
        }

        $products = $query->paginate(12)->appends($request->query());

        return view('products.index', compact('products'));
    }

    /**
     * Menampilkan formulir untuk menambahkan produk baru.
     */
    public function create(): View
    {
        $this->authorize('create', Product::class);

        $units = Unit::all();
        $suppliers = Supplier::all();

        return view('products.create', compact('units', 'suppliers'));
    }

    /**
     * Menyimpan produk baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'product_code' => 'required|string|max:50|unique:products,product_code',
            'product_name' => 'required|string|max:200',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'unit_id' => 'required|exists:units,unit_id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('product-images', 'public');
        }

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Produk baru berhasil ditambahkan!');
    }

    /**
     * Menampilkan detail lengkap suatu produk.
     */
    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        return view('products.show', compact('product'));
    }

    /**
     * Menampilkan formulir edit untuk produk yang ada.
     */
    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $units = Unit::all();
        $suppliers = Supplier::all();

        return view('products.edit', compact('product', 'units', 'suppliers'));
    }

    /**
     * Memperbarui data produk yang ada.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($product->product_id, 'product_id')],
            'product_name' => 'required|string|max:200',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'unit_id' => 'required|exists:units,unit_id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('product-images', 'public');
        }

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diupdate!');
    }

    /**
     * Menghapus produk (soft delete).
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        // Opsional: hapus file gambar secara fisik
        // if ($product->image_path) {
        //     Storage::disk('public')->delete($product->image_path);
        // }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus!');
    }
}