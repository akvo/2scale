<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Partnership;
use App\RsrProject;
use App\RsrResult;
use App\RsrIndicator;
use App\RsrPeriod;
use App\RsrDimension;
use App\RsrDimensionValue;
use App\RsrPeriodDimensionValue;
use App\RsrPeriodData;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\RsrSeedController;
use App\Http\Controllers\Api\ApiController;
use App\ViewRsrOverview;
use App\ViewRsrCountryData;
class RsrSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rsr:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Akvo RSR data using RSR API';

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
        $rsr = new RsrSeedController();
        $partnership = new Partnership();
        $project = new RsrProject();
        $result = new RsrResult();
        $indicator = new RsrIndicator();
        $period = new RsrPeriod();
        $dimension = new RsrDimension();
        $dimensionValue = new RsrDimensionValue();
        $periodDimensionValue = new RsrPeriodDimensionValue();
        $periodData = new RsrPeriodData();
        $request = new Request();

        $start = microtime(true);
        $this->info("Sync...");
        Log::info('Start RSR Sync - '.now());
        $res = $rsr->seedRsr(
            $partnership, $project, $result, $indicator,
            $period, $dimension, $dimensionValue,
            $periodDimensionValue, $periodData, $request
        );
        $time_elapsed_secs = microtime(true) - $start;
        $this->info($res);

        // * Clearing cache after sync
        \Artisan::call('cache:clear');
        $this->info("Cache cleared");
        // * Create cache
        $api = new ApiController();
        $rsrOverview = new ViewRsrOverview();
        $rsrCountryData= new ViewRsrCountryData();
        $api->getRsrUiiReport($request, $rsrOverview);
        $api->getRsrCountryData($request, $rsrCountryData);
        $this->info("Cache created");

        $this->info("Time : ".date("H:i:s",$time_elapsed_secs));
        Log::info('End RSR Sync - '.now());
    }
}
