<?php

namespace App\Console\Commands\Record;

use App\Console\Progress;
use App\Models\Record;
use App\Services\RecordService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecordImport extends Command implements SignalableCommandInterface
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

    private bool $beenSignaled = false;

    /**
     * Execute the console command.
     */
    public function handle(RecordService $recordService): int
    {
        $limit = 1000;
        $progressBar = $this->getProgressBar();

        try {
            $count = $recordService->count();
            $max = ceil($count / $limit);

            if ($count > 100 && $this->getOutput()->getVerbosity() !== OutputInterface::VERBOSITY_DEBUG) {
                $this->info("Unsetting event dispatcher for bulk import...");
                DB::connection()->unsetEventDispatcher();
            }

            $recordData = $recordService->getRecords($limit);

            foreach ($progressBar->iterate($recordData, $max) as $batch) {
                $progressBar->setMessage(substr(last($batch)['date'], 0, 10));
                Record::upsert($batch, ['_id'], ['date', 'confirmed_cases', 'active_cases', 'cumulative_cases']);

                if ($this->beenSignaled) {
                    $this->info("\nStopping...");
                    return 2;
                }
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $this->newLine();

        return 0;
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): void
    {
        $this->beenSignaled = true;
    }
}
