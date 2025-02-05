<!doctype html>

<html lang="en" class="light-style layout-menu-fixed layout-compact layout-transitioning" dir="ltr"
    data-theme="theme-default" data-assets-path="{{ asset('assets/') }}" data-template="vertical-menu-template-free"
    data-style="light" x-data="{ isMenuExpanded: false }" :class="{ 'layout-menu-expanded': isMenuExpanded }">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>{{ $title ?? 'KCP' }}</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('img/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    {{-- <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" /> --}}

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('assets/js/config.js') }}"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        .placeholder {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 300px;
            width: 100%;
            background-color: #f8f9fa;
            border: 2px dashed #ccc;
            border-radius: 10px;
            color: #6c757d;
            font-size: 1.5em;
            padding: 20px;
            box-sizing: border-box;
            cursor: default;
        }

        #map {
            height: 200px;
        }

        #layout-menu {
            overflow-y: auto;
            overflow-x: hidden;
        }

        .last-item {
            padding-bottom: 100px;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .layout-menu::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .layout-menu {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }

        .chartCard {
            height: 100vh;
            width: 100%;
        }

        .chartBox {
            height: 100%;
            width: 100%;
            padding: 50px;
            border-radius: 0.375rem;
            background: white;
            overflow: auto
        }

        .canvas {
            height: 100%;
        }

        @media screen and (max-width: 768px) {
            .canvas {
                width: 500%;
            }
        }
    </style>
</head>

<body>

    @php
        $menus = include resource_path('menus.php');
    @endphp

    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->

            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" x-data="{ isMenuCollapsed: false }">
                <div class="app-brand demo">
                    <a href="/" class="app-brand-link">
                        <span class="app-brand-text demo menu-text fw-bold">KCP APP</span>
                    </a>
                </div>
                <div class="menu-inner-shadow"></div>

                <x-menus :menus='$menus' />
            </aside>

            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->

                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)"
                            @click="isMenuExpanded = !isMenuExpanded">
                            <i class="bx bx-menu bx-md"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <div>
                                            <i class='bx bxs-user-circle' style='font-size: 40px;'></i>
                                        </div>
                                    </div>
                                </a>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0">{{ Auth::user()->username }}</h6>
                                                    <small class="text-muted">
                                                        {!! implode(', ', Auth::user()->roles->pluck('name')->toArray()) !!}
                                                    </small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider my-1"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/logout">
                                            <span>Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>
                </nav>

                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->

                    <div class="container-xxl flex-grow-1 container-p-y">
                        {{ $slot }}
                    </div>
                    <!-- / Content -->

                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl">
                            <div
                                class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                                <div class="text-body">
                                    © {{ date('Y') }}, PT Kumala Central Partindo
                                </div>
                                <div class="text-body">
                                    Designed & Developed by Rahmat
                                </div>
                            </div>
                        </div>
                    </footer>

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->

            <!-- Toast -->
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto">Kumala Central Partindo</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body" id="toast-message">

                    </div>
                </div>
            </div>
            <!-- / Toast -->

            <x-modal-dks />
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle" @click="isMenuExpanded = !isMenuExpanded"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->

    <script data-navigate-once src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script data-navigate-once src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script data-navigate-once src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    {{-- <script data-navigate-once src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script> --}}
    {{-- <script data-navigate-once src="{{ asset('assets/vendor/js/menu.js') }}"></script> --}}

    <!-- endbuild -->

    <!-- Main JS -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-toast', (event) => {
                var toastLive = document.getElementById('liveToast');
                var toast = new bootstrap.Toast(toastLive);

                document.getElementById('toast-message').innerHTML = event[0].message;

                toast.show();
            });

            Livewire.on('open-modal-faktur-pajak', () => {
                $('#editFakturPajakModal').modal('show');
            });

            Livewire.on('hide-modal-faktur-pajak', () => {
                $('#editFakturPajakModal').modal('hide');
            });

            Livewire.on('open-modal-program', () => {
                $('#createProgramModal').modal('show');
            });

            Livewire.on('hide-modal-program', () => {
                $('#createProgramModal').modal('hide');
            });

            Livewire.on('saved', () => {
                document.getElementById('part_number').focus();
            });
        });
    </script>

    <script src="{{ asset('js/html5-qrcode.min.js') }}" data-navigate-once></script>

    <script data-navigate-once>
        const html5QrCode = new Html5Qrcode("reader");
        let scanning = false;
    </script>

    {{-- <script src="{{ asset('assets/js/main.js') }}"></script> --}}

    @stack('script')
</body>

</html>
