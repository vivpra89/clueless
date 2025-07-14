<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NativePhpIniCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'native:php-ini';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Placeholder for legacy native:php-ini command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Return the PHP INI settings from the NativeAppServiceProvider
        $settings = [
            'memory_limit' => '512M',
            'upload_max_filesize' => '50M',
            'post_max_size' => '50M',
        ];
        echo json_encode($settings);

        return 0;
    }
}
