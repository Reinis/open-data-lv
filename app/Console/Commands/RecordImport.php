<?php

namespace App\Console\Commands;

use App\Models\Record;
use Generator;
use Illuminate\Console\Command;
use JsonException;
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
    public function handle(): int
    {
        $limit = 1000;

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

        try {
            foreach ($progressBar->iterate($this->getRecords($limit), ceil($this->getNumRecords() / $limit)) as $batch) {
                $progressBar->setMessage(substr(last($batch)['date'], 0, 10));
                Record::upsert($batch, ['_id'], ['date', 'confirmed_cases', 'active_cases', 'cumulative_cases']);
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * @throws JsonException
     */
    private function getRecords(int $limit): Generator
    {
        $columnMap = [
            '_id' => '_id',
            'date' => 'Datums',
            'territory_name' => 'AdministrativiTeritorialasVienibasNosaukums',
            'territory_code' => 'ATVK',
            'confirmed_cases' => 'ApstiprinataCOVID19infekcija',
            'active_cases' => 'AktivaCOVID19infekcija',
            'cumulative_cases' => '14DienuKumulativaSaslimstiba',
        ];

        $result = $this->fetchPage(null, $limit);

        while (count($result['result']['records']) > 0) {
            $batch = [];

            if (!$result['success']) {
                throw new RuntimeException("Failed to retrieve records");
            }

            foreach ($result['result']['records'] as $record) {
                $data = [];
                foreach ($columnMap as $column => $name) {
                    $data[$column] = $record[$name];
                }
                $data['confirmed_cases'] = (int)$data['confirmed_cases'];
                $data['active_cases'] = (int)$data['active_cases'];
                $data['cumulative_cases'] = (int)$data['cumulative_cases'];

                $batch[] = $data;
            }

            yield $batch;

            $result = $this->fetchPage($result['result']['_links']['next']);
        }
    }

    /**
     * @throws JsonException
     */
    private function fetchPage(?string $queryString = null, int $limit = 1): array
    {
        $baseName = 'https://data.gov.lv/dati/lv';

        if (null === $queryString) {
            $queryString = "/api/3/action/datastore_search";
            $queryString .= "?resource_id=492931dd-0012-46d7-b415-76fe0ec7c216";
            $queryString .= "&sort=_id";
            $queryString .= "&limit={$limit}";
        }

        $contents = file_get_contents($baseName . $queryString);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    private function getNumRecords(): int
    {
        $result = $this->fetchPage();

        return $result['result']['total'];
    }
}
