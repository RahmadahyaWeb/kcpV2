<?php

use Illuminate\Support\Facades\Request;

return [
    [
        "label" => "Dashboards",
        "icon" => "bx bx-home-smile",
        "route" => "dashboard",
        "active" => Request::is('dashboard'),
        "children" => []
    ],

    // MASTER
    [
        "label" => "Master",
        "header" => true
    ],
    [
        "label" => "Toko",
        "icon" => "bx bx-store",
        "route" => "master-toko.index",
        "active" => Request::is('master/toko*'),
        "children" => []
    ],
    [
        "label" => "Users",
        "icon" => "bx bx-user",
        "route" => "users.index",
        "active" => Request::is('master/users*'),
        "children" => []
    ],

    // MARKETING
    [
        "label" => "Marketing",
        "header" => true
    ],
    [
        "label" => "DKS Scan",
        "icon" => "bx bx-qr-scan",
        "route" => "dks.index",
        "active" => Request::is('dks/scan*'),
        "children" => []
    ],
    [
        "label" => "Invoice",
        "icon" => "bx bxs-receipt",
        "route" => "invoice.index",
        "active" => Request::is('invoice*'),
        "children" => []
    ],
    [
        "label" => "Report Marketing",
        "icon" => "bx bx-layout",
        "route" => null,
        "active" => Request::is('report*'),
        "children" => [
            [
                "label" => "DKS",
                "route" => "report.dks",
                "active" => Request::is('report/dks*')
            ]
        ]
    ],

    // FINANCE
    [
        "label" => "Finance",
        "header" => true
    ],
    [
        "label" => "Pembelian",
        "icon" => "bx bx-store",
        "route" => null,
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
        "active" => Request::is('customer-payment*'),
        "children" => []
    ],
    [
        "label" => "Daftar Piutang",
        "icon" => "bx bx-list-ul",
        "route" => "piutang.index",
        "active" => Request::is('piutang*'),
        "children" => []
    ],

    // WAREHOUSE
    [
        "label" => "Warehouse",
        "header" => true
    ],
    [
        "label" => "Goods Receipt",
        "icon" => "bx bxs-receipt",
        "route" => null,
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
        "label" => "Delivery Order",
        "icon" => "bx bx-package",
        "route" => "delivery-order.index",
        "active" => Request::is('delivery-order*'),
        "children" => []
    ],
    [
        "label" => "Comparator",
        "icon" => "bx bx-scan",
        "route" => "comparator.index",
        "active" => Request::is('comparator*'),
        "children" => []
    ],
];
