@session('success')
    <div class="alert alert-primary" role="alert">
        {{ session('success') }}
    </div>
@endsession

@session('error')
    <div class="alert alert-danger" role="alert">
        {{ substr(session('error'), 0, 250) }}
    </div>
@endsession
