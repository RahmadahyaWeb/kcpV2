<div>
    <x-dashboard-navigation />

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label" for="periode">Periode</label>
            <input type="month" id="periode" class="form-control" wire:model.change="periode">
        </div>
    </div>

    <div class="row gap-3">
        <div class="col-12">
            <div x-data="{
                kelompok2w: @entangle('kelompok_2w'),
            }" x-init="$nextTick(() => {
                initializeChart('kelompok_2w', kelompok2w, 'Penjualan Produk 2W', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            })"
                x-effect="$watch('kelompok2w', () => {
                initializeChart('kelompok_2w', kelompok2w, 'Penjualan Produk 2W', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            })">

                <div class="row gap-3 mb-3">
                    <div class="col-12">
                        <div class="chartCard">
                            <div class="chartBox">
                                <div class="canvas">
                                    <canvas id="kelompok_2w"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div x-data="{
                kelompok4w: @entangle('kelompok_4w'),
            }" x-init="$nextTick(() => {
                initializeChart('kelompok_4w', kelompok4w, 'Penjualan Produk 4W', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            })"
                x-effect="$watch('kelompok4w', () => {
                initializeChart('kelompok_4w', kelompok4w, 'Penjualan Produk 4W', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            })">

                <div class="row gap-3 mb-3">
                    <div class="col-12">
                        <div class="chartCard">
                            <div class="chartBox">
                                <div class="canvas">
                                    <canvas id="kelompok_4w"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div x-data="{
                kelompokNonAop: @entangle('non_aop'),
            }" x-init="$nextTick(() => {
                initializeChart('non_aop', kelompokNonAop, 'Penjualan Produk NON AOP', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            })"
                x-effect="$watch('kelompokNonAop', () => {
                initializeChart('non_aop', kelompokNonAop, 'Penjualan Produk NON AOP', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
            })">

                <div class="row gap-3 mb-3">
                    <div class="col-12">
                        <div class="chartCard">
                            <div class="chartBox">
                                <div class="canvas">
                                    <canvas id="non_aop"></canvas>
                                </div>
                            </div>
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
                if (canvasId === 'kelompok_2w') {
                    window['kelompok_2wChart'] = new Chart(ctx, {
                        type: 'bar',
                        data: chartData,
                        options: chartOptions
                    });
                } else if (canvasId === 'kelompok_4w') {
                    window['kelompok_4wChart'] = new Chart(ctx, {
                        type: 'bar',
                        data: chartData,
                        options: chartOptions
                    });
                } else if (canvasId === 'non_aop') {
                    window['non_aopChart'] = new Chart(ctx, {
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
                    borderWidth: 1,
                };
            }
        </script>
    @endpush
</div>
