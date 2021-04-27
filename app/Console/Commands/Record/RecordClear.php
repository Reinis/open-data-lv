<?php

namespace App\Console\Commands\Record;

use App\Models\Record;
use Illuminate\Console\Command;

class RecordClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'record:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the local database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Record::truncate();

        return 0;
    }
}
