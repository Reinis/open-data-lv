<?php

namespace App\Console\Commands\Record;

use App\Console\Progress;
use App\Models\Record;
use App\Services\RecordService;
use Illuminate\Console\Command;
use RuntimeException;

class RecordImport extends Command
{
    use Progress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'record:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import records from data.gov.lv';

    /**
     * Execute the console command.
     */
    public function handle(RecordService $recordService): int
    {
        $limit = 1000;
        $progressBar = $this->getProgressBar();

        try {
            $max = ceil($recordService->count() / $limit);

            foreach ($progressBar->iterate($recordService->getRecords($limit), $max) as $batch) {
                $progressBar->setMessage(substr(last($batch)['date'], 0, 10));
                Record::upsert($batch, ['_id'], ['date', 'confirmed_cases', 'active_cases', 'cumulative_cases']);
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
