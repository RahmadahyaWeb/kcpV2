<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            Users
        </div>
        <div class="card-body">
            <div class="d-flex gap-2 mb-3 py-4" style="overflow-x: auto; white-space: nowrap;">
                <a href="{{ route('users.create') }}" class="btn btn-primary" wire:navigate>Create User</a>
            </div>

            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">Nama / Username</label>
                    <input type="text" class="form-control" wire:model.live.debounce.150ms="search"
                        placeholder="Cari berdasarkan nama user / username" wire:loading.attr="disabled">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td style="white-space: nowrap">{{ $user->name }}</td>
                                <td>{{ $user->username }}</td>
                                <td>
                                    {!! implode(',', $user->roles->pluck('name')->toArray()) !!}
                                </td>
                                <td>{{ $user->status }}</td>
                                <td class="text-end" style="white-space: nowrap">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning"
                                        wire:navigate>Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $users->links(data: ['scrollTo' => false]) }}
        </div>
    </div>
</div>
