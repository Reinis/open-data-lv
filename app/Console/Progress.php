<?php

declare(strict_types=1);


namespace App\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait Progress
{
    private function getProgressBar(): ProgressBar
    {
        $stdErr = Command::getOutput();

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
