<div>
    <x-loading target="" />

    <div class="card">
        <div class="card-header">
            @php
                $inOrOut = '';

                if ($check == 'out') {
                    $inOrOut = 'Check Out';
                } else {
                    $inOrOut = 'Check In';
                }
            @endphp
            DKS {{ $inOrOut }}
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12 mb-3">
                    <label for="kd_outlet" class="form-label">Kode Toko</label>
                    <input type="text" id="kd_outlet" class="form-control" value="{{ $toko->kd_outlet }}" disabled>
                </div>
                <div class="col-12 mb-3">
                    <label for="nm_outlet" class="form-label">Nama Toko</label>
                    <input type="text" id="nm_outlet" class="form-control" value="{{ $toko->nm_outlet }}" disabled>
                </div>

                <div class="col my-3">
                    <div id="map"></div>
                </div>

                <form action="{{ route('daftar-kehadiran-driver.store') }}" method="POST" onsubmit="return validateForm(event)">
                    @csrf
                    <input type="hidden" name="kode_toko" value="{{ $toko->kd_outlet }}">
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <input type="hidden" name="distance" id="distance">
                    <input type="hidden" name="katalog" id="katalog" value="{{ $katalog }}">

                    <div class="col-12 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <input type="text" class="form-control" id="status" name="status"
                            value="Lokasi tidak ditemukan." disabled>
                    </div>

                    <div class="col-12 mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" placeholder="Keterangan"></textarea>
                    </div>

                    <div class="col my-3">
                        <small>NB: Pastikan Anda berada di dalam radius Toko ketika melakukan check in / check
                            out.</small>
                    </div>

                    <div class="col-12 mb-3 d-flex justify-content-end">
                        <button type="submit" id="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            var tokoLatitude = {{ $toko->latitude }};
            var tokoLongitude = {{ $toko->longitude }};
            var tokoLatitude_2 = '';
            var tokoLongitude_2 = '';
            var radiusToko = 50;
            var userMarker;
            var userCircle;
            var hasErrorAlerted = false;
            var kd_outlet = `{{ $toko->kd_outlet }}`;

            var map = L.map('map', {
                center: [tokoLatitude, tokoLongitude],
                zoom: 13
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                zoom: 13,
            }).addTo(map);

            map.locate({
                setView: true,
                zoom: 13,
                enableHighAccuracy: true,
            });

            storeCircle = L.circle([tokoLatitude, tokoLongitude], {
                color: 'red',
                fillColor: 'red',
                fillOpacity: 0.2,
                radius: radiusToko
            }).addTo(map);

            if (kd_outlet == 'TQ') {
                tokoLatitude_2 = '-3.290902753103496';
                tokoLongitude_2 = '114.5975916206663';

                storeCircle_2 = L.circle([tokoLatitude_2, tokoLongitude_2], {
                    color: 'red',
                    fillColor: 'red',
                    fillOpacity: 0.2,
                    radius: radiusToko
                }).addTo(map);
            } else if (kd_outlet == '47') {
                tokoLatitude_2 = '-2.6765782478941156';
                tokoLongitude_2 = '111.6364410789898';

                storeCircle_2 = L.circle([tokoLatitude_2, tokoLongitude_2], {
                    color: 'red',
                    fillColor: 'red',
                    fillOpacity: 0.2,
                    radius: radiusToko
                }).addTo(map);
            }

            setInterval(function() {
                map.locate({
                    setView: true,
                    zoom: 13,
                    enableHighAccuracy: true,
                });
            }, 2000);

            function onLocationFound(e) {
                if (userMarker) {
                    map.removeLayer(userMarker);
                }

                if (userCircle) {
                    map.removeLayer(userCircle);
                }

                userMarker = L.marker(e.latlng).addTo(map)
                    .bindPopup("{{ Auth::user()->username }}").openPopup();

                userCircle = L.circle(e.latlng, 5).addTo(map);

                var userLatLng = userMarker.getLatLng();
                var userLat = userLatLng.lat;
                var userLng = userLatLng.lng;

                if (kd_outlet == 'TQ' || kd_outlet == '47') {
                    var storeLatLng = L.latLng(tokoLatitude, tokoLongitude);
                    var storeLatLng_2 = L.latLng(tokoLatitude_2, tokoLongitude_2);

                    var distance = userLatLng.distanceTo(storeLatLng);
                    var distance_2 = userLatLng.distanceTo(storeLatLng_2);

                    if (distance <= radiusToko || distance_2 <= radiusToko) {
                        document.getElementById('status').value = "Anda berada di dalam radius";
                    } else {
                        document.getElementById('status').value =
                            "Anda berada di luar radius toko. Pastikan Anda berada dalam radius " + radiusToko + " meter";
                    }
                } else {
                    var storeLatLng = L.latLng(tokoLatitude, tokoLongitude);

                    var distance = userLatLng.distanceTo(storeLatLng);

                    if (distance <= radiusToko) {
                        document.getElementById('status').value = "Anda berada di dalam radius";
                    } else {
                        document.getElementById('status').value =
                            "Anda berada di luar radius toko. Pastikan Anda berada dalam radius " + radiusToko + " meter";
                    }
                }

                hasErrorAlerted = false;
            }

            function onLocationError(e) {
                if (!hasErrorAlerted) {
                    alert(e.message);
                    hasErrorAlerted = true;
                }
            }

            function validateForm(event) {
                const check = "{{ $check }}";
                let inOrOut = "{{ $inOrOut }}"

                let confirmAction = confirm(`Apakah Anda yakin ingin melakukan scan ${inOrOut}?`)

                if (!confirmAction) {
                    return false;
                }

                var submitButton = document.getElementById('submit');
                submitButton.disabled = true;
                submitButton.innerHTML = "Loading...";

                map.locate({
                    setView: true,
                    zoom: 13,
                    enableHighAccuracy: true,
                });

                if (hasErrorAlerted) {
                    alert('Lokasi tidak ditemukan!');
                    submitButton.disabled = false;
                    submitButton.innerHTML = "Submit";
                    return false;
                }

                var userLatLng = userMarker.getLatLng();
                var userLat = userLatLng.lat;
                var userLng = userLatLng.lng;

                var storeLatLng = L.latLng(tokoLatitude, tokoLongitude);
                var distance = userLatLng.distanceTo(storeLatLng);

                document.getElementById('latitude').value = userLat;
                document.getElementById('longitude').value = userLng;
                document.getElementById('distance').value = distance;

                if (kd_outlet == 'TQ' || kd_outlet == '47') {
                    var storeLatLng_2 = L.latLng(tokoLatitude_2, tokoLongitude_2);

                    var distance_2 = userLatLng.distanceTo(storeLatLng_2);

                    console.log(distance)

                    if (distance < radiusToko || distance_2 < radiusToko) {
                        console.log('nice')
                    } else {
                        alert("Anda berada di luar radius toko. Pastikan Anda berada dalam radius " + radiusToko + " meter.");
                        submitButton.disabled = false;
                        submitButton.innerHTML = "Submit";
                        event.preventDefault();
                        return false;
                    }

                } else {
                    if (distance > radiusToko) {
                        alert("Anda berada di luar radius toko. Pastikan Anda berada dalam radius " + radiusToko + " meter.");
                        submitButton.disabled = false;
                        submitButton.innerHTML = "Submit";
                        event.preventDefault();
                        return false;
                    }
                }

                return true;
            }

            map.on('locationfound', onLocationFound);

            map.on('locationerror', onLocationError);
        </script>
    @endpush
</div>
