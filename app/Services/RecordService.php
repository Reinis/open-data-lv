<?php

declare(strict_types=1);

namespace App\Services;

use Generator;

interface RecordService
{
    public function getRecords(int $limit): Generator;

    public function count(): int;
}
