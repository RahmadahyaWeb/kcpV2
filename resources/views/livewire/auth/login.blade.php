<div>
    <span class="mb-1 fs-2 fw-bold">
        KCP APP
    </span>

    <p class="mb-6 text-muted">Please login to your account and get started!</p>

    <hr>

    <form class="mb-6" wire:submit.prevent="login">
        <div class="mb-6">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                name="username" placeholder="Enter your username" wire:model="username">

            @error('username')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>
        <div class="mb-6 ">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                aria-describedby="password" wire:model="password" />

            @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-6">
            <button class="btn btn-primary d-grid w-100" type="submit">Login</button>
        </div>
    </form>
</div>
