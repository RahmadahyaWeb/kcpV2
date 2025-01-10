<div class="col-md-4">
    <div class="card">
        <div class="card-header">
            Total Invoice {{ date('m-Y') }}
        </div>
        <div class="card-body">
            <span class="d-block fs-2 fw-bold">
                {{ number_format($total_invoice, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>
