<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\InvoiceController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendInvoiceToBosnet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-invoice-to-bosnet';

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
            $controller = new InvoiceController();
            $controller->sendToBosnet();
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
        }
    }
}
