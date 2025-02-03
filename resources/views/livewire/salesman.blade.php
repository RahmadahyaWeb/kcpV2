<div>
    <x-loading :target="$target" />

    <x-dashboard-navigation />

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label" for="periode">Periode</label>
            <input type="month" id="periode" class="form-control" wire:model.change="periode">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>NAMA SALES</th>
                                    <th>AMOUNT TOTAL (INVOICE)</th>
                                    <th>AMOUNT TOTAL (RETUR)</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $no = 1;
                                @endphp

                                @foreach ($salesmanData as $sales)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td class="text-wrap">{{ $sales->fullname }}</td>
                                        <td>{{ number_format($sales->total_amount, 0, ',', '.') }}</td>
                                        <td>{{ number_format($sales->total_retur, 0, ',', '.') }}</td>
                                        <td>{{ number_format($sales->total_amount - $sales->total_retur, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{
        dataSalesman: @entangle('data_salesman'),
    }" x-init="$nextTick(() => {
        initializeChart('salesman', dataSalesman, 'Total Invoice Terbentuk', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    })"
        x-effect="$watch('dataSalesman', () => {
        initializeChart('salesman', dataSalesman, 'Total Invoice Terbentuk', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
    })">

        <div class="row gap-3 mb-3">
            <div class="col-12">
                <div class="chartCard">
                    <div class="chartBox">
                        <div class="canvas">
                            <canvas id="salesman"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            // Fungsi untuk menginisialisasi chart
            function initializeChart(canvasId, data, label, bgColor, borderColor) {

                const ctx = document.getElementById(canvasId);

                // Clear the canvas content
                ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height);

                // Destroy the previous chart instance if it exists
                if (window[canvasId + 'Chart']) {
                    window[canvasId + 'Chart'].destroy();
                }

                const labels = data.labels;

                const chartData = {
                    labels: labels,
                    datasets: [
                        createDataset(label, bgColor, borderColor, data.amount),
                        createDataset('Retur Invoice', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)', data.retur)
                    ]
                };

                const chartOptions = {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                };

                // Create the new chart and store the chart instance in a global variable
                if (canvasId === 'salesman') {
                    window['salesmanChart'] = new Chart(ctx, {
                        type: 'line',
                        data: chartData,
                        options: chartOptions
                    });
                }
            }

            // Fungsi untuk membuat dataset
            function createDataset(label, backgroundColor, borderColor, data) {
                return {
                    label: label,
                    data: data,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: 1,
                    pointRadius: 8
                };
            }
        </script>
    @endpush
</div>
