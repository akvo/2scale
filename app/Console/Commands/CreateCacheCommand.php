<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\ViewRsrOverview;
use App\ViewRsrCountryData;

class CreateCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '2scale:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create cache needed for 2SCALE data platform based on latest data available in the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $request = new Request();

        $start = microtime(true);
        Log::info('Start RSR cache - '.now());

        // * Clearing cache after sync
        \Artisan::call('cache:clear');
        $this->info("Cache cleared");
        // * Create cache
        $this->info("Creating cache...");
        $api = new ApiController();
        $rsrOverview = new ViewRsrOverview();
        $rsrCountryData= new ViewRsrCountryData();
        $api->getRsrUiiReport($request, $rsrOverview);
        $api->getRsrCountryData($request, $rsrCountryData);

        $time_elapsed_secs = microtime(true) - $start;
        $this->info("Cache created");
        $this->info("Time : ".date("H:i:s",$time_elapsed_secs));
        Log::info('End RSR cache - '.now());
    }
}
