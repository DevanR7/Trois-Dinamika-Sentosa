<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class SalesOrderController extends Controller
{    
    public function __construct()
    {
        $this->middleware('can:view-sales-orders')->only(['index', 'show', 'trash']);
        $this->middleware('can:create-sales-orders')->only(['create', 'store']);
        $this->middleware('can:edit-sales-orders')->only(['edit', 'update', 'cancel', 'restore']);
        $this->middleware('can:delete-sales-orders')->only(['destroy', 'forceDelete']);
    }
    
    public function index(Request $request): View
    {
        // 1. Authorize
        $this->authorize('viewAny', Order::class);
        $user = Auth::user();

        // 2. Base Query
        $query = Order::with(['client', 'sales'])
            ->where('order_source', 'sales');

        // Filter: Jika user adalah sales, hanya tampilkan datanya sendiri
        if ($user->hasRole('sales')) {
            $query->where('user_id_sales', $user->user_id);
        }

        // 3. [FIX UTAMA] Generate Unique Dates untuk Dropdown Filter
        // Mengambil daftar bulan unik dari database untuk filter
        $uniqueDatesQuery = Order::where('order_source', 'sales');
        
        if ($user->hasRole('sales')) {
            $uniqueDatesQuery->where('user_id_sales', $user->user_id);
        }

        $uniqueDates = $uniqueDatesQuery
            ->select(DB::raw("DATE_FORMAT(order_date, '%Y-%m') as ym"))
            ->distinct()
            ->orderBy('ym', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')
                ];
            });

        // 4. Filter Search (No Order / Nama Klien)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($q) use ($search) {
                      $q->where('client_name', 'like', "%{$search}%");
                  });
            });
        }

        // 5. Filter Date (Bulan)
        if ($request->filled('date')) {
            try {
                $date = Carbon::createFromFormat('Y-m', $request->date);
                $query->whereYear('order_date', $date->year)
                      ->whereMonth('order_date', $date->month);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        // 6. Filter Status
        if ($request->filled('status_filter')) {
             $query->where('status', $request->status_filter);
        }

        // 7. Sorting
        $sort = $request->get('sort', 'terbaru');
        switch ($sort) {
            case 'terlama':
                $query->orderBy('order_date', 'asc')->orderBy('order_id', 'asc');
                break;
            case 'klien_az':
                $query->join('clients', 'orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'asc')
                      ->select('orders.*'); // Hindari ambiguitas kolom ID
                break;
            case 'klien_za':
                $query->join('clients', 'orders.client_id', '=', 'clients.client_id')
                      ->orderBy('clients.client_name', 'desc')
                      ->select('orders.*');
                break;
            default:
                $query->orderBy('order_date', 'desc')->orderBy('order_id', 'desc');
        }

        $orders = $query->paginate(15)->appends($request->query());

        // Kirim $uniqueDates ke view agar tidak error "Undefined variable"
        return view('admin.sales_orders.index', compact('orders', 'uniqueDates'));
    }

    public function create(): View
    {
        $this->authorize('create', Order::class);
        
        $clients = Client::orderBy('client_name')->get();
        // Hanya tampilkan produk aktif dan ada stok (Opsional, tergantung kebijakan)
        $products = Product::where('is_active', true)->orderBy('product_name')->get(); 
        $salesUsers = User::role('sales')->get(); 

        return view('admin.sales_orders.create', compact('clients', 'products', 'salesUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'sales_id' => 'nullable|exists:users,user_id',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1', 
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01', 
        ]);

        try {
            DB::beginTransaction();

            // 1. Buat Header Order
            $order = new Order();
            $order->client_id = $validated['client_id'];
            $order->order_date = $validated['order_date'];
            $order->notes = $validated['notes'] ?? null;
            
            // Assign Salesman
            if ($request->filled('sales_id')) {
                 $order->user_id_sales = $validated['sales_id'];
            } else {
                 $order->user_id_sales = Auth::id();
            }
            
            $order->order_number = Order::generateOrderNumber(Auth::id());
            $order->status = 'pending';
            $order->order_source = 'sales';
            $order->total_amount = 0; // Hitung nanti
            $order->save();

            $totalAmount = 0;

            // 2. Simpan Item
            foreach ($validated['products'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                
                // Gunakan harga jual saat ini
                $price = $product->selling_price; 
                $subtotal = $itemData['quantity'] * $price;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $price,
                    'subtotal' => $subtotal,
                ]);

                // NOTE: Stok TIDAK dikurangi di sini (Sales Order hanya pencatatan/booking awal).
                // Stok akan dikurangi saat Admin memproses order ini menjadi Invoice (di SalesInvoiceController).
            }

            // 3. Update Total
            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            return redirect()
                ->route('admin.sales-orders.show', $order->order_id)
                ->with('success', 'Pesanan Penjualan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal membuat pesanan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);
        
        $order->load(['client', 'sales', 'items.product' => function ($query) {
            $query->withTrashed(); // Tampilkan produk meski sudah dihapus master-nya
        }]);

        return view('admin.sales_orders.show', compact('order'));
    }

    public function edit(Order $order): View
    {
        $this->authorize('update', $order);
        
        if ($order->status !== 'pending') {
            abort(403, 'Pesanan yang sudah diproses tidak dapat diedit.');
        }
        
        $order->load(['items.product' => function ($query) {
            $query->withTrashed();
        }]);
        
        $clients = Client::all();
        $products = Product::where('is_active', true)->get();
        $salesUsers = User::role('sales')->get();

        return view('admin.sales_orders.edit', compact('order', 'clients', 'products', 'salesUsers'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        
        if ($order->status !== 'pending') {
            return back()->with('error', 'Pesanan yang sudah diproses tidak dapat diedit.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'order_date' => 'required|date',
            'sales_id' => 'nullable|exists:users,user_id',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1', 
            'products.*.product_id' => 'required|exists:products,product_id',
            'products.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();
            
            $updateData = [
                'client_id' => $validated['client_id'],
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'] ?? null,
            ];
            
            if ($request->filled('sales_id')) {
                $updateData['user_id_sales'] = $validated['sales_id'];
            }

            $order->update($updateData);

            // Reset Item (Hapus lama, buat baru - cara paling aman untuk update order)
            $order->items()->delete();
            
            $totalAmount = 0;
            
            foreach ($validated['products'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                $price = $product->selling_price; 
                $subtotal = $itemData['quantity'] * $price;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);
            
            DB::commit();

            return redirect()
                ->route('admin.sales-orders.index')
                ->with('success', 'Pesanan Penjualan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal mengupdate pesanan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);
        
        if ($order->status !== 'pending') {
             return back()->with('error', 'Hanya pesanan berstatus pending yang dapat dihapus.');
        }
        
        $order->delete(); // Soft Delete
        
        return redirect()
            ->route('admin.sales-orders.index')
            ->with('success', 'Pesanan Penjualan berhasil dihapus.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        
        if ($order->status !== 'pending') {
            return back()->with('error', 'Hanya pesanan dengan status Pending yang dapat dibatalkan.');
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'rejected',
                'notes'  => $order->notes . "\n[Dibatalkan Manual oleh Admin pada " . now()->format('d-m-Y H:i') . "]"
            ]);
        
            // NOTE: Tidak perlu mengembalikan stok ($product->increment)
            // Karena pada saat store(), kita TIDAK mengurangi stok.
            // Stok baru berkurang saat Invoice Dikonfirmasi.
            // Jadi Cancel Sales Order cukup ubah status saja.

            DB::commit();
            return redirect()->route('admin.sales-orders.show', $order->order_id)
                ->with('success', 'Pesanan berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan pesanan: ' . $e->getMessage());
        }
    }

    public function trash(Request $request): View
    {
        $this->authorize('viewAny', Order::class);
        
        $query = Order::onlyTrashed()
            ->with(['client', 'sales'])
            ->where('order_source', 'sales');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($q) use ($search) {
                      $q->where('client_name', 'like', "%{$search}%");
                  });
            });
        }
        $orders = $query->orderBy('deleted_at', 'desc')->paginate(15);
        
        return view('admin.sales_orders.trash', compact('orders'));
    }

    public function restore($id): RedirectResponse
    {
        $order = Order::onlyTrashed()->findOrFail($id);
        $this->authorize('delete', $order); 
        
        $order->restore();
        
        return redirect()->route('admin.sales-orders.trash')
            ->with('success', "Pesanan #{$order->order_number} berhasil dipulihkan ke daftar aktif.");
    }

    public function forceDelete($id): RedirectResponse
    {
        $order = Order::onlyTrashed()->findOrFail($id);
        $this->authorize('delete', $order);
        
        // Hapus item pesanan terkait secara permanen
        $order->items()->delete(); 
        $order->forceDelete();
        
        return redirect()->route('admin.sales-orders.trash')
            ->with('success', "Pesanan #{$order->order_number} telah dihapus permanen.");
    }
}