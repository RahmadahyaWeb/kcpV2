<div class="col-md-4 mb-3">
    <div class="card" style="height: 10rem">
        <div class="card-header">
            Total Invoice {{ date('m-Y') }}
        </div>
        <div class="card-body">
            <span class="d-block fs-2 fw-bold">
                {{ number_format($amount, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>
