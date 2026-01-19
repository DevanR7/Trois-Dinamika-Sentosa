<?php

use Illuminate\Support\Facades\Route;

// Import Auth Controllers
use App\Http\Controllers\Admin\GoogleAuthController;

// Import Main Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DataMigrationController;

// Import Master Data Controllers
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductHistoryController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\AnnouncementController;

// Import Sales Controllers
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\SalesReturnController;
use App\Http\Controllers\Admin\ClientOrderReviewController;
use App\Http\Controllers\Admin\OrderChangeRequestController;
use App\Http\Controllers\Admin\InvoiceAdjustmentController;

// Import Purchase Controllers
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseOrderPaymentController;
use App\Http\Controllers\Admin\PurchaseOrderAdjustmentController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\StockOpnameController;
use App\Http\Controllers\Admin\StockCardController;

// Import Finance & Payment Controllers
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\LoanPaymentController;
// [REVISI]: Menggunakan Controller Pembayaran Manual yang Baru
use App\Http\Controllers\Admin\SalesInvoicePaymentController;
use App\Http\Controllers\Admin\PaymentClearanceController;
use App\Http\Controllers\Admin\BulkSalesPaymentController;
use App\Http\Controllers\Admin\BulkPurchasePaymentController;
use App\Http\Controllers\Admin\CompanyBankAccountController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\TaxController;
// MidtransController tidak di-import di sini karena khusus Callback (di api.php)

// Import Accounting Controllers
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\ManualJournalController;
use App\Http\Controllers\Admin\GeneralLedgerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\FixedAssetController;
use App\Http\Controllers\Admin\EquityTransactionController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\ClosingBookController;

/*
|--------------------------------------------------------------------------
| Admin Routes
| Prefix: /admin | Name: admin.
|--------------------------------------------------------------------------
*/

// =============================================================================
// 1. AUTHENTICATION & GUEST ROUTES
// =============================================================================
require __DIR__.'/admin-auth.php';

// Google Auth Redirects
Route::middleware('guest')->group(function() {
    Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Root Redirect
Route::get('/', function () {
    return auth()->check() ? redirect()->route('admin.dashboard') : redirect()->route('admin.login');
});

// =============================================================================
// 2. PROTECTED ROUTES (Require Login & Verified Email)
// =============================================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // Global Search
    Route::get('/global-search', [\App\Http\Controllers\Admin\GlobalSearchController::class, 'search'])->name('global-search');

    // --- A. DASHBOARD & PROFILE ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::put('/profile/password', 'updatePassword')->name('profile.password.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    // --- B. MASTER DATA ---
    
    // 1. Products & Inventory
    Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
        Route::patch('/{id}/restore', 'restore')->name('restore')->withTrashed();
        Route::delete('/{id}/force-delete', 'forceDelete')->name('forceDelete')->withTrashed();
        Route::patch('/{product}/toggle-status', 'toggleStatus')->name('toggle-status');
    });
    Route::resource('products', ProductController::class);
    
    Route::get('/reports/product-history', [ProductHistoryController::class, 'index'])->name('reports.product-history');
    Route::get('/reports/stock-card', [StockCardController::class, 'index'])->name('reports.stock-card');
    
    // Categories
    Route::prefix('categories')->name('categories.')->controller(CategoryController::class)->group(function () {
        Route::patch('/{category}/restore', 'restore')->name('restore')->withTrashed();
        Route::delete('/{category}/force-delete', 'forceDelete')->name('forceDelete')->withTrashed();
    });
    Route::resource('categories', CategoryController::class);
    
    // Units
    Route::prefix('units')->name('units.')->controller(UnitController::class)->group(function () {
        Route::patch('/{unit}/restore', 'restore')->name('restore')->withTrashed();
        Route::delete('/{unit}/force-delete', 'forceDelete')->name('forceDelete')->withTrashed();
    });
    Route::resource('units', UnitController::class)->except(['show']);

    // Stock Opname
    Route::prefix('stock-opnames')->name('stock-opnames.')->controller(StockOpnameController::class)->group(function() {
        Route::get('/worksheet', 'downloadWorksheet')->name('worksheet');
    });
    Route::resource('stock-opnames', StockOpnameController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // 2. Clients
    Route::prefix('clients')->name('clients.')->controller(ClientController::class)->group(function () {
        Route::patch('/{client}/approve', 'approve')->name('approve');
        Route::patch('/{client}/lock', 'lock')->name('lock');
        Route::patch('/{client}/unlock', 'unlock')->name('unlock');
        Route::patch('/{client}/restore', 'restore')->name('restore')->withTrashed();
        Route::get('/{client}/tab-content', 'getTabContent')->name('tab-content');
    });
    Route::resource('clients', ClientController::class);

    // 3. Suppliers
    Route::patch('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore')->withTrashed();
    Route::resource('suppliers', SupplierController::class);

    // 4. Announcements
    Route::prefix('announcements')->name('announcements.')->controller(AnnouncementController::class)->group(function(){
        Route::patch('/{announcement}/restore', 'restore')->name('restore')->withTrashed();
        Route::delete('/{announcement}/force-delete', 'forceDelete')->name('forceDelete')->withTrashed();
    });
    Route::resource('announcements', AnnouncementController::class);


    // --- C. SALES MODULE (PENJUALAN) ---

    // 1. Sales Orders (Input by Sales/Admin)
    Route::prefix('sales-orders')->name('sales-orders.')->controller(SalesOrderController::class)->group(function () {
        Route::get('/trash', 'trash')->name('trash');
        Route::patch('/{order}/restore', 'restore')->name('restore')->withTrashed();
        Route::delete('/{order}/force-delete', 'forceDelete')->name('forceDelete')->withTrashed();
        Route::post('/{order}/cancel', 'cancel')->name('cancel');
    });
    Route::resource('sales-orders', SalesOrderController::class)->parameters(['sales-orders' => 'order']);

    // 2. Client Order Reviews (Orders from Client Portal)
    Route::prefix('client-order-reviews')->name('client-order-reviews.')->controller(ClientOrderReviewController::class)->group(function() {
        Route::get('/', 'index')->name('index'); 
        Route::get('/{order}', 'show')->name('show'); 
        Route::post('/{order}/approve', 'approve')->name('approve'); 
        Route::post('/{order}/reject', 'reject')->name('reject'); 
    });

    // 3. Order Change Requests (Ajuan Perubahan oleh Client)
    Route::prefix('order-change-requests')->name('order-change-requests.')->controller(OrderChangeRequestController::class)->group(function() {
        Route::get('/', 'index')->name('index');
        Route::get('/{changeRequest}', 'show')->name('show');
        Route::post('/{changeRequest}/process', 'process')->name('process');
    });

    // 4. Invoices
    Route::prefix('invoices')->name('invoices.')->controller(SalesInvoiceController::class)->group(function(){
        Route::get('/create/from-order/{order}', 'createFromOrder')->name('createFromOrder');
        Route::post('/{invoice}/cancel', 'cancel')->name('cancel');
        Route::post('/{invoice}/confirm', 'confirm')->name('confirm');
        Route::get('/{invoice}/download', 'downloadPDF')->name('pdf');
        Route::post('/{invoice}/refund', 'processRefund')->name('refund');
        Route::post('/{invoice}/get-snap-token', [SalesInvoiceController::class, 'getSnapToken'])->name('get-snap-token');
    });
    Route::resource('invoices', SalesInvoiceController::class);
    
    // 5. Invoice Adjustments
    Route::prefix('invoice-adjustments')->name('invoice-adjustments.')->controller(InvoiceAdjustmentController::class)->group(function () {
        Route::get('/create', 'create')->name('create');
        Route::get('/create-manual/{invoice}', 'createManual')->name('create.manual');
        Route::post('/store-manual', 'storeManual')->name('store.manual');
        Route::get('/create-auto/{invoice}', 'createAuto')->name('create.auto');
        Route::post('/store-auto/{invoice}', 'storeAuto')->name('store.auto');
        Route::delete('/{invoiceAdjustment}', 'destroy')->name('destroy');
    });

    // 6. Sales Returns
    Route::resource('sales-returns', SalesReturnController::class);


    // --- D. PURCHASING MODULE (PEMBELIAN) ---

    // 1. Purchase Orders
    Route::prefix('purchase-orders')->name('purchase-orders.')->controller(PurchaseOrderController::class)->group(function(){
        Route::post('/{purchaseOrder}/cancel', 'cancel')->name('cancel');
        Route::post('/{purchaseOrder}/receive', 'receive')->name('receive');
        Route::patch('/{purchaseOrder}/mark-ordered', 'markAsOrdered')->name('mark-ordered');
        Route::post('/{purchaseOrder}/mark-as-paid', 'markAsPaid')->name('markAsPaid');
        Route::post('/{purchaseOrder}/add-supplier-invoice', 'addSupplierInvoice')->name('addSupplierInvoice');
        Route::get('/{purchaseOrder}/download-pdf', 'downloadPDF')->name('pdf');
    });
    Route::resource('purchase-orders', PurchaseOrderController::class);
    
    // 2. Purchase Order Payment (Internal Outgoing)
    Route::post('/purchase-orders/{purchaseOrder}/payments', [PurchaseOrderPaymentController::class, 'store'])->name('purchase-orders.payments.store');
    Route::delete('/purchase-order-payments/{payment}', [PurchaseOrderPaymentController::class, 'destroy'])->name('purchase-orders.payments.destroy');

    // 3. Purchase Adjustments
    Route::prefix('purchase-order-adjustments')->name('purchase-order-adjustments.')->controller(PurchaseOrderAdjustmentController::class)->group(function () {
        Route::get('/create', 'create')->name('create');
        Route::get('/create-manual/{purchaseOrder}', 'createManual')->name('create.manual');
        Route::post('/store-manual', 'storeManual')->name('store.manual');
        Route::get('/create-auto/{purchaseOrder}', 'createAuto')->name('create.auto');
        Route::post('/store-auto/{purchaseOrder}', 'storeAuto')->name('store.auto');
        Route::delete('/{purchaseOrderAdjustment}', 'destroy')->name('destroy');
    });

    // 4. Purchase Returns
    Route::resource('purchase-returns', PurchaseReturnController::class);


    // --- E. FINANCE MODULE (KEUANGAN) ---

    // 1. Payment Clearance (Persetujuan Pembayaran Pending)
    Route::prefix('payment-clearance')->name('payment-clearance.')->controller(PaymentClearanceController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/sales/{payment}/approve', 'approveSalesPayment')->name('sales.approve');
        Route::post('/sales/{payment}/reject', 'rejectSalesPayment')->name('sales.reject');
        Route::post('/purchase/{purchaseOrderPayment}/approve', 'approvePurchasePayment')->name('purchase.approve');
        Route::post('/purchase/{purchaseOrderPayment}/reject', 'rejectPurchasePayment')->name('purchase.reject');
    });

    // 2. Bulk Sales Payment (Penerimaan Massal)
    Route::prefix('bulk-sales-payments')->name('bulk-sales-payments.')->controller(BulkSalesPaymentController::class)->group(function () {
        Route::get('/', 'index')->name('index'); 
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/pending', 'pending')->name('pending'); 
        Route::get('/pending/{bulkSalesPayment}', 'showPending')->name('showPending');
        Route::get('/{bulkSalesPayment}', 'show')->name('show');
        Route::post('/{bulkSalesPayment}/approve', 'approve')->name('approve');
        Route::post('/{bulkSalesPayment}/reject', 'reject')->name('reject');
        Route::get('/get-unpaid-invoices/{client}', 'getUnpaidInvoicesApi')->name('get-unpaid-invoices'); 
        Route::delete('/{bulkSalesPayment}', 'destroy')->name('destroy');
    });

    // 3. Bulk Purchase Payment (Pembayaran Massal ke Supplier)
    Route::prefix('bulk-purchase-payments')->name('bulk-purchase-payments.')->controller(BulkPurchasePaymentController::class)->group(function () {
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/get-unpaid-pos/{supplier}', 'getUnpaidPurchaseOrdersApi')->name('get-unpaid-pos');
    });

    // 4. Single Payments (Manual Input by Admin/Sales) - REVISI
    // Menggunakan SalesInvoicePaymentController untuk pencatatan manual Cash/Transfer
    Route::prefix('payments')->name('payments.')->controller(SalesInvoicePaymentController::class)->group(function() {
        Route::delete('/{payment}', 'destroy')->name('destroy');
        Route::post('/{payment}/approve', 'approve')->name('approve'); 
        Route::post('/{payment}/reject', 'reject')->name('reject');
    });
    Route::post('/invoices/{invoice}/payments', [SalesInvoicePaymentController::class, 'store'])->name('payments.store');

    // 5. Expenses (Pengeluaran Operasional)
    Route::resource('expenses', ExpenseController::class);

    // 6. Loans (Pinjaman)
    Route::resource('loans', LoanController::class);
    Route::prefix('loans/{loan}/payments')->name('loan-payments.')->controller(LoanPaymentController::class)->group(function () {
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::delete('{payment}', 'destroy')->name('destroy');
    });


    // --- F. ACCOUNTING MODULE (AKUNTANSI) ---

    // 1. Chart of Accounts
    Route::resource('chart-of-accounts', ChartOfAccountController::class)->except(['show']);

    // 2. Manual Journals
    Route::resource('manual-journals', ManualJournalController::class)->except(['show']);
    Route::get('manual-journals/{manualJournal}', [ManualJournalController::class, 'show'])->name('manual-journals.show');

    // 3. Fixed Assets
    Route::resource('fixed-assets', FixedAssetController::class);

    // 4. Equity Transactions
    Route::resource('equity-transactions', EquityTransactionController::class);

    // 5. Bank Reconciliation
    Route::resource('bank-reconciliations', BankReconciliationController::class)->except(['edit']);

    // 6. Closing Book
    Route::get('/closing-book', [ClosingBookController::class, 'index'])->name('closing-book.index');
    Route::post('/closing-book', [ClosingBookController::class, 'store'])->name('closing-book.store');

    // 7. Financial Reports
    Route::prefix('reports')->name('reports.')->group(function() {
        Route::get('/', [ReportController::class, 'index'])->name('index'); 
        Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])->name('general-ledger');
        Route::get('/aging-schedule', [ReportController::class, 'agingSchedule'])->name('aging-schedule');
        Route::get('/print-pdf', [ReportController::class, 'printPDF'])->name('pdf');
    });


    // --- G. SETTINGS & SYSTEM ---

    // 1. General Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // 2. Users & Roles
    Route::prefix('users')->name('users.')->controller(UserController::class)->group(function() {
        Route::patch('/{user}/approve', 'approve')->name('approve');
        Route::patch('/{user}/restore', 'restore')->name('restore')->withTrashed();
    });
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class)->except(['show']);

    // 3. Finance Master Data
    Route::prefix('company-bank-accounts')->name('company-bank-accounts.')->controller(CompanyBankAccountController::class)->group(function () {
        Route::patch('/{id}/restore', 'restore')->name('restore')->withTrashed();
        Route::delete('/{id}/force-delete', 'forceDelete')->name('forceDelete')->withTrashed();
    });
    Route::resource('company-bank-accounts', CompanyBankAccountController::class)->except(['show']);

    // Taxes
    Route::prefix('taxes')->name('taxes.')->controller(TaxController::class)->group(function () {
        Route::patch('/{tax}/restore', 'restore')->name('restore')->withTrashed();
        Route::delete('/{tax}/force-delete', 'forceDelete')->name('forceDelete')->withTrashed();
    });
    Route::resource('taxes', TaxController::class)->except(['show']);
    
    // Payment Methods
    Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
        Route::get('archived', [PaymentMethodController::class, 'archivedIndex'])->name('archived.index');
        Route::patch('{id}/restore', [PaymentMethodController::class, 'restore'])->name('restore');
        Route::delete('{id}/force-delete', [PaymentMethodController::class, 'forceDelete'])->name('force-delete');
    });
    Route::resource('payment-methods', PaymentMethodController::class);

    // 4. Data Migration
    Route::prefix('migration')->name('migration.')->controller(DataMigrationController::class)->group(function() {
        Route::get('/', 'index')->name('index');
        Route::post('/products', 'importProducts')->name('import-products');
        Route::post('/clients', 'importClients')->name('import-clients');
        Route::get('/template/{type}', 'downloadTemplate')->name('template');
    });
    
    // 5. Audit Logs
    Route::get('/system/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    
    // --- H. INTERNAL API HELPERS (AJAX) ---
    
    // Client Details (Helper untuk form input manual)
    Route::get('/api/clients/{client}/details', function (\App\Models\Client $client) {
        return response()->json([
            'client_id' => $client->client_id,
            'balance' => $client->balance,
        ]);
    })->name('api.clients.details');

    // Invoice Items
    Route::get('/api/invoices/{invoice}/items', function (Illuminate\Http\Request $request, \App\Models\SalesInvoice $invoice) {
        if ($request->user()->cannot('view', $invoice)) { return response()->json(['message' => 'Unauthorized'], 403); }
        $invoice->load('items.product');
        return response()->json(['invoice' => $invoice, 'items' => $invoice->items]);
    });

    // Supplier Details
    Route::get('/api/suppliers/{supplier}/details', function (\App\Models\Supplier $supplier) {
        return response()->json([
            'supplier_id' => $supplier->supplier_id,
            'balance' => $supplier->balance,
        ]);
    })->name('api.suppliers.details');

    // PO Items
    Route::get('/api/purchase-orders/{purchaseOrder}/items', function (Illuminate\Http\Request $request, \App\Models\PurchaseOrder $purchaseOrder) {
        if ($request->user()->cannot('view', $purchaseOrder)) { return response()->json(['message' => 'Unauthorized'], 403); }
        
        $purchaseOrder->load(['items.product.unit', 'items.discounts', 'tax']);
        return response()->json([
            'items' => $purchaseOrder->items,
            'po' => [
                'tax_rate' => $purchaseOrder->tax->rate ?? 0,
                'disc_fee_percent' => $purchaseOrder->disc_fee_percent ?? 0,
                'use_custom_dpp' => $purchaseOrder->use_custom_dpp_factor,
                'custom_dpp_factor' => $purchaseOrder->custom_dpp_factor ?? 1,
            ]
        ]);
    });

});

// Fallback
Route::fallback(function () {
    return auth()->check() ? redirect()->route('admin.dashboard') : redirect()->route('admin.login');
});