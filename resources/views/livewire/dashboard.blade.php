{{-- <div x-data="{
    data: @entangle('data') // Gunakan entangle jika menggunakan Livewire atau set secara langsung
}" x-init="$nextTick(function () {
    const ctx = document.getElementById('myChart');

    const labels = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    console.log(data);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Penjualan',
                    data: data.arrPenjualan,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)', // Warna untuk penjualan
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Target',
                    data: data.arrTarget,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)', // Warna untuk target
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
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
        }
    });
})">
    <div class="card">
        <div class="card-header">
            Penjualan Inc. Retur vs Target Bulanan
        </div>
        <div class="card-body">
            <canvas id="myChart"></canvas>
        </div>
    </div>

    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
</div> --}}
<div>
    dashboard
</div>
