<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image; // Pastikan library sudah terinstall

class ProductController extends Controller
{
    public function __construct()
    {
        // Middleware Permission
        $this->middleware('can:view-products')->only(['index', 'show']); 
        $this->middleware('can:create-products')->only(['create', 'store']);
        $this->middleware('can:edit-products')->only(['edit', 'update']);
        $this->middleware('can:delete-products')->only(['destroy', 'restore', 'forceDelete']);
    }
    
    /**
     * Menampilkan daftar produk dengan filter dan sorting.
     */
    public function index(Request $request): View
    {
        $query = Product::with(['unit', 'supplier', 'category']);

        // Filter: Sampah (Arsip)
        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        // Filter: Pencarian (Kode atau Nama)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        // Filter: Sorting
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
                case 'terbaru':
                default:
                    $query->latest('product_id');
            }
        } else {
            $query->latest('product_id');
        }

        $products = $query->paginate(12)->appends($request->query());
        return view('admin.products.index', compact('products'));
    }

    /**
     * Form Tambah Produk.
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $suppliers = Supplier::orderBy('supplier_name')->get();
        
        return view('admin.products.create', compact('categories', 'units', 'suppliers'));
    }

    /**
     * Simpan Produk Baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'product_code' => 'required|string|max:50|unique:products,product_code',
            'category_id'  => 'nullable|exists:categories,category_id',
            'product_name' => 'required|string|max:200',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'unit_id' => 'required|exists:units,unit_id',
            'description' => 'nullable|string',
            // Validasi Gambar: Max 10MB
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:10240', 
            'is_active' => 'nullable', 
        ]);

        // Fix: Checkbox Status (Jika tidak dicentang, request tidak mengirim key ini)
        $validated['is_active'] = $request->has('is_active');

        // Logic Upload & Crop Image (1:1 Ratio)
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'products/' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Baca gambar -> Crop Square (1:1) -> Resize 800x800 (Optimization)
            $img = Image::read($image);
            $img->cover(800, 800); 
            
            // Simpan ke Storage Public
            Storage::disk('public')->put($filename, (string) $img->encode());
            $validated['image_path'] = $filename;
        }

        // Set HPP Awal sama dengan Harga Beli
        $validated['average_cost'] = $validated['purchase_price'] ?? 0;
        
        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Produk baru berhasil ditambahkan!');
    }

    /**
     * Detail Produk & Statistik.
     */
    public function show(Product $product): View
    {
        $product->load([
            'unit', 
            'supplier', 
            'stockOpnameItems.opname.user',
            'invoiceItems'
        ]);

        // Hitung Statistik
        $totalSold = $product->invoiceItems->sum('quantity');
        $totalRevenue = $product->invoiceItems->sum('subtotal');
        
        // Hitung Margin Profit
        $marginPerUnit = $product->selling_price - $product->average_cost;
        $marginPercentage = $product->selling_price > 0 ? ($marginPerUnit / $product->selling_price) * 100 : 0;
        $markupPercentage = $product->average_cost > 0 ? ($marginPerUnit / $product->average_cost) * 100 : 100;
        
        // Potensi Profit dari stok yang ada
        $potentialProfit = $product->stock_quantity * $marginPerUnit;

        return view('admin.products.show', compact(
            'product', 
            'totalSold', 
            'totalRevenue', 
            'marginPerUnit', 
            'marginPercentage', 
            'markupPercentage',
            'potentialProfit'
        ));
    }

    /**
     * Form Edit Produk.
     */
    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $units = Unit::all();
        $suppliers = Supplier::all();
        
        return view('admin.products.edit', compact('product', 'categories', 'units', 'suppliers'));
    }

    /**
     * Update Produk.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($product->product_id, 'product_id')],
            'product_name' => 'required|string|max:200',
            'category_id'  => 'nullable|exists:categories,category_id',
            'supplier_id'  => 'required|exists:suppliers,supplier_id',
            'unit_id'      => 'required|exists:units,unit_id', // Unit bebas diedit
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:10240', // Max 10MB
            'is_active' => 'nullable',
        ]);

        // Fix: Status Aktif/Non-Aktif
        $validated['is_active'] = $request->has('is_active');

        // Logic Update Gambar & Crop
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $image = $request->file('image');
            $filename = 'products/' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Proses Crop 1:1
            $img = Image::read($image);
            $img->cover(800, 800); 
            
            Storage::disk('public')->put($filename, (string) $img->encode());
            $validated['image_path'] = $filename;
        }

        $product->update($validated);
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Soft Delete (Pindah ke Sampah).
     */
    public function destroy(Product $product): RedirectResponse
    {
        // Validasi: Stok harus 0
        if ($product->stock_quantity > 0) {
            return back()->with('error', 'Gagal: Produk masih memiliki stok fisik.');
        }
        
        // Validasi: Tidak sedang digunakan di transaksi Pending
        $hasPendingInvoice = \App\Models\InvoiceItem::where('product_id', $product->product_id)
            ->whereHas('salesInvoice', function($q) {
                $q->whereIn('status', ['draft', 'unpaid', 'partially_paid']);
            })->exists();
        
        $hasPendingPO = \App\Models\PurchaseOrderItem::where('product_id', $product->product_id)
            ->whereHas('purchaseOrder', function($q) {
                $q->whereIn('status', ['draft', 'ordered']);
            })->exists();

        if ($hasPendingInvoice || $hasPendingPO) {
            return back()->with('error', 'Gagal: Produk sedang digunakan dalam transaksi aktif (Invoice/PO) yang belum selesai.');
        }

        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dipindahkan ke sampah.');
    }

    /**
     * Restore (Pulihkan dari Sampah).
     */
    public function restore($id): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();
        return redirect()->route('admin.products.index', ['status' => 'trash'])
            ->with('success', "Produk '{$product->product_name}' berhasil dipulihkan.");
    }

    /**
     * Force Delete (Hapus Permanen).
     */
    public function forceDelete($id): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        
        // Cek Integritas Data (Riwayat Transaksi)
        $hasHistory = $product->invoiceItems()->exists() || 
                      $product->stockOpnameItems()->exists() ||
                      \Illuminate\Support\Facades\DB::table('purchase_order_items')->where('product_id', $id)->exists();

        if ($hasHistory) {
            return back()->with('error', 'Gagal: Produk ini memiliki riwayat transaksi (Penjualan/Pembelian/Opname). Tidak bisa dihapus permanen demi integritas laporan. Biarkan di sampah (Arsip).');
        }

        // Hapus Gambar Fisik
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->forceDelete();
        return redirect()->route('admin.products.index', ['status' => 'trash'])
            ->with('success', 'Produk berhasil dihapus permanen.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        // Toggle nilai is_active (true jadi false, false jadi true)
        $product->update(['is_active' => !$product->is_active]);
    
        $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Produk '{$product->product_name}' berhasil {$status}.");
    }
}