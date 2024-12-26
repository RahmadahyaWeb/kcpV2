@session('success')
    <div class="alert alert-primary" role="alert">
        {{ session('success') }}
    </div>
@endsession

@session('error')
    <div class="alert alert-danger" role="alert">
        {{ session('error') }}
    </div>
@endsession
