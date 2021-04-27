<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Record;
use Generator;
use JsonException;
use RuntimeException;

class APIRecordService implements RecordService
{
    /**
     * @var string[]
     */
    private array $columnMap;
    private string $baseName = 'https://data.gov.lv/dati/lv';
    private string $resourceId = "492931dd-0012-46d7-b415-76fe0ec7c216";

    public function __construct()
    {
        $this->columnMap = [
            '_id' => '_id',
            'date' => 'Datums',
            'territory_name' => 'AdministrativiTeritorialasVienibasNosaukums',
            'territory_code' => 'ATVK',
            'confirmed_cases' => 'ApstiprinataCOVID19infekcija',
            'active_cases' => 'AktivaCOVID19infekcija',
            'cumulative_cases' => '14DienuKumulativaSaslimstiba',
        ];
    }

    /**
     * @throws JsonException
     */
    public function getRecords(int $limit, int $offset = 0): Generator
    {
        $result = $this->fetchPage(null, $offset, $limit);

        while (count($result['result']['records']) > 0) {
            $batch = [];

            if (!$result['success']) {
                throw new RuntimeException("Failed to retrieve records");
            }

            foreach ($result['result']['records'] as $record) {
                $data = [];
                foreach ($this->columnMap as $column => $name) {
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
    private function fetchPage(?string $queryString = null, int $offset = 0, int $limit = 1): array
    {
        if (null === $queryString) {
            $queryString = "/api/3/action/datastore_search";
            $queryString .= "?resource_id={$this->resourceId}";
            $queryString .= "&sort=_id";
            $queryString .= "&offset={$offset}";
            $queryString .= "&limit={$limit}";
        }

        $contents = file_get_contents($this->baseName . $queryString);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public function count(): int
    {
        $result = $this->fetchPage();

        return $result['result']['total'];
    }

    public function getLastId(): int
    {
        return Record::max('_id') ?? 0;
    }
}
