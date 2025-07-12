<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NativeConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'native:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Placeholder for legacy native:config command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Return empty JSON to satisfy NativePHP expectations
        echo json_encode([]);
        return 0;
    }
}