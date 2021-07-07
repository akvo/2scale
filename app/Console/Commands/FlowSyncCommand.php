<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\SyncController;
use App\Libraries\FlowApi;
use App\Libraries\AkvoAuth0 as Auth;
use App\Libraries\FlowAuth0;
use App\Sync;
use App\Form;
use App\Partnership;
use App\Datapoint;
use App\Answer;
use App\Question;

class FlowSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flow:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Akvo Flow data using Sync API';

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
        $auth = new Auth();
        $flowApi = new FlowApi();
        $flow = new FlowAuth0($auth);
        $syncs = new Sync();
        $forms = new Form();
        $partnerships = new Partnership();
        $datapoints = new Datapoint();
        $answers = new Answer();
        $questions = new Question();
        $api = new SyncController();
        $res = $api->syncData(
            $flowApi, $flow, $syncs, $forms,
            $partnerships, $datapoints, $answers, $questions,
        );

        Log::info('Start Flow Sync - '.now());
        foreach ($res as $key => $value) {
            $key += 1;
            Log::info($key.' | '.$value);
            $this->info($key.' | '.$value);
        }
        Log::info('End Flow Sync - '.now());
    }
}
