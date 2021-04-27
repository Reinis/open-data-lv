<?php

declare(strict_types=1);

namespace App\Services;

use Generator;

interface RecordService
{
    public function getRecords(int $limit, int $offset = 0): Generator;

    public function count(): int;

    public function getLastId(): int;
}
