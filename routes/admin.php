<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\GoogleAuthController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StockOpnameController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\ClientOrderReviewController;
use App\Http\Controllers\Admin\OrderChangeRequestController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\SalesReturnController;
use App\Http\Controllers\Admin\InvoiceAdjustmentController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseOrderPaymentController; 
use App\Http\Controllers\Admin\PurchaseOrderAdjustmentController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\BulkSalesPaymentController;
use App\Http\Controllers\Admin\BulkPurchasePaymentController;
use App\Http\Controllers\Admin\PaymentClearanceController;
use App\Http\Controllers\Admin\PaymentController; 
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\LoanPaymentController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\ManualJournalController;
use App\Http\Controllers\Admin\FixedAssetController;
use App\Http\Controllers\Admin\EquityTransactionController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\ClosingBookController;
use App\Http\Controllers\Admin\GeneralLedgerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\CompanyBankAccountController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\DataMigrationController;
use App\Http\Controllers\Admin\MidtransController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| Prefix URL  : /admin
| Prefix Name : admin.
| Middleware  : web, auth, verified (applied in groups below)
*/

// --- 1. AUTHENTICATION (Guest) ---
require __DIR__.'/admin-auth.php';

Route::get('/cek-php', function () {
    phpinfo();
});

// --- 2. GOOGLE AUTH ---
Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/', function () {
    return redirect()->back(302, [], route('admin.dashboard'));
})->middleware('auth');

// --- 3. PROTECTED ROUTES ---
Route::middleware(['auth', 'verified'])->group(function () {

    // ========================================================================
    // A. DASHBOARD & PROFILE (Menu Utama)
    // ========================================================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::put('/profile/password', 'updatePassword')->name('profile.password.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });


    // ========================================================================
    // B. INVENTORY SECTION (Produk & Stok)
    // ========================================================================
    Route::resource('products', ProductController::class);
    
    // Stock Opname
    Route::get('/stock-opnames/worksheet', [StockOpnameController::class, 'downloadWorksheet'])->name('stock-opnames.worksheet');
    Route::resource('stock-opnames', StockOpnameController::class)->only(['index', 'create', 'store', 'show', 'destroy']);


    // ========================================================================
    // C. SALES SECTION (Penjualan)
    // ========================================================================
    
    // 1. Clients
    Route::resource('clients', ClientController::class)->except(['show']);
    Route::controller(ClientController::class)->prefix('clients')->name('clients.')->group(function () {
        Route::patch('/{client}/approve', 'approve')->name('approve');
        Route::patch('/{client}/lock', 'lock')->name('lock');
        Route::patch('/{client}/unlock', 'unlock')->name('unlock');
        Route::patch('/{client}/restore', 'restore')->name('restore')->withTrashed();
        Route::get('/{client}', 'show')->name('show')->withTrashed();
    });

    // 2. Sales Orders (SO)
    Route::resource('sales-orders', SalesOrderController::class)->parameters(['sales-orders' => 'order']);

    // 3. Reviews & Change Requests
    Route::prefix('client-order-reviews')->name('client-order-reviews.')->group(function() {
        Route::get('/', [ClientOrderReviewController::class, 'index'])->name('index'); 
        Route::get('/{order}', [ClientOrderReviewController::class, 'show'])->name('show'); 
        Route::post('/{order}/approve', [ClientOrderReviewController::class, 'approve'])->name('approve'); 
        Route::post('/{order}/reject', [ClientOrderReviewController::class, 'reject'])->name('reject'); 
    });
    
    Route::prefix('order-change-requests')->name('order-change-requests.')->group(function() {
        Route::get('/', [OrderChangeRequestController::class, 'index'])->name('index');
        Route::get('/{changeRequest}', [OrderChangeRequestController::class, 'show'])->name('show');
        Route::post('/{changeRequest}/process', [OrderChangeRequestController::class, 'process'])->name('process');
    });

    // 4. Invoices (Faktur)
    Route::resource('invoices', SalesInvoiceController::class);
    Route::controller(SalesInvoiceController::class)->prefix('invoices')->name('invoices.')->group(function(){
        Route::get('/create/from-order/{order}', 'createFromOrder')->name('createFromOrder');
        Route::post('/{invoice}/cancel', 'cancel')->name('cancel');
        Route::get('/{invoice}/download', 'downloadPDF')->name('pdf');
        Route::post('/{invoice}/confirm', 'confirm')->name('confirm');
    });

    // 5. Invoice Adjustments
    Route::prefix('invoice-adjustments')->name('invoice-adjustments.')->group(function () {
        Route::get('/create', [InvoiceAdjustmentController::class, 'create'])->name('create');
        Route::get('/create-manual/{invoice}', [InvoiceAdjustmentController::class, 'createManual'])->name('create.manual');
        Route::post('/store-manual', [InvoiceAdjustmentController::class, 'storeManual'])->name('store.manual');
        Route::get('/create-auto/{invoice}', [InvoiceAdjustmentController::class, 'createAuto'])->name('create.auto');
        Route::post('/store-auto/{invoice}', [InvoiceAdjustmentController::class, 'storeAuto'])->name('store.auto');
        Route::delete('/{invoiceAdjustment}', [InvoiceAdjustmentController::class, 'destroy'])->name('destroy');
    });

    // 6. Sales Returns
    Route::resource('sales-returns', SalesReturnController::class);


    // ========================================================================
    // D. PURCHASING SECTION (Pembelian)
    // ========================================================================
    
    // 1. Suppliers
    Route::resource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore')->withTrashed();

    // 2. Purchase Orders (PO)
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::controller(PurchaseOrderController::class)->prefix('purchase-orders')->name('purchase-orders.')->group(function(){
        Route::post('/{purchaseOrder}/cancel', 'cancel')->name('cancel');
        Route::post('/{purchaseOrder}/receive', 'receive')->name('receive');
        Route::post('/{purchaseOrder}/mark-as-paid', 'markAsPaid')->name('markAsPaid');
        Route::post('/{purchaseOrder}/add-supplier-invoice', 'addSupplierInvoice')->name('addSupplierInvoice');
        Route::get('/{purchaseOrder}/download-pdf', 'downloadPDF')->name('pdf');
    });

    // 3. PO Adjustments
    Route::prefix('purchase-order-adjustments')->name('purchase-order-adjustments.')->group(function () {
        Route::get('/create', [PurchaseOrderAdjustmentController::class, 'create'])->name('create');
        Route::get('/create-manual/{purchaseOrder}', [PurchaseOrderAdjustmentController::class, 'createManual'])->name('create.manual');
        Route::post('/store-manual', [PurchaseOrderAdjustmentController::class, 'storeManual'])->name('store.manual');
        Route::get('/create-auto/{purchaseOrder}', [PurchaseOrderAdjustmentController::class, 'createAuto'])->name('create.auto');
        Route::post('/store-auto/{purchaseOrder}', [PurchaseOrderAdjustmentController::class, 'storeAuto'])->name('store.auto');
        Route::delete('/{purchaseOrderAdjustment}', [PurchaseOrderAdjustmentController::class, 'destroy'])->name('destroy');
    });

    // 4. Purchase Returns
    Route::resource('purchase-returns', PurchaseReturnController::class);


    // ========================================================================
    // E. FINANCE SECTION (Keuangan & Arus Kas)
    // ========================================================================
    
    // 1. Bulk Sales Payments (Terima Piutang)
    Route::controller(BulkSalesPaymentController::class)
        ->prefix('bulk-sales-payments')
        ->name('bulk-sales-payments.')
        ->group(function () {
            Route::get('/', 'index')->name('index'); 
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/pending', 'pending')->name('pending'); 
            Route::get('/pending/{bulkSalesPayment}', 'showPending')->name('showPending');
            Route::get('/{bulkSalesPayment}', 'show')->name('show');
            Route::post('/{bulkSalesPayment}/approve', 'approve')->name('approve');
            Route::post('/{bulkSalesPayment}/reject', 'reject')->name('reject');
        });
        
    // 2. Bulk Purchase Payments (Bayar Hutang)
    Route::prefix('bulk-purchase-payments')->name('bulk-purchase-payments.')->group(function () {
        Route::get('/create', [BulkPurchasePaymentController::class, 'create'])->name('create');
        Route::post('/', [BulkPurchasePaymentController::class, 'store'])->name('store');
    });

    // 3. Payment Clearance (Kliring)
    Route::middleware(['permission:manage-payment-clearance'])->prefix('payment-clearance')->name('payment-clearance.')->group(function () {
        Route::get('/', [PaymentClearanceController::class, 'index'])->name('index');
        Route::post('/sales/{payment}/approve', [PaymentClearanceController::class, 'approveSalesPayment'])->name('sales.approve');
        Route::post('/sales/{payment}/reject', [PaymentClearanceController::class, 'rejectSalesPayment'])->name('sales.reject');
        Route::post('/purchase/{purchaseOrderPayment}/approve', [PaymentClearanceController::class, 'approvePurchasePayment'])->name('purchase.approve');
        Route::post('/purchase/{purchaseOrderPayment}/reject', [PaymentClearanceController::class, 'rejectPurchasePayment'])->name('purchase.reject');
    });

    // 4. Expenses (Biaya Ops)
    Route::resource('expenses', ExpenseController::class);

    // 5. Loans (Pinjaman)
    Route::resource('loans', LoanController::class);

    // [FIX] Menggunakan Custom Group agar nama route sesuai dengan View (admin.loan-payments.*)
    Route::prefix('loans/{loan}/payments')->name('loan-payments.')->group(function () {
        Route::get('create', [LoanPaymentController::class, 'create'])->name('create');
        Route::post('/', [LoanPaymentController::class, 'store'])->name('store');
        Route::delete('{payment}', [LoanPaymentController::class, 'destroy'])->name('destroy');
    });

    // 6. Single Payment Helpers (Used in modals inside Invoice/PO View)
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve'); 
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

    Route::post('/purchase-orders/{purchaseOrder}/payments', [PurchaseOrderPaymentController::class, 'store'])->name('purchase-orders.payments.store');
    Route::delete('/purchase-order-payments/{payment}', [PurchaseOrderPaymentController::class, 'destroy'])->name('purchase-orders.payments.destroy');

    Route::prefix('midtrans')->name('midtrans.')->group(function () {
        // Single Payment (Show Invoice Admin)
        Route::post('/pay/{invoice}', [MidtransController::class, 'pay'])->name('pay');
        // Bulk Payment (Create Bulk Admin)
        Route::post('/pay-batch', [MidtransController::class, 'payBatch'])->name('payBatch');
    });


    // ========================================================================
    // F. ACCOUNTING SECTION (Akuntansi)
    // ========================================================================
    
    Route::resource('chart-of-accounts', ChartOfAccountController::class)->except(['show']);
    Route::resource('manual-journals', ManualJournalController::class)->except(['show']);
    Route::get('manual-journals/{manualJournal}', [ManualJournalController::class, 'show'])->name('manual-journals.show');
    
    Route::resource('fixed-assets', FixedAssetController::class);
    Route::resource('equity-transactions', EquityTransactionController::class);
    
    Route::resource('bank-reconciliations', BankReconciliationController::class)->except(['edit']);

    Route::get('/closing-book', [ClosingBookController::class, 'index'])->name('closing-book.index');
    Route::post('/closing-book', [ClosingBookController::class, 'store'])->name('closing-book.store');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/general-ledger', [GeneralLedgerController::class, 'index'])->name('reports.general-ledger');


    // ========================================================================
    // G. SYSTEM & SETTINGS SECTION (Pengaturan)
    // ========================================================================

    // 1. Settings & Profile PT
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // 2. Users & Roles
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
    Route::resource('roles', RoleController::class)->except(['show']);

    // 3. Master Data Keuangan (Bank, Pajak, Metode Bayar, Satuan)
    Route::resource('company-bank-accounts', CompanyBankAccountController::class)
        ->except(['show'])
        ->middleware('permission:manage-bank-accounts');
    Route::resource('taxes', TaxController::class)->except(['show']);
    Route::resource('units', UnitController::class)->except(['show']);

    Route::resource('payment-methods', PaymentMethodController::class)->except(['show']);
    Route::prefix('archived-payment-methods')->name('payment-methods.archived.')
        ->middleware('permission:manage-payment-methods')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'archivedIndex'])->name('index');
            Route::post('/{id}/restore', [PaymentMethodController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [PaymentMethodController::class, 'forceDelete'])->name('forceDelete');
    });

    // 4. Announcements
    Route::resource('announcements', AnnouncementController::class);
    Route::patch('announcements/{announcement}/restore', [AnnouncementController::class, 'restore'])->name('announcements.restore')->withTrashed();
    Route::delete('announcements/{announcement}/force-delete', [AnnouncementController::class, 'forceDelete'])->name('announcements.forceDelete')->withTrashed();

    // 5. Data Migration
    Route::get('/migration', [DataMigrationController::class, 'index'])->name('migration.index');
    Route::get('/migration/template/{type}', [DataMigrationController::class, 'downloadTemplate'])->name('migration.template');
    Route::post('/migration/products', [DataMigrationController::class, 'importProducts'])->name('migration.import-products');
    Route::post('/migration/clients', [DataMigrationController::class, 'importClients'])->name('migration.import-clients');


    // ========================================================================
    // H. INTERNAL API (Helpers for AJAX/Dropdowns)
    // ========================================================================
    
    // Client & Sales Helper
    Route::get('/bulk-sales-payments/get-unpaid-invoices/{client}', [BulkSalesPaymentController::class, 'getUnpaidInvoicesApi'])->name('api.clients.unpaid-invoices');
    Route::get('/api/clients/{client}/details', function (\App\Models\Client $client) {
        return response()->json([
            'client_id' => $client->client_id,
            'client_name' => $client->client_name,
            'balance' => $client->balance,
            'pending_balance' => $client->pending_balance,
        ]);
    })->name('api.clients.details');
    
    Route::get('/api/invoices/{invoice}/items', function (Illuminate\Http\Request $request, \App\Models\SalesInvoice $invoice) {
        if ($request->user()->cannot('view', $invoice)) { return response()->json(['message' => 'Unauthorized'], 403); }
        $invoice->load('items.product');
        return response()->json(['invoice' => $invoice, 'items' => $invoice->items]);
    });

    // Supplier & Purchase Helper
    Route::get('/api/suppliers/{supplier}/unpaid-purchase-orders', [BulkPurchasePaymentController::class, 'getUnpaidPurchaseOrdersApi'])->name('api.suppliers.unpaid-pos');
    Route::get('/api/suppliers/{supplier}/details', function (\App\Models\Supplier $supplier) {
        return response()->json([
            'supplier_id' => $supplier->supplier_id,
            'supplier_name' => $supplier->supplier_name,
            'balance' => $supplier->balance,
            'pending_balance' => $supplier->pending_balance,
        ]);
    })->name('api.suppliers.details');

    Route::get('/api/purchase-orders/{purchaseOrder}/items', function (Illuminate\Http\Request $request, \App\Models\PurchaseOrder $purchaseOrder) {
        if ($request->user()->cannot('view', $purchaseOrder)) { return response()->json(['message' => 'Unauthorized'], 403); }
        $purchaseOrder->load('items.product.unit');
        return response()->json(['items' => $purchaseOrder->items]);
    });

});

// Route ini harus bisa diakses publik oleh server Midtrans
Route::post('/payment/callback', [MidtransController::class, 'callback'])->name('midtrans.callback');

Route::fallback(function () {
    return redirect()->back(302, [], route('admin.dashboard'));
});