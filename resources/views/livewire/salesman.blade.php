<div>
    <x-loading :target="$target" />

    <x-dashboard-navigation />

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label" for="periode">Periode</label>
            <input type="month" id="periode" class="form-control" wire:model.change="periode">
        </div>
    </div>

    <div class="row mb-3 gap-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    AOP
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <!-- Tabel untuk Sales AOP -->
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Nama Area</th>
                                    <th class="text-nowrap">Sales AOP</th>
                                    <th class="text-nowrap">Target 2W</th>
                                    <th class="text-nowrap">Target 4W</th>
                                    <th class="text-nowrap">Total Target</th>
                                    <th class="text-nowrap">Invoice 2W</th>
                                    <th class="text-nowrap">Invoice 4W</th>
                                    <th class="text-nowrap">Total Invoice AOP</th>
                                    <th class="text-nowrap">Retur 2W</th>
                                    <th class="text-nowrap">Retur 4W</th>
                                    <th class="text-nowrap">Total Retur AOP</th>
                                    <th class="text-nowrap">Total AOP</th>
                                    <th class="text-nowrap">Pencapaian 2W</th>
                                    <th class="text-nowrap">Pencapaian 4W</th>
                                    <th class="text-nowrap">Total Pencapaian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_invoce_aop = 0;
                                @endphp

                                @foreach ($report as $area => $data)
                                    @php
                                        $total_invoce_aop += $data['total_astra'];
                                    @endphp

                                    <tr>
                                        <td class="text-nowrap">{{ $area }}</td>
                                        <td>
                                            @foreach ($data['salesman_astra'] as $sales)
                                                <span class="text-nowrap">{{ $sales }}</span><br>
                                            @endforeach
                                        </td>
                                        <td class="table-warning">
                                            {{ number_format($data['target_2w'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-warning">
                                            {{ number_format($data['target_4w'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-danger">
                                            {{ number_format($data['target_2w'] + $data['target_4w'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-warning">
                                            {{ number_format($data['total_2w_astra'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-warning">
                                            {{ number_format($data['total_4w_astra'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-danger">
                                            {{ number_format($data['total_inv_astra'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-warning">
                                            {{ number_format($data['retur_2w_aop'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-warning">
                                            {{ number_format($data['retur_4w_aop'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-danger">
                                            {{ number_format($data['total_retur_astra'], 0, ',', '.') }}
                                        </td>
                                        <td class="table-danger">
                                            {{ number_format($data['total_astra'], 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ number_format($data['persen_2w_aop'], 2) }}%
                                        </td>
                                        <td>
                                            {{ number_format($data['persen_4w_aop'], 2) }}%
                                        </td>
                                        <td>
                                            {{ number_format($data['persen_aop'], 2) }}%
                                        </td>
                                    </tr>
                                @endforeach

                                <tr>
                                    <td colspan="11">
                                        <strong>TOTAL</strong>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($total_invoce_aop, 0, ',', '.') }}</strong>
                                    </td>
                                    <td colspan="3">

                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    NON AOP
                </div>

                <div class="card-body">
                    <!-- Tabel untuk Sales Non AOP -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Area</th>
                                    <th>Sales NON AOP</th>
                                    <th>Target Area</th>
                                    <th>Total Invoice NON AOP</th>
                                    <th>Total Retur NON AOP</th>
                                    <th>Total NON AOP</th>
                                    <th>Pencapaian (Persen)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report as $area => $data)
                                    <!-- Hanya tampilkan jika ada salesman Non AOP -->
                                    <tr>
                                        <td class="text-nowrap">{{ $area }}</td>
                                        <td>
                                            @foreach ($data['salesman_non_astra'] as $sales)
                                                <span class="text-nowrap">{{ $sales }}</span><br>
                                            @endforeach
                                        </td>
                                        <td>{{ number_format($data['target_non_aop'], 0, ',', '.') }}</td>
                                        <td>
                                            {{ number_format($data['total_inv_non_astra'], 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ number_format($data['total_retur_non_astra'], 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ number_format($data['total_non_astra'], 0, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ $data['persen_non_aop'] }}%
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

    {{-- <div x-data="{
        dataSalesman: @entangle('data_salesman'),
    }" x-init="$nextTick(() => {
        initializeChart('salesman', dataSalesman, 'Total', 'rgba(54, 162, 235, 0.6)', 'rgba(54, 162, 235, 1)');
    })"
        x-effect="$watch('dataSalesman', () => {
        initializeChart('salesman', dataSalesman, 'Total', 'rgba(255, 99, 132, 0.6)', 'rgba(255, 99, 132, 1)');
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
    </div> --}}

    {{-- @push('script')
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
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
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
    @endpush --}}
</div>
