<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\DB;

// https://www.youtube.com/watch?v=CAi4WEKOT4A
trait ImportHelper
{
    protected float $benchmarkStartTime;

    protected int $benchmarkStartMemory;

    protected int $startRowCount;

    protected int $startQueries;

    protected function startBenchmark(string $table = 'categories'): void
    {
        $this->startRowCount = DB::table($table)->count();
        $this->benchmarkStartTime = microtime(true);
        $this->benchmarkStartMemory = memory_get_usage();
        DB::enableQueryLog();
        $this->startQueries = (int) DB::select("SHOW SESSION STATUS LIKE 'Questions'")[0]->Value;
    }

    protected function endBenchmark(string $table = 'categories'): void
    {
        $executionTime = microtime(true) - $this->benchmarkStartTime;
        $memoryUsage = round((memory_get_usage() - $this->benchmarkStartMemory) / 1024 / 1024, 2);
        $queriesCount = (int) DB::select("SHOW SESSION STATUS LIKE 'Questions'")[0]->Value - $this->startQueries - 1;
        $rowDiff = DB::table($table)->count() - $this->startRowCount;

        $formattedTime = match (true) {
            $executionTime >= 60 => sprintf('%dm %ds', floor($executionTime / 60), $executionTime % 60),
            $executionTime >= 1 => round($executionTime, 2) . 's',
            default => round($executionTime * 1000) . 'ms',
        };

        $this->newLine();
        $this->line(sprintf(
            '⚡ <bg=bright-blue;fg=black> TIME: %s </> <bg=bright-green;fg=black> MEM: %sMB </> <bg=bright-yellow;fg=black> SQL: %s </> <bg=bright-magenta;fg=black> ROWS: %s </>',
            $formattedTime,
            $memoryUsage,
            number_format($queriesCount),
            number_format($rowDiff)
        ));
        $this->newLine();
    }
}
