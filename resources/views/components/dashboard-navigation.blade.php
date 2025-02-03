<div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
    <a href="{{ route('dashboard') }}" class="btn btn-{{ Request::is('dashboard') ? 'primary' : 'secondary' }}"
        wire:navigate wire:ignore.self>Penjualan</a>
    <a href="{{ route('dashboard.product-part') }}"
        class="btn btn-{{ Request::is('dashboard/product-part') ? 'primary' : 'secondary' }}" wire:navigate
        wire:ignore.self>Produk
        Part</a>
</div>
