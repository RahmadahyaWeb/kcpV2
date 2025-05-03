<?php

namespace App\Console\Commands;

use App\Livewire\Lss\GenerateLss;
use Illuminate\Console\Command;

class ProsesPenjualanFifo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:proses-penjualan-fifo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $instance->prosesPenjualanFifo();

        $this->info("Penjualan FIFO berhasil diproses untuk periode $bulan-$tahun.");
    }
}
