<?php

use App\Http\Controllers\API\ReturManualController;
use App\Http\Controllers\DkdController;
use App\Http\Controllers\DksController;
use App\Http\Controllers\PackingSheetController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\UploadController;
use App\Livewire\Auth\Login;
use App\Livewire\Comparator\IndexComparator;
use App\Livewire\CustomerPayment\DetailCustomerPayment;
use App\Livewire\CustomerPayment\IndexBonusToko;
use App\Livewire\CustomerPayment\IndexCustomerPayment;
use App\Livewire\Dashboard;
use App\Livewire\DeliveryOrder\DetailDeliveryOrder;
use App\Livewire\DeliveryOrder\IndexDeliveryOrder;
use App\Livewire\Dkd\IndexDaftarKehadiranDriver;
use App\Livewire\Dkd\RekapDaftarKehadiranDriver;
use App\Livewire\Dkd\SubmitDaftarKehadiranDriver;
use App\Livewire\Dks\RekapPunishment;
use App\Livewire\Dks\Scan;
use App\Livewire\Dks\Submit;
use App\Livewire\GoodsReceipt\GoodsReceiptAop;
use App\Livewire\GoodsReceipt\GoodsReceiptAopDetail;
use App\Livewire\GoodsReceipt\GoodsReceiptNonAop;
use App\Livewire\GoodsReceipt\GoodsReceiptNonAopDetail;
use App\Livewire\Intransit\DetailIntransit;
use App\Livewire\Intransit\FormUpdateIntransit;
use App\Livewire\Intransit\IndexIntransit;
use App\Livewire\Invoice\DetailInvoice;
use App\Livewire\Invoice\DetailSalesOrder;
use App\Livewire\Invoice\IndexInvoice;
use App\Livewire\Invoice\IndexInvoiceBosnet;
use App\Livewire\KelompokPart;
use App\Livewire\LogViewer;
use App\Livewire\Lss\DetailLss;
use App\Livewire\Lss\GenerateLss;
use App\Livewire\Lss\IndexLss;
use App\Livewire\Lss\InjectCogs;
use App\Livewire\Master\CreateExpedition;
use App\Livewire\Master\CreateUser;
use App\Livewire\Master\EditExpedition;
use App\Livewire\Master\EditMasterToko;
use App\Livewire\Master\EditUser;
use App\Livewire\Master\IndexExpedition;
use App\Livewire\Master\IndexMasterToko;
use App\Livewire\Master\IndexUser;
use App\Livewire\Pajak\IndexPajakKeluaran;
use App\Livewire\Piutang\IndexPiutang;
use App\Livewire\ProductPart;
use App\Livewire\Purchase\CreatePurchaseNonAop;
use App\Livewire\Purchase\PurchaseAop;
use App\Livewire\Purchase\PurchaseAopDetail;
use App\Livewire\Purchase\PurchaseNonAop;
use App\Livewire\Purchase\PurchaseNonAopDetail;
use App\Livewire\ReportFinance\PurchaseAopReport;
use App\Livewire\ReportMarketing\MonitoringDks;
use App\Livewire\ReturInvoice\DetailReturInvoice;
use App\Livewire\ReturInvoice\IndexReturInvoice;
use App\Livewire\Salesman;
use App\Livewire\StockMovement\IndexStockMovement;
use App\Livewire\Master\IndexMasterPart;
use App\Livewire\Pajak\IndexPajakMasukan;
use App\Livewire\ReportFinance\AgingReport;
use App\Livewire\ReportFinance\IndexLaporanPenerimaanPiutang;
use App\Livewire\ReportMarketing\IndexLaporanInvoice;
use App\Livewire\ReportWarehouse\MonitoringDkd;
use App\Livewire\StockPart\IndexStockPart;
use App\Livewire\StockPart\IndexStockPartRak;
use App\Livewire\StoreRak\DetailStoreRak;
use App\Livewire\StoreRak\IndexStoreRak;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    // DASHBOARD
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // PRODUCT PART
    Route::get('/dashboard/product-part', ProductPart::class)->name('dashboard.product-part');

    // SALESMAN
    Route::get('/dashboard/salesman', Salesman::class)->name('dashboard.salesman');

    // KELOMPOK PART
    Route::get('/dashboard/kelompok-part', KelompokPart::class)->name('dashboard.kelompok-part');

    /**
     * super-user
     */
    Route::middleware('role:super-user')->group(function () {
        // USERS
        Route::get('/master/users', IndexUser::class)->name('users.index');
        Route::get('/master/users/create', CreateUser::class)->name('users.create');
        Route::get('/master/users/edit/{user}', EditUser::class)->name('users.edit');

        // LOG VIEWER
        Route::get('/master/log-viewer', LogViewer::class)->name('log-viewer.index');

        // SYNC
        Route::get('/sync/limit_kredit/{kd_outlet}', [SyncController::class, 'sync_limit_kredit'])->name('sync.limit-kredit');

        // SYNC INTRANSI
        // Route::get('/sync/intransit', [SyncController::class, 'sync_intransit'])->name('sync.intransit');

        // EXPEDITION
        Route::get('/master/expedition', IndexExpedition::class)->name('expedition.index');
        Route::get('/master/expedition/create', CreateExpedition::class)->name('expedition.create');
        Route::get('/master/expedition/edit/{kd_expedition}', EditExpedition::class)->name('expedition.edit');

        // LSS
        Route::get('/report-marketing/lss', IndexLss::class)->name('report-marketing.lss.index');
        Route::get('/report-marketing/lss/inject', InjectCogs::class)->name('report-marketing.lss.inject');
        Route::get('/report-marketing/lss/generate', GenerateLss::class)->name('report-marketing.lss.generate');
        Route::get('/report-marketing/lss/detail', DetailLss::class)->name('report-marketing.lss.detail');
        Route::post('/report-marketing/lss/upload', [UploadController::class, 'import_cogs'])->name('upload-cogs');
    });

    /**
     * super-user
     * admin
     */
    Route::middleware('role:admin|super-user')->group(function () {
        // MASTER TOKO
        Route::get('/master/toko', IndexMasterToko::class)->name('master-toko.index');
        Route::get('/master/toko/edit/{kode_toko}', EditMasterToko::class)->name('master-toko.edit');
        Route::post('/master/toko/upload-frekuensi', [UploadController::class, 'import_frekuensi_toko'])->name('upload-frekuensi');

        // MASTER PART
        Route::get('/master/part', IndexMasterPart::class)->name('master-part.index');
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
     * salesman
     * admin
     * fakturis
     */
    Route::middleware('role:salesman|super-user|admin|supervisor-area|fakturis')->group(function () {
        Route::get('/stock-part', IndexStockPart::class)->name('stock-part.index');
    });

    /**
     * super-user
     * supervisor-area
     */
    Route::middleware('role:supervisor-area|super-user')->group(function () {
        // REPORT MARKETING
        Route::get('/report-marketing/dks', MonitoringDks::class)->name('report-marketing.dks');
        Route::get('/report-marketing/dks/rekap-punishment', RekapPunishment::class)->name('report-marketing.dks.rekap-punishment');
    });

    /**
     * super-user
     * head-marketing
     * supervisor-area
     * ar
     * fakturis
     */
    Route::middleware('role:supervisor-area|super-user|head-marketing|ar|fakturis')->group(function () {
        // REPORT MARKETING => LAPORAN INVOICE
        Route::get('/report-marketing/laporan-invoice', IndexLaporanInvoice::class)->name('report-marketing.laporan-invoice');
    });


    /**
     * super-user
     * head-warehouse
     * storer
     */
    Route::middleware('role:storer|head-warehouse|super-user|inventory')->group(function () {
        // COMPARATOR
        Route::get('/comparator', IndexComparator::class)->name('comparator.index');

        // STORE RAK
        Route::get('/store-rak', IndexStoreRak::class)->name('store-rak.index');
        Route::get('/store-rak/detail/{header_id}', DetailStoreRak::class)->name('store-rak.detail');

        // STOCK PART RAK
        Route::get('/part-rak', IndexStockPartRak::class)->name('part-rak.index');

        // INTRANSIT
        Route::get('/intransit', IndexIntransit::class)->name('intransit.index');
        Route::get('/intransit/detail/{delivery_note}', DetailIntransit::class)->name('intransit.detail');
        Route::get('/intransit/update/{id}', FormUpdateIntransit::class)->name('intransit.update');
    });

    /**
     * super-user
     * head-warehouse
     * inventory
     */
    Route::middleware('role:inventory|head-warehouse|super-user|admin')->group(function () {
        // GOODS RECEIPT AOP
        Route::get('/goods-receipt/aop', GoodsReceiptAop::class)->name('goods.aop.index');
        Route::get('/goods-receipt/aop/{invoiceAop}', GoodsReceiptAopDetail::class)->name('goods.aop.detail');

        // GOODS RECEIPT NON AOP
        Route::get('/goods-receipt/non', GoodsReceiptNonAop::class)->name('goods.non.index');
        Route::get('/goods-receipt/non/{invoiceNon}', GoodsReceiptNonAopDetail::class)->name('goods.non.detail');

        // STOCK MOVEMENT
        Route::get('/stock-movement', IndexStockMovement::class)->name('stock-movement.index');

        // RETUR INVOICE
        Route::get('/retur/invoice', IndexReturInvoice::class)->name('retur.invoice.index');
        Route::get('/retur/invoice/{no_retur}', DetailReturInvoice::class)->name('retur.invoice.detail');

        // PRINT SO
        Route::get('/sales-order/print/{noso}', [SalesOrderController::class, 'print'])->name('so.print');

        // PRINT LABEL
        Route::get('/packingsheet/print/label/{nops}', [PackingSheetController::class, 'print_label'])->name('ps.print.label');
    });

    /**
     * super-user
     * finance
     * admin
     */
    Route::middleware('role:finance|super-user|admin')->group(function () {
        // PURCHASE AOP
        Route::get('/purchase/aop', PurchaseAop::class)->name('purchase.aop.index');
        Route::get('/purchase/aop/{invoiceAop}', PurchaseAopDetail::class)->name('purchase.aop.detail');

        // PURCHASE NON AOP
        Route::get('/purchase/non', PurchaseNonAop::class)->name('purchase.non.index');
        Route::get('/purchase/non/create', CreatePurchaseNonAop::class)->name('purchase.non.create');
        Route::get('/purchase/non/{invoiceNon}', PurchaseNonAopDetail::class)->name('purchase.non.detail');

        // REPORT FINANCE
        Route::get('/report-finance/purchase/aop', PurchaseAopReport::class)->name('report-finance.purchase.aop.index');
        Route::get('/report-finance/penerimaan-piutang', IndexLaporanPenerimaanPiutang::class)->name('report-finance.penerimaan-piutang.index');

        // PAJAK KELUARAN
        Route::get('/pajak/pajak-keluaran', IndexPajakKeluaran::class)->name('pajak.pajak-keluaran.index');

        // PAJAK MASUKAN
        Route::get('/pajak/pajak-masukan', IndexPajakMasukan::class)->name('pajak.pajak-masukan.index');
    });

    /**
     * super-user
     * fakturis
     */
    Route::middleware('role:fakturis|super-user')->group(function () {
        // INVOICE
        Route::get('/invoice', IndexInvoice::class)->name('invoice.index');
        Route::get('/invoice/detail/{noinv}', DetailInvoice::class)->name('invoice.detail');

        // INVOICE BOSNET
        Route::get('/invoice-bosnet', IndexInvoiceBosnet::class)->name('invoice.bosnet');

        // DETAIL SO (BELUM INVOICE)
        Route::get('/invoice/sales-order/{noso}', DetailSalesOrder::class)->name('invoice.sales-order.detail');
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
        Route::get('/customer-payment/detail/{no_piutang}', DetailCustomerPayment::class)->name('customer-payment.detail');

        // CP BONUS TOKO
        Route::get('/customer-payment/bonus-toko', IndexBonusToko::class)->name('customer-payment.bonus-toko');

        // PIUTANG
        Route::get('/piutang', IndexPiutang::class)->name('piutang.index');

        // AGING
        Route::get('/report-finance/aging', AgingReport::class)->name('report-finance.aging.index');
    });

    /**
     * super-user
     * driver
     */
    Route::middleware('role:driver|super-user')->group(function () {
        // DKS
        Route::get('/daftar-kehadiran-driver/scan', IndexDaftarKehadiranDriver::class)->name('daftar-kehadiran-driver.index');
        Route::get('/daftar-kehadiran-driver/scan/{kode_toko}', SubmitDaftarKehadiranDriver::class)->name('daftar-kehadiran-driver.submit');
        Route::post('daftar-kehadiran-driver/scan/store', [DkdController::class, 'store'])->name('daftar-kehadiran-driver.store');
    });

    /**
     * super-user
     * head-warehouse
     */
    Route::middleware('role:head-warehouse|super-user')->group(function () {
        Route::get('/report-warehouse/dkd', MonitoringDkd::class)->name('report-warehouse.dkd');
        Route::get('/report-warehouse/dkd/rekap-punishment', RekapDaftarKehadiranDriver::class)->name('report-warehouse.dkd.rekap-daftar-kehadiran-driver');
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
