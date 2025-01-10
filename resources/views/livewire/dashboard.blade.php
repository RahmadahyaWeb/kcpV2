<div x-data="{ data: @entangle('data') }" x-init="$nextTick(() => initializeChart(data))">

    <div class="row mb-3">
        <div class="col-md-4 mb-3">
            <div class="card" style="height: 10rem">
                <div class="card-header">
                    Pencapaian Bulan {{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM') }}
                </div>
                <div class="card-body">
                    <span class="text-center d-block fs-1 fw-bold">
                        {{ $performance }} %
                    </span>
                </div>
            </div>
        </div>

        <x-total-invoice-card :amount="$total_invoice" />
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
                const ctx = document.getElementById('myChart');
                const labels = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];

                const chartData = {
                    labels: labels,
                    datasets: [
                        createDataset('Penjualan', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)', data
                            .arrPenjualan),
                        createDataset('Target', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)', data.arrTarget)
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
