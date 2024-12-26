<ul class="menu-inner py-1">
    @foreach ($menus as $index => $menu)
        @if (isset($menu['header']) && $menu['header'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">{{ $menu['label'] }}</span>
            </li>
        @else
            <li class="menu-item {{ $menu['active'] ? 'active' : '' }} {{ $loop->last ? 'last-item' : '' }}"
                x-data="{ isOpen: {{ $menu['active'] ? 'true' : 'false' }} }" :class="{ 'open': isOpen }">
                <a href="{{ $menu['route'] ? route($menu['route']) : 'javascript:void(0);' }}"
                    class="menu-link {{ $menu['children'] ? 'menu-toggle' : '' }}"
                    @if ($menu['children']) @click="isOpen = !isOpen" @endif
                    @if ($menu['route']) wire:navigate @endif @click="isMenuExpanded = !isMenuExpanded">
                    <i class="menu-icon tf-icons {{ $menu['icon'] }}"></i>
                    <div class="text-truncate">{{ $menu['label'] }}</div>
                </a>
                @if (!empty($menu['children']))
                    <ul class="menu-sub" x-show="isOpen" x-collapse>
                        @foreach ($menu['children'] as $child)
                            <li class="menu-item {{ $child['active'] ? 'active' : '' }}">
                                <a href="{{ route($child['route']) }}" class="menu-link" wire:navigate
                                    @click="isMenuExpanded = !isMenuExpanded">
                                    <div class="text-truncate">{{ $child['label'] }}</div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endif
    @endforeach
</ul>
