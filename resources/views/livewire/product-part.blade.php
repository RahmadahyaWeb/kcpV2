<div x-data="{ data: @entangle('data_aop') }" x-init="$nextTick(() => initializeChart(data))">

    <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
        <a href="{{ route('dashboard') }}" class="btn btn-primary" wire:navigate>Penjualan</a>
        <a href="{{ route('dashboard.product-part') }}" class="btn btn-primary" wire:navigate>Produk</a>
    </div>

    <div class="chartCard">
        <div class="chartBox">
            <div class="canvas">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>

    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            // Fungsi untuk menginisialisasi chart
            function initializeChart(data) {
                console.log(data.amount)

                const ctx = document.getElementById('myChart');
                const labels = data.labels;

                const chartData = {
                    labels: labels,
                    datasets: [
                        createDataset('Penjualan Produk AOP', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)', data.amount),
                    ]
                };

                const chartOptions = {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top' // Legend di atas chart
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
