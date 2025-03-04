<?php

namespace App\Console\Commands;

use App\Http\Controllers\SyncController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncIntransit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-intransit';

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
        try {
            $controller = new SyncController();
            $controller->sync_intransit();
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
        }
    }
}
