<?php

use App\Http\Controllers\DksController;
use App\Livewire\Auth\Login;
use App\Livewire\Comparator\IndexComparator;
use App\Livewire\CustomerPayment\DetailCustomerPayment;
use App\Livewire\CustomerPayment\IndexCustomerPayment;
use App\Livewire\Dashboard;
use App\Livewire\DeliveryOrder\DetailDeliveryOrder;
use App\Livewire\DeliveryOrder\IndexDeliveryOrder;
use App\Livewire\Dks\RekapPunishment;
use App\Livewire\Dks\Scan;
use App\Livewire\Dks\Submit;
use App\Livewire\GoodsReceipt\GoodsReceiptAop;
use App\Livewire\GoodsReceipt\GoodsReceiptAopDetail;
use App\Livewire\GoodsReceipt\GoodsReceiptNonAop;
use App\Livewire\GoodsReceipt\GoodsReceiptNonAopDetail;
use App\Livewire\Invoice\DetailInvoice;
use App\Livewire\Invoice\IndexInvoice;
use App\Livewire\Invoice\IndexInvoiceBosnet;
use App\Livewire\Master\CreateUser;
use App\Livewire\Master\EditMasterToko;
use App\Livewire\Master\EditUser;
use App\Livewire\Master\IndexMasterToko;
use App\Livewire\Master\IndexUser;
use App\Livewire\Piutang\IndexPiutang;
use App\Livewire\Purchase\CreatePurchaseNonAop;
use App\Livewire\Purchase\PurchaseAop;
use App\Livewire\Purchase\PurchaseAopDetail;
use App\Livewire\Purchase\PurchaseNonAop;
use App\Livewire\Purchase\PurchaseNonAopDetail;
use App\Livewire\ReportMarketing\MonitoringDks;
use App\Livewire\StockMovement\IndexStockMovement;
use App\Livewire\StockPart\IndexStockPartRak;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    // DASHBOARD
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    /**
     * super-user
     */
    Route::middleware('role:super-user')->group(function () {
        // USERS
        Route::get('/master/users', IndexUser::class)->name('users.index');
        Route::get('/master/users/create', CreateUser::class)->name('users.create');
        Route::get('/master/users/edit/{user}', EditUser::class)->name('users.edit');
    });

    /**
     * super-user
     * admin
     */
    Route::middleware('role:admin|super-user')->group(function () {
        // MASTER TOKO
        Route::get('/master/toko', IndexMasterToko::class)->name('master-toko.index');
        Route::get('/master/toko/edit/{kode_toko}', EditMasterToko::class)->name('master-toko.edit');
    });

    /**
     * super-user
     * salesman
     */
    Route::middleware('role:salesman|super-user')->group(function () {
        // DKS
        Route::get('/dks/scan', Scan::class)->name('dks.index');
        Route::get('/dks/scan/{kode_toko}', Submit::class)->name('dks.submit');
        Route::post('dks/scan/store', [DksController::class, 'store'])->name('dks.store');
    });

    /**
     * super-user
     * supervisor-area
     */
    Route::middleware('role:supervisor-area|super-user')->group(function () {
        // REPORT MARKETING
        Route::get('/report/dks', MonitoringDks::class)->name('report.dks');
        Route::get('/report/dks/rekap-punishment', RekapPunishment::class)->name('report.dks.rekap-punishment');
    });

    /**
     * super-user
     * head-warehouse
     * storer
     */
    Route::middleware('role:storer|head-warehouse|super-user|inventory')->group(function () {
        // COMPARATOR
        Route::get('/comparator', IndexComparator::class)->name('comparator.index');

        // STOCK PART RAK
        Route::get('/stock-part/rak', IndexStockPartRak::class)->name('stock-part.rak.index');
    });

    /**
     * super-user
     * head-warehouse
     * inventory
     */
    Route::middleware('role:inventory|head-warehouse|super-user')->group(function () {
        // GOODS RECEIPT AOP
        Route::get('/goods-receipt/aop', GoodsReceiptAop::class)->name('goods.aop.index');
        Route::get('/goods-receipt/aop/{invoiceAop}', GoodsReceiptAopDetail::class)->name('goods.aop.detail');

        // GOODS RECEIPT NON AOP
        Route::get('/goods-receipt/non', GoodsReceiptNonAop::class)->name('goods.non.index');
        Route::get('/goods-receipt/non/{invoiceNon}', GoodsReceiptNonAopDetail::class)->name('goods.non.detail');

        // STOCK MOVEMENT
        Route::get('/stock-movement', IndexStockMovement::class)->name('stock-movement.index');
    });

    /**
     * super-user
     * finance
     */
    Route::middleware('role:finance|super-user')->group(function () {
        // PURCHASE AOP
        Route::get('/purchase/aop', PurchaseAop::class)->name('purchase.aop.index');
        Route::get('/purchase/aop/{invoiceAop}', PurchaseAopDetail::class)->name('purchase.aop.detail');

        // PURCHASE NON AOP
        Route::get('/purchase/non', PurchaseNonAop::class)->name('purchase.non.index');
        Route::get('/purchase/non/create', CreatePurchaseNonAop::class)->name('purchase.non.create');
        Route::get('/purchase/non/{invoiceNon}', PurchaseNonAopDetail::class)->name('purchase.non.detail');
    });

    /**
     * super-user
     * fakturis
     */
    Route::middleware('role:fakturis|super-user')->group(function () {
        // INVOICE
        Route::get('/invoice', IndexInvoice::class)->name('invoice.index');
        Route::get('/invoice/{noinv}', DetailInvoice::class)->name('invoice.detail');

        // INVOICE BOSNET
        Route::get('/invoice-bosnet', IndexInvoiceBosnet::class)->name('invoice.bosnet');
    });

    /**
     * super-user
     * ar
     */
    Route::middleware('role:ar|super-user')->group(function () {
        // DELIVERY ORDER
        Route::get('/delivery-order', IndexDeliveryOrder::class)->name('delivery-order.index');
        Route::get('/delivery-order/{no_lkh}', DetailDeliveryOrder::class)->name('delivery-order.detail');

        // CUSTOMER PAYMENT
        Route::get('/customer-payment', IndexCustomerPayment::class)->name('customer-payment.index');
        Route::get('/customer-payment/{no_piutang}', DetailCustomerPayment::class)->name('customer-payment.detail');

        // PIUTANG
        Route::get('/piutang', IndexPiutang::class)->name('piutang.index');
    });

    // LOGOUT
    Route::get('logout', function () {
        Auth::logout();

        return redirect()->route('login');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
});
