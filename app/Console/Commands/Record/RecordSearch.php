<?php

namespace App\Console\Commands\Record;

use App\Services\RecordService;
use Illuminate\Console\Command;

class RecordSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'record:search {term?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display latest data matching the term';

    /**
     * Execute the console command.
     */
    public function handle(RecordService $recordService): int
    {
        $headers = [
            '_id',
            'date',
            'territory_name',
            'territory_code',
            'confirmed_cases',
            'active_cases',
            'cumulative_cases',
        ];
        $result = $recordService->searchLatest($this->argument('term') ?? '')->toArray();

        for ($i = 0, $iMax = count($result); $i < $iMax; $i++) {
            $result[$i]['date'] = date('Y-m-d H:i:s', strtotime($result[$i]['date']));
        }

        $this->table($headers, $result);

        return 0;
    }
}
