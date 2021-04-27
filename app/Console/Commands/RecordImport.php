<?php

namespace App\Console\Commands;

use App\Models\Record;
use App\Services\RecordService;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecordImport extends Command
{
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

    private function getProgressBar(): ProgressBar
    {
        $stdErr = $this->getOutput();

        if ($stdErr instanceof ConsoleOutputInterface) {
            $stdErr = $stdErr->getErrorOutput();
        }

        $format = 'Progress: [%bar%] %percent:3s%% %message:10s%';

        if ($stdErr->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $format .= ' %elapsed:6s%/%estimated:-6s% %memory:6s%';
        }

        $progressBar = new ProgressBar($stdErr);
        $progressBar->setFormat($format);
        $progressBar->setMessage('');

        return $progressBar;
    }
}
