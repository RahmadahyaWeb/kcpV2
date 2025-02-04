<div>
    <x-alert />
    <x-loading target="" />

    <div class="card">
        <div class="card-header">
            DKS Monitoring Harian
        </div>

        <div class="card-body">
            <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-dks">Scan</button>
            </div>

            <div class="table-responsive mb-3">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tgl. Kunjungan</th>
                            <th>Toko</th>
                            <th>Check In</th>
                            <th>Katalog</th>
                            <th>Check Out</th>
                            <th>Lama Kunjungan</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($items->isEmpty())
                            <tr>
                                <td colspan="8" class="text-center">No data</td>
                            </tr>
                        @else
                            @foreach ($items as $item)
                                @php
                                    $carbonDate = \Carbon\Carbon::parse($item->tgl_kunjungan);

                                    $dayInIndonesian = config('days.days.' . $carbonDate->format('l'));

                                    $formattedDate = $dayInIndonesian . ', ' . $carbonDate->format('d-m-Y');
                                @endphp
                                <tr>
                                    <td>{{ $item->user_sales }}</td>
                                    <td style="white-space: nowrap">{{ $formattedDate }}</td>
                                    <td style="white-space: nowrap">{{ $item->nama_toko }}</td>
                                    <td>{{ date('H:i:s', strtotime($item->waktu_cek_in)) }}</td>
                                    <td>
                                        @if ($item->katalog_at)
                                            {{ date('H:i:s', strtotime($item->katalog_at)) }}
                                        @else
                                            Belum scan katalog
                                        @endif
                                    </td>
                                    @if (in_array($item->kd_toko, $absen_toko))
                                        <td>
                                            @if ($item->waktu_cek_out)
                                                {{ date('H:i:s', strtotime($item->waktu_cek_out)) }}
                                            @else
                                                Belum check out
                                            @endif
                                        </td>
                                        <td>-</td>
                                        <td>Absen Toko</td>
                                    @else
                                        <td>
                                            @if ($item->waktu_cek_out)
                                                {{ date('H:i:s', strtotime($item->waktu_cek_out)) }}
                                            @else
                                                Belum check out
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->lama_kunjungan != null)
                                                {{ $item->lama_kunjungan }} menit
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $item->keterangan }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            document.addEventListener('livewire:init', () => {

                Livewire.on('open-dks-modal', (event) => {
                    $('#modal-dks').modal('show');
                });

                Livewire.on('hide-dks-modal', (event) => {
                    $('#modal-dks').modal('hide');
                });
            });
        </script>

        <script>
            function getRandomString(length) {
                const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let result = '';
                for (let i = 0; i < length; i++) {
                    result += characters.charAt(Math.floor(Math.random() * characters.length));
                }
                return result;
            }

            document.getElementById("start-button").addEventListener("click", () => {
                document.getElementById("start-button").setAttribute('disabled', 'true');

                document.getElementById("loading").classList.remove('d-none');
                document.getElementById("scan-text").classList.add('d-none');

                function getQrBoxSize() {
                    const width = window.innerWidth;
                    const height = window.innerHeight;
                    const qrBoxSize = Math.min(width, height) * 0.25;
                    return {
                        width: Math.max(qrBoxSize, 200),
                        height: Math.max(qrBoxSize, 200)
                    };
                }

                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        var cameraId = devices[0].id;
                        const config = {
                            aspectRatio: 1,
                            qrbox: getQrBoxSize(),
                        };

                        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                            let redirectUrl = '';
                            const url = new URL(decodedText);
                            const kd_toko = url.searchParams.get('kd_toko');
                            const katalog = url.searchParams.get('Katalog');

                            if (katalog) {
                                redirectUrl = `/dks/scan/${kd_toko}?katalog=${katalog}`;
                            } else {
                                redirectUrl = `/dks/scan/${kd_toko}`;
                            }

                            document.getElementById("loading").classList.remove('d-none');
                            document.getElementById("stop-button").classList.add('d-none');

                            html5QrCode.stop().then(() => {
                                document.getElementById("placeholder").classList.remove('d-none');

                                window.location.href = redirectUrl;

                                // if (kd_toko == 'TQ') {
                                //     $('#tqModal').modal('show');

                                //     document.getElementById('confirmSelection').onclick = () => {
                                //         const selectedOption = document.querySelector(
                                //             'input[name="Tq"]:checked').id;

                                //         $('#tqModal').modal('hide');

                                //         if (katalog) {
                                //             window.location.href =
                                //                 `/dks-scan/${selectedOption}?katalog=${katalog}`;
                                //         } else {
                                //             window.location.href =
                                //                 `/dks-scan/${selectedOption}`;
                                //         }

                                //     };
                                // } else {
                                // }
                            });
                        };

                        html5QrCode.start({

                            facingMode: {
                                exact: "environment"
                                // exact: "user"
                            }
                        }, config, qrCodeSuccessCallback).then(() => {
                            scanning = true;
                            document.getElementById("loading").classList.add('d-none');
                            document.getElementById("start-button").removeAttribute('disabled');
                            document.getElementById("start-button").classList.add('d-none');
                            document.getElementById("stop-button").classList.remove('d-none');
                            document.getElementById("placeholder").classList.add('d-none');
                        }).catch(err => {
                            alert('Error starting scanner');
                            location.reload()
                        });
                    } else {
                        document.getElementById("result").innerText = "No camera found.";
                    }
                }).catch(err => {
                    alert('Camera access denied or not available');
                    location.reload()
                });
            });

            document.getElementById("stop-button").addEventListener("click", () => {
                if (scanning) {
                    html5QrCode.stop().then(() => {
                        scanning = false;
                        document.getElementById("scan-text").classList.remove('d-none');
                        document.getElementById("start-button").classList.remove('d-none');
                        document.getElementById("stop-button").classList.add('d-none');
                        document.getElementById("placeholder").classList.remove('d-none');
                    }).catch(err => {
                        document.getElementById("result").innerText = `Error stopping scanner: ${err}`;
                    });
                }
            });
        </script>
    @endpush
</div>
