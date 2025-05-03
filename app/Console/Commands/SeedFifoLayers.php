<?php

namespace App\Console\Commands;

use App\Livewire\Lss\GenerateLss;
use Illuminate\Console\Command;

class SeedFifoLayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fifo:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed FIFO layers berdasarkan stock awal dan pembelian';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bulan = 04;
        $tahun = 2025;

        $instance = app(GenerateLss::class);
        $instance->bulan = $bulan;
        $instance->tahun = $tahun;
        $instance->seedFifoLayers();

        $this->info("Pembelian FIFO berhasil diproses untuk periode $bulan-$tahun.");
    }
}
