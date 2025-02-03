<div x-data="{
    dataAop: @entangle('data_aop'),
    dataNonAop: @entangle('data_non_aop')
}" x-init="$nextTick(() => {
    initializeChart('product_aop', dataAop, 'Penjualan Produk AOP', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    initializeChart('product_non_aop', dataNonAop, 'Penjualan Produk Non-AOP', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
})">

    <x-dashboard-navigation />

    <div class="row gap-3">
        <div class="col-12">
            <div class="chartCard">
                <div class="chartBox">
                    <div class="canvas">
                        <canvas id="product_aop"></canvas>
                    </div>
                </div>
            </div>
        </div>

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

    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            // Fungsi untuk menginisialisasi chart
            function initializeChart(canvasId, data, label, bgColor, borderColor) {
                console.log(`${label}:`, data.amount);

                const ctx = document.getElementById(canvasId);
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

                new Chart(ctx, {
                    type: 'bar',
                    data: chartData,
                    options: chartOptions
                });
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
