<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{   
    public function __construct()
    {
        // Menggunakan permission manage-settings (Admin/Manager)
        $this->middleware('can:manage-settings');
    }

    public function index(Request $request): View
    {
        $query = AuditLog::with('user'); 

        // 1. Filter User
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // 2. Filter Action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        
        // 3. Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // 4. Filter Subject Type
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        $logs = $query->latest()->paginate(20)->appends($request->query());

        // Data untuk Dropdown User
        $users = User::orderBy('full_name')->get();
        
        // --- DEFINISI VARIABEL $subjects (Solusi Error Anda) ---
        // Ini memetakan Nama Model (Database) ke Nama yang mudah dibaca (View)
        $subjects = [
            'App\Models\SalesInvoice' => 'Sales Invoice',
            'App\Models\PurchaseOrder' => 'Purchase Order',
            'App\Models\Product' => 'Produk',
            'App\Models\Client' => 'Klien',
            'App\Models\Supplier' => 'Supplier',
            'App\Models\Payment' => 'Pembayaran (Sales)',
            'App\Models\PurchaseOrderPayment' => 'Pembayaran (Purchase)',
            'App\Models\Expense' => 'Pengeluaran',
            'App\Models\User' => 'User System',
            'App\Models\StockOpname' => 'Stock Opname',
            'App\Models\Announcement' => 'Pengumuman',
            'App\Models\Loan' => 'Pinjaman',
            'App\Models\FixedAsset' => 'Aset Tetap',
            'App\Models\EquityTransaction' => 'Transaksi Modal',
        ];

        // Pastikan 'subjects' dimasukkan ke dalam compact
        return view('admin.audit_logs.index', compact('logs', 'users', 'subjects'));
    }
}