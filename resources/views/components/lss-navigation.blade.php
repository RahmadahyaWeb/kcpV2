<div class="col-12 mb-3">
    <div class="d-flex gap-2 py-4" style="overflow-x: auto; white-space: nowrap;">
        <a href="{{ route('report-marketing.lss.index') }}"
            class="btn btn-{{ Request::is('report-marketing/lss') ? 'primary' : 'secondary' }}" wire:navigate
            wire:ignore.self>
            Laporan LSS
        </a>
        <a href="{{ route('report-marketing.lss.detail') }}"
            class="btn btn-{{ Request::is('report-marketing/lss/detail') ? 'primary' : 'secondary' }}" wire:navigate
            wire:ignore.self>
            Detail LSS
        </a>
        <a href="{{ route('report-marketing.lss.generate') }}"
            class="btn btn-{{ Request::is('report-marketing/lss/generate') ? 'primary' : 'secondary' }}" wire:navigate
            wire:ignore.self>
            Generate LSS
        </a>
        <a href="{{ route('report-marketing.lss.inject') }}"
            class="btn btn-{{ Request::is('report-marketing/lss/inject') ? 'primary' : 'secondary' }}" wire:navigate
            wire:ignore.self>
            Inject COGS
        </a>
    </div>
</div>
