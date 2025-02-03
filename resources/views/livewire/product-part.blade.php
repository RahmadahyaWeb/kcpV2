<div>

    <x-dashboard-navigation />

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label" for="periode">Periode</label>
            <input type="month" id="periode" class="form-control" wire:model.change="periode">
        </div>
    </div>

    <div x-data="{
        dataAop: @entangle('data_aop'),
    }" x-init="$nextTick(() => {
        initializeChart('product_aop', dataAop, 'Penjualan Produk AOP', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
    })"
        x-effect="$watch('dataAop', () => {
        initializeChart('product_aop', dataAop, 'Penjualan Produk AOP', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
    })">

        <div class="row gap-3 mb-3">
            <div class="col-12">
                <div class="chartCard">
                    <div class="chartBox">
                        <div class="canvas">
                            <canvas id="product_aop"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{
        dataNonAop: @entangle('data_non_aop'),
    }" x-init="$nextTick(() => {
        initializeChart('product_non_aop', dataNonAop, 'Penjualan Produk AOP', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    })"
        x-effect="$watch('dataNonAop', () => {
        initializeChart('product_non_aop', dataNonAop, 'Penjualan Produk AOP', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    })">

        <div class="row gap-3 mb-3">
            <div class="col-12">
                <div class="chartCard">
                    <div class="chartBox">
                        <div class="canvas">
                            <canvas id="product_non_aop"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            let productAopChart = null;
            let productNonAopChart = null;

            // Fungsi untuk menginisialisasi chart
            function initializeChart(canvasId, data, label, bgColor, borderColor) {
                console.log("Data for chart:", data); // Debugging data

                const ctx = document.getElementById(canvasId);

                // Hancurkan chart lama jika sudah ada
                if (canvasId === 'product_aop' && productAopChart) {
                    productAopChart.destroy();
                } else if (canvasId === 'product_non_aop' && productNonAopChart) {
                    productNonAopChart.destroy();
                }

                const labels = data.labels;

                const chartData = {
                    labels: labels,
                    datasets: [
                        createDataset(label, bgColor, borderColor, data.amount),
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

                // Buat chart baru dan simpan objek chart ke dalam variabel
                if (canvasId === 'product_aop') {
                    productAopChart = new Chart(ctx, {
                        type: 'bar',
                        data: chartData,
                        options: chartOptions
                    });
                } else if (canvasId === 'product_non_aop') {
                    productNonAopChart = new Chart(ctx, {
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
