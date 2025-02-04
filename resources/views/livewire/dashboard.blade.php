<div>
    <x-dashboard-navigation />

    <div class="mb-3" x-data="{
        dataAop: @entangle('data_aop'),
    }" x-init="$nextTick(() => {
        initializeChart('product_aop', dataAop, 'Penjualan Produk AOP', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    })"
        x-effect="$watch('dataAop', () => {
        initializeChart('product_aop', dataAop, 'Penjualan Produk AOP', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    })">

        <div class="row mb-3">
            <div class="col-md-4 mb-3">
                <div class="card" style="height: 10rem">
                    <div class="card-header">
                        Pencapaian AOP Bulan {{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM') }}
                    </div>
                    <div class="card-body">
                        <span class="d-block fs-2 fw-bold">
                            {{ $performance }} %
                        </span>
                    </div>
                </div>
            </div>

            <x-total-invoice-card :amount="$total_invoice" />

            <x-total-invoice-terbentuk-card :total="$total_invoice_terbentuk" />
        </div>

        <div class="chartCard">
            <div class="chartBox">
                <div class="canvas">
                    <canvas id="product_aop"></canvas>
                </div>
            </div>
        </div>
    </div>


    <div x-data="{
        dataNonAop: @entangle('data_non_aop'),
    }" x-init="$nextTick(() => {
        initializeChart('product_non_aop', dataNonAop, 'Penjualan Produk NON AOP', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    })"
        x-effect="$watch('dataNonAop', () => {
        initializeChart('product_non_aop', dataNonAop, 'Penjualan Produk NON AOP', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    })">
        <div class="chartCard">
            <div class="chartBox">
                <div class="canvas">
                    <canvas id="product_non_aop"></canvas>
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

                console.log(canvasId)

                // Clear the canvas content
                ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height);

                // Destroy the previous chart instance if it exists
                if (window[canvasId + 'Chart']) {
                    window[canvasId + 'Chart'].destroy();
                }

                const labels = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];

                const chartData = {
                    labels: labels,
                    datasets: [
                        createDataset(label, bgColor, borderColor, data.arrPenjualan),
                        createDataset('Target NON AOP', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)', data.arrTarget),
                    ]
                };

                console.log(chartData)

                const chartOptions = {
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
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
                if (canvasId === 'product_aop') {
                    window['product_aopChart'] = new Chart(ctx, {
                        type: 'bar',
                        data: chartData,
                        options: chartOptions
                    });
                } else if (canvasId === 'product_non_aop') {
                    window['product_non_aopChart'] = new Chart(ctx, {
                        type: 'bar',
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
                    borderWidth: 1
                };
            }
        </script>
    @endpush
</div>
