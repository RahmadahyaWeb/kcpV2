<?php

use Illuminate\Support\Facades\Request;

return [
    [
        "label" => "Dashboards",
        "icon" => "bx bx-home-smile",
        "route" => "dashboard",
        "active" => Request::is('dashboard*'),
        "roles" => ['super-user', 'admin', 'head-marketing', 'salesman', 'storer', 'head-warehouse', 'inventory', 'finance', 'ar', 'supervisor-area', 'fakturis'],
        "children" => []
    ],

    // MASTER
    [
        "label" => "Master",
        "roles" => ['super-user', 'admin'],
        "header" => true
    ],
    [
        "label" => "Toko",
        "icon" => "bx bx-store",
        "route" => "master-toko.index",
        "roles" => ['super-user', 'admin'],
        "active" => Request::is('master/toko*'),
        "children" => []
    ],
    [
        "label" => "Part",
        "icon" => "bx bx-wrench",
        "route" => "master-part.index",
        "roles" => ['super-user', 'admin'],
        "active" => Request::is('master/part*'),
        "children" => []
    ],
    [
        "label" => "Users",
        "icon" => "bx bx-user",
        "route" => "users.index",
        "roles" => ['super-user'],
        "active" => Request::is('master/users*'),
        "children" => []
    ],
    [
        "label" => "Monitoring API",
        "icon" => "bx bx-history",
        "route" => "log-viewer.index",
        "roles" => ['super-user'],
        "active" => Request::is('master/log-viewer*'),
        "children" => []
    ],

    // MARKETING
    [
        "label" => "Marketing",
        "roles" => ['super-user', 'admin', 'head-marketing', 'supervisor-area', 'salesman', 'fakturis'],
        "header" => true
    ],
    [
        "label" => "Stock Part",
        "icon" => "bx bx-file",
        "route" => "stock-part.index",
        "roles" => ['super-user', 'admin', 'head-marketing', 'supervisor-area', 'fakturis', 'salesman'],
        "active" => Request::is('stock-part*'),
        "children" => []
    ],
    [
        "label" => "DKS Scan",
        "icon" => "bx bx-qr-scan",
        "route" => "dks.index",
        "roles" => ['super-user', 'admin', 'salesman', 'head-marketing'],
        "active" => Request::is('dks/scan*'),
        "children" => []
    ],
    [
        "label" => "Invoice",
        "icon" => "bx bxs-receipt",
        "route" => "invoice.index",
        "roles" => ['super-user', 'admin', 'fakturis', 'head-marketing'],
        "active" => Request::is('invoice*'),
        "children" => []
    ],
    [
        "label" => "Retur",
        "icon" => "bx bx-package",
        "route" => "retur.invoice.index",
        "roles" => ['super-user', 'admin'],
        "active" => Request::is('retur*'),
        "children" => []
    ],
    [
        "label" => "Report Marketing",
        "icon" => "bx bx-layout",
        "route" => null,
        "roles" => ['super-user', 'admin', 'head-marketing', 'supervisor-area'],
        "active" => Request::is('report-marketing*'),
        "children" => [
            [
                "label" => "DKS",
                "route" => "report-marketing.dks",
                "active" => Request::is('report-marketing/dks*')
            ]
        ]
    ],

    // FINANCE
    [
        "label" => "Finance",
        "roles" => ['super-user', 'finance', 'ar', 'admin'],
        "header" => true
    ],
    [
        "label" => "Pembelian",
        "icon" => "bx bx-store",
        "route" => null,
        "roles" => ['super-user', 'finance', 'admin'],
        "active" => Request::is('purchase*'),
        "children" => [
            [
                "label" => "AOP",
                "route" => "purchase.aop.index",
                "active" => Request::is('purchase/aop*')
            ],
            [
                "label" => "Non AOP",
                "route" => "purchase.non.index",
                "active" => Request::is('purchase/non*')
            ],
        ]
    ],
    [
        "label" => "Customer Payment",
        "icon" => "bx bx-note",
        "route" => "customer-payment.index",
        "roles" => ['super-user', 'ar'],
        "active" => Request::is('customer-payment*'),
        "children" => []
    ],
    [
        "label" => "Daftar Piutang",
        "icon" => "bx bx-list-ul",
        "route" => "piutang.index",
        "roles" => ['super-user', 'ar'],
        "active" => Request::is('piutang*'),
        "children" => []
    ],
    [
        "label" => "Pajak",
        "icon" => "bx bx-note",
        "route" => null,
        "roles" => ['super-user', 'finance'],
        "active" => Request::is('pajak*'),
        "children" => [
            [
                "label" => "Pajak Keluaran",
                "route" => "pajak.pajak-keluaran.index",
                "active" => Request::is('pajak/pajak-keluaran*')
            ]
        ]
    ],
    [
        "label" => "Report Finance",
        "icon" => "bx bx-layout",
        "route" => null,
        "roles" => ['super-user', 'admin', 'finance'],
        "active" => Request::is('report-finance*'),
        "children" => [
            [
                "label" => "Invoice AOP",
                "route" => "report-finance.purchase.aop.index",
                "active" => Request::is('report-finance/purchase/aop*')
            ]
        ]
    ],

    // WAREHOUSE
    [
        "label" => "Warehouse",
        "roles" => ['super-user', 'storer', 'head-warehouse', 'inventory'],
        "header" => true
    ],
    [
        "label" => "Goods Receipt",
        "icon" => "bx bxs-receipt",
        "route" => null,
        "roles" => ['super-user', 'head-warehouse', 'inventory'],
        "active" => Request::is('goods-receipt*'),
        "children" => [
            [
                "label" => "AOP",
                "route" => "goods.aop.index",
                "active" => Request::is('goods-receipt/aop*')
            ],
            [
                "label" => "Non AOP",
                "route" => "goods.non.index",
                "active" => Request::is('goods-receipt/non*')
            ],
        ]
    ],
    [
        "label" => "Part Rak",
        "icon" => "bx bx-file",
        "route" => "part-rak.index",
        "roles" => ['super-user', 'head-warehouse', 'inventory', 'storer'],
        "active" => Request::is('part-rak'),
        "children" => []
    ],
    [
        "label" => "Delivery Order",
        "icon" => "bx bx-package",
        "route" => "delivery-order.index",
        "roles" => ['super-user'],
        "active" => Request::is('delivery-order*'),
        "children" => []
    ],
    [
        "label" => "Comparator",
        "icon" => "bx bx-scan",
        "route" => "comparator.index",
        "roles" => ['super-user', 'head-warehouse', 'inventory', 'storer'],
        "active" => Request::is('comparator*'),
        "children" => []
    ],
    [
        "label" => "Store Rak",
        "icon" => "bx bx-scan",
        "route" => "store-rak.index",
        "roles" => ['super-user', 'head-warehouse', 'inventory', 'storer'],
        "active" => Request::is('store-rak*'),
        "children" => []
    ],
    [
        "label" => "Stock Movement",
        "icon" => "bx bx-file",
        "route" => "stock-movement.index",
        "roles" => ['super-user', 'head-warehouse', 'inventory'],
        "active" => Request::is('stock-movement*'),
        "children" => []
    ],
];
