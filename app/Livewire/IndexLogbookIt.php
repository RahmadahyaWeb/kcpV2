<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class IndexLogbookIt extends Component
{
    use WithFileUploads, WithPagination;

    public $target = "store_logbook, filter_tanggal_akhir";

    public $kegiatan, $tanggal, $jam, $foto_kegiatan, $filter_tanggal_mulai, $filter_tanggal_akhir;

    protected $rules = [
        'kegiatan' => 'required|string|max:255',
        'tanggal' => 'required|date',
        'jam' => 'required|date_format:H:i',
        'foto_kegiatan' => 'nullable|image|max:2048',
    ];

    public function store_logbook()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $path = null;
            if ($this->foto_kegiatan) {
                $path = $this->foto_kegiatan->store('logbook_foto', 'public');
            }

            DB::table('logbook_it')->insert([
                'kegiatan'          => $this->kegiatan,
                'tanggal'           => $this->tanggal,
                'jam'               => $this->jam,
                'foto_kegiatan'     => $path ?? '-',
                'created_at'        => $this->tanggal . ' ' . $this->jam,
                'crea_by'           => Auth::user()->username,
            ]);

            DB::commit();

            $this->reset(['kegiatan', 'tanggal', 'jam', 'foto_kegiatan']);

            session()->flash('success', 'Logbook berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan logbook: ' . $th->getMessage());
        }
    }

    public function render()
    {
        $items = DB::table('logbook_it')
            ->when($this->filter_tanggal_mulai && $this->filter_tanggal_akhir, function ($query) {
                return $query->whereBetween('tanggal', [$this->filter_tanggal_mulai, $this->filter_tanggal_akhir]);
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.index-logbook-it', compact(
            'items'
        ));
    }
}
