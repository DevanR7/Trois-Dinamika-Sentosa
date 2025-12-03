<?php

use Illuminate\Support\Facades\Route;

// ==============================================================================
// IMPORT CONTROLLERS (SEMUA MENGACU KE NAMESPACE ADMIN)
// ==============================================================================

// Auth & Core
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\GoogleAuthController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\DataMigrationController;

// Master Data & Inventory
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\StockOpnameController;

// Sales & Clients
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\SalesReturnController;
use App\Http\Controllers\Admin\ClientOrderReviewController;
use App\Http\Controllers\Admin\OrderChangeRequestController;
use App\Http\Controllers\Admin\InvoiceAdjustmentController;
use App\Http\Controllers\Admin\BulkSalesPaymentController; // Controller yang diperbaiki

// Purchase & Suppliers
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseOrderPaymentController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\BulkPurchasePaymentController;
use App\Http\Controllers\Admin\PurchaseOrderAdjustmentController;

// Finance & Accounting
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PaymentClearanceController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FixedAssetController;
use App\Http\Controllers\Admin\EquityTransactionController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\LoanPaymentController;
use App\Http\Controllers\Admin\CompanyBankAccountController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\ManualJournalController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\GeneralLedgerController;
use App\Http\Controllers\Admin\ClosingBookController;
use App\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| Prefix URL  : /admin  (Diatur di RouteServiceProvider)
| Prefix Name : admin.  (Diatur di RouteServiceProvider)
| Middleware  : web     (Diatur di RouteServiceProvider)
*/

// --- 1. ADMIN AUTHENTICATION ---
require __DIR__.'/admin-auth.php';

// --- 2. GOOGLE AUTH (Admin) ---
// URL: /admin/auth/google
Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);


// --- 3. PROTECTED ADMIN ROUTES ---
Route::middleware(['auth', 'verified'])->group(function () {

    // ========================================================================
    // A. DASHBOARD & PROFILE
    // ========================================================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::put('/profile/password', 'updatePassword')->name('profile.password.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    // ========================================================================
    // B. SYSTEM & MASTER DATA
    // ========================================================================
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();

    Route::resource('roles', RoleController::class)->except(['show']);
    
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    
    Route::resource('units', UnitController::class)->except(['show']);
    Route::resource('taxes', TaxController::class)->except(['show']);
    
    // Payment Methods & Archive
    Route::resource('payment-methods', PaymentMethodController::class)->except(['show']);
    Route::prefix('archived-payment-methods')->name('payment-methods.archived.')
        ->middleware('permission:manage-payment-methods')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'archivedIndex'])->name('index');
            Route::post('/{id}/restore', [PaymentMethodController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [PaymentMethodController::class, 'forceDelete'])->name('forceDelete');
    });

    Route::resource('announcements', AnnouncementController::class);
    Route::patch('announcements/{announcement}/restore', [AnnouncementController::class, 'restore'])->name('announcements.restore')->withTrashed();
    Route::delete('announcements/{announcement}/force-delete', [AnnouncementController::class, 'forceDelete'])->name('announcements.forceDelete')->withTrashed();

    Route::resource('company-bank-accounts', CompanyBankAccountController::class)
        ->except(['show'])
        ->middleware('permission:manage-bank-accounts');

    // ========================================================================
    // C. INVENTORY & PRODUCTS
    // ========================================================================
    Route::resource('products', ProductController::class);
    
    Route::get('/stock-opnames/worksheet', [StockOpnameController::class, 'downloadWorksheet'])->name('stock-opnames.worksheet');
    Route::resource('stock-opnames', StockOpnameController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // ========================================================================
    // D. SALES & CLIENT MANAGEMENT
    // ========================================================================
    Route::resource('clients', ClientController::class)->except(['show']);
    Route::controller(ClientController::class)->prefix('clients')->name('clients.')->group(function () {
        Route::patch('/{client}/approve', 'approve')->name('approve');
        Route::patch('/{client}/lock', 'lock')->name('lock');
        Route::patch('/{client}/unlock', 'unlock')->name('unlock');
        Route::patch('/{client}/restore', 'restore')->name('restore')->withTrashed();
        Route::get('/{client}', 'show')->name('show')->withTrashed();
    });

    Route::resource('sales-orders', SalesOrderController::class)->parameters(['sales-orders' => 'order']);
    
    // Client Reviews & Change Requests
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

    // Invoices
    Route::resource('invoices', SalesInvoiceController::class);
    Route::controller(SalesInvoiceController::class)->prefix('invoices')->name('invoices.')->group(function(){
        Route::get('/create/from-order/{order}', 'createFromOrder')->name('createFromOrder');
        Route::post('/{invoice}/cancel', 'cancel')->name('cancel');
        Route::get('/{invoice}/download', 'downloadPDF')->name('pdf');
        Route::post('/{invoice}/confirm', 'confirm')->name('confirm');
    });
    
    Route::prefix('invoice-adjustments')->name('invoice-adjustments.')->group(function () {
        Route::get('/create', [InvoiceAdjustmentController::class, 'create'])->name('create');
        Route::get('/create-manual/{invoice}', [InvoiceAdjustmentController::class, 'createManual'])->name('create.manual');
        Route::post('/store-manual', [InvoiceAdjustmentController::class, 'storeManual'])->name('store.manual');
        Route::get('/create-auto/{invoice}', [InvoiceAdjustmentController::class, 'createAuto'])->name('create.auto');
        Route::post('/store-auto/{invoice}', [InvoiceAdjustmentController::class, 'storeAuto'])->name('store.auto');
        Route::delete('/{invoiceAdjustment}', [InvoiceAdjustmentController::class, 'destroy'])->name('destroy');
    });

    Route::resource('sales-returns', SalesReturnController::class);

    // Single Payments
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

    // ------------------------------------------------------------------------
    // [REVISI PENTING] Bulk Sales Payment (Dengan Route Pending & ShowPending)
    // ------------------------------------------------------------------------
    Route::controller(BulkSalesPaymentController::class)
        ->prefix('bulk-sales-payments')
        ->name('bulk-sales-payments.')
        ->group(function () {
            // 1. History / Index
            Route::get('/', 'index')->name('index'); 
            
            // 2. Form Buat Baru
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');

            // 3. List Pending (Antrian Verifikasi) -> Mengatasi error orange
            Route::get('/pending', 'pending')->name('pending'); 

            // 4. Detail Pending (Khusus verifikasi) -> Mengatasi error orange
            Route::get('/pending/{bulkSalesPayment}', 'showPending')->name('showPending');

            // 5. Detail Umum (History)
            Route::get('/{bulkSalesPayment}', 'show')->name('show');

            // 6. Action
            Route::post('/{bulkSalesPayment}/approve', 'approve')->name('approve');
            Route::post('/{bulkSalesPayment}/reject', 'reject')->name('reject');
        });

    // ========================================================================
    // E. PURCHASING & SUPPLIERS
    // ========================================================================
    Route::resource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore')->withTrashed();

    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::controller(PurchaseOrderController::class)->prefix('purchase-orders')->name('purchase-orders.')->group(function(){
        Route::post('/{purchaseOrder}/cancel', 'cancel')->name('cancel');
        Route::post('/{purchaseOrder}/receive', 'receive')->name('receive');
        Route::post('/{purchaseOrder}/mark-as-paid', 'markAsPaid')->name('markAsPaid');
        Route::post('/{purchaseOrder}/add-supplier-invoice', 'addSupplierInvoice')->name('addSupplierInvoice');
        Route::get('/{purchaseOrder}/download-pdf', 'downloadPDF')->name('pdf');
    });

    Route::prefix('purchase-order-adjustments')->name('purchase-order-adjustments.')->group(function () {
        Route::get('/create', [PurchaseOrderAdjustmentController::class, 'create'])->name('create');
        Route::get('/create-manual/{purchaseOrder}', [PurchaseOrderAdjustmentController::class, 'createManual'])->name('create.manual');
        Route::post('/store-manual', [PurchaseOrderAdjustmentController::class, 'storeManual'])->name('store.manual');
        Route::get('/create-auto/{purchaseOrder}', [PurchaseOrderAdjustmentController::class, 'createAuto'])->name('create.auto');
        Route::post('/store-auto/{purchaseOrder}', [PurchaseOrderAdjustmentController::class, 'storeAuto'])->name('store.auto');
        Route::delete('/{purchaseOrderAdjustment}', [PurchaseOrderAdjustmentController::class, 'destroy'])->name('destroy');
    });

    Route::resource('purchase-returns', PurchaseReturnController::class);

    Route::post('/purchase-orders/{purchaseOrder}/payments', [PurchaseOrderPaymentController::class, 'store'])->name('purchase-orders.payments.store');
    Route::delete('/purchase-order-payments/{payment}', [PurchaseOrderPaymentController::class, 'destroy'])->name('purchase-orders.payments.destroy');
    
    Route::prefix('bulk-purchase-payments')->name('bulk-purchase-payments.')->group(function () {
        Route::get('/create', [BulkPurchasePaymentController::class, 'create'])->name('create');
        Route::post('/', [BulkPurchasePaymentController::class, 'store'])->name('store');
    });

    // ========================================================================
    // F. FINANCE & ACCOUNTING
    // ========================================================================
    Route::resource('chart-of-accounts', ChartOfAccountController::class)->except(['show']);
    
    Route::resource('expenses', ExpenseController::class);
    Route::resource('fixed-assets', FixedAssetController::class);
    Route::resource('equity-transactions', EquityTransactionController::class);
    
    Route::resource('loans', LoanController::class);
    Route::resource('loans.payments', LoanPaymentController::class)->only(['create', 'store', 'destroy'])->scoped();

    Route::resource('manual-journals', ManualJournalController::class)->except(['show']);
    Route::get('manual-journals/{manualJournal}', [ManualJournalController::class, 'show'])->name('manual-journals.show');

    Route::resource('bank-reconciliations', BankReconciliationController::class)->except(['edit']);

    Route::get('/closing-book', [ClosingBookController::class, 'index'])->name('closing-book.index');
    Route::post('/closing-book', [ClosingBookController::class, 'store'])->name('closing-book.store');
    
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/general-ledger', [GeneralLedgerController::class, 'index'])->name('reports.general-ledger');

    Route::middleware(['permission:manage-payment-clearance'])->prefix('payment-clearance')->name('payment-clearance.')->group(function () {
        Route::get('/', [PaymentClearanceController::class, 'index'])->name('index');
        Route::post('/sales/{payment}/approve', [PaymentClearanceController::class, 'approveSalesPayment'])->name('sales.approve');
        Route::post('/sales/{payment}/reject', [PaymentClearanceController::class, 'rejectSalesPayment'])->name('sales.reject');
        Route::post('/purchase/{purchaseOrderPayment}/approve', [PaymentClearanceController::class, 'approvePurchasePayment'])->name('purchase.approve');
        Route::post('/purchase/{purchaseOrderPayment}/reject', [PaymentClearanceController::class, 'rejectPurchasePayment'])->name('purchase.reject');
    });

    // ========================================================================
    // G. INTERNAL APIs & HELPERS (For Admin UI AJAX)
    // ========================================================================
    // URL: /admin/api/...
    
    Route::get('/api/clients/{client}/unpaid-invoices', [BulkSalesPaymentController::class, 'getUnpaidInvoicesApi'])->name('api.clients.unpaid-invoices');

    Route::get('/api/clients/{client}/details', function (\App\Models\Client $client) {
        return response()->json([
            'client_id' => $client->client_id,
            'client_name' => $client->client_name,
            'balance' => $client->balance,
            'pending_balance' => $client->pending_balance,
        ]);
    })->name('api.clients.details');

    Route::get('/api/suppliers/{supplier}/unpaid-purchase-orders', [BulkPurchasePaymentController::class, 'getUnpaidPurchaseOrdersApi'])->name('api.suppliers.unpaid-pos');
    
    Route::get('/api/suppliers/{supplier}/details', function (\App\Models\Supplier $supplier) {
        return response()->json([
            'supplier_id' => $supplier->supplier_id,
            'supplier_name' => $supplier->supplier_name,
            'balance' => $supplier->balance,
            'pending_balance' => $supplier->pending_balance,
        ]);
    })->name('api.suppliers.details');

    Route::get('/api/invoices/{invoice}/items', function (Illuminate\Http\Request $request, \App\Models\SalesInvoice $invoice) {
        if ($request->user()->cannot('view', $invoice)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $invoice->load('items.product');
        return response()->json([
            'invoice' => $invoice,
            'items' => $invoice->items
        ]);
    });

    Route::get('/api/purchase-orders/{purchaseOrder}/items', function (Illuminate\Http\Request $request, \App\Models\PurchaseOrder $purchaseOrder) {
        if ($request->user()->cannot('view', $purchaseOrder)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $purchaseOrder->load('items.product.unit');
        return response()->json(['items' => $purchaseOrder->items]);
    });

    // ========================================================================
    // H. UTILITIES
    // ========================================================================
    Route::get('/migration', [DataMigrationController::class, 'index'])->name('migration.index');
    Route::post('/migration/products', [DataMigrationController::class, 'importProducts'])->name('migration.import-products');
    Route::post('/migration/clients', [DataMigrationController::class, 'importClients'])->name('migration.import-clients');

});

// TIDAK ADA REQUIRE DUPLIKAT DI SINI