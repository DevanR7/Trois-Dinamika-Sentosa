<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-products')->only(['index']);
        $this->middleware('can:edit-products')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('can:delete-products')->only(['destroy', 'restore', 'forceDelete']);
    }

    public function index(Request $request): View
    {
        $query = Category::query()->withCount('products'); 

        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->latest()->paginate(10)->appends($request->query());
        
        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'image' => 'nullable|image|max:2048', 
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only(['name', 'description']);
        $data['slug'] = Str::slug($request->name);
        $data['is_active'] = $request->has('is_active'); 

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->category_id . ',category_id',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only(['name', 'description']);
        $data['slug'] = Str::slug($request->name);
        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    // Soft Delete (Pindah ke Sampah)
    public function destroy(Category $category): RedirectResponse
    {
        // REVISI: Cek integritas data (termasuk produk yang sudah di-soft delete)
        // Agar tidak ada data yatim piatu di masa depan jika produk di-restore
        if ($category->products()->withTrashed()->count() > 0) {
             return back()->with('error', 'Gagal: Kategori ini digunakan oleh Produk (Aktif/Arsip). Silakan ganti kategori produk terkait terlebih dahulu.');
        }

        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Kategori dipindahkan ke sampah.');
    }

    // Restore (Pulihkan)
    public function restore($id): RedirectResponse
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        return redirect()->route('admin.categories.index', ['status' => 'trash'])
            ->with('success', 'Kategori berhasil dipulihkan.');
    }

    // Force Delete (Hapus Permanen)
    public function forceDelete($id): RedirectResponse
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        
        // Double check sebelum hapus permanen
        if ($category->products()->withTrashed()->count() > 0) {
             return back()->with('error', 'Gagal: Masih ada produk yang terhubung dengan kategori ini.');
        }

        // Hapus Gambar Fisik jika ada
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->forceDelete();

        return redirect()->route('admin.categories.index', ['status' => 'trash'])
            ->with('success', 'Kategori dihapus permanen beserta gambarnya.');
    }
}