<?php

namespace App\Console\Commands\Record;

use App\Console\Progress;
use App\Models\Record;
use App\Services\RecordService;
use Illuminate\Console\Command;
use RuntimeException;

class RecordUpdate extends Command
{
    use Progress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'record:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update records from data.gov.lv';

    /**
     * Execute the console command.
     */
    public function handle(RecordService $recordService): int
    {
        $limit = 1000;
        $offset = $recordService->getLastId();
        $progressBar = $this->getProgressBar();

        try {
            $total = $recordService->count();

            if ($total <= $offset) {
                return 0;
            }

            $max = ceil(($total - $offset) / $limit);

            foreach ($progressBar->iterate($recordService->getRecords($limit, $offset), $max) as $batch) {
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
