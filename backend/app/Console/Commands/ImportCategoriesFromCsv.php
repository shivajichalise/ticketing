<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Traits\ImportHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDOException;
use PDOStatement;

final class ImportCategoriesFromCsv extends Command
{
    use ImportHelper;

    protected $signature = 'app:import-categories-from-csv {path : The full path to the CSV file}';

    protected $description = 'Import categories from a CSV file.';

    public function handle(): int
    {
        $filePath = $this->argument('path');
        $this->info("Importing categories from: {$filePath}");

        if (! is_readable($filePath)) {
            $this->error("File not found or unreadable: {$filePath}");

            return 1;
        }

        $this->startBenchmark('categories');

        $now = Carbon::now()->toDateTimeString();
        $chunks = [];

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Failed to open file: {$filePath}");

            return 1;
        }

        try {
            fgets($handle); // skip header
            $stmt = $this->prepareChunkedStatement(500);

            while (($line = fgetcsv($handle)) !== false) {
                if (count($line) < 4) {
                    $this->warn('Skipping incomplete row: ' . implode(',', $line));

                    continue;
                }

                $chunks[] = $line[0]; // name
                $chunks[] = $line[1]; // slug
                $chunks[] = $line[2] === '' ? null : (int) $line[2]; // parent_id
                $chunks[] = $line[3] === '' ? null : $line[3]; // path
                $chunks[] = $now; // created_at
                $chunks[] = $now; // updated_at

                if (count($chunks) === 500 * 6) {
                    $this->executeChunk($stmt, $chunks);
                    $chunks = [];
                }
            }

            if (! empty($chunks)) {
                $remainingRows = (int) (count($chunks) / 6);
                $stmt = $this->prepareChunkedStatement($remainingRows);
                $this->executeChunk($stmt, $chunks);
            }
        } finally {
            fclose($handle);
        }

        $this->endBenchmark('categories');

        return 0;
    }

    private function prepareChunkedStatement(int $chunkSize): PDOStatement
    {
        $rowPlaceholders = '(?, ?, ?, ?, ?, ?)';
        $placeholders = implode(',', array_fill(0, $chunkSize, $rowPlaceholders));

        return DB::connection()->getPdo()->prepare("
            INSERT INTO categories (name, slug, parent_id, path, created_at, updated_at)
            VALUES {$placeholders}
        ");
    }

    private function executeChunk(PDOStatement $stmt, array $chunks): void
    {
        try {
            $stmt->execute($chunks);
        } catch (PDOException $e) {
            $this->error('Failed to execute chunk: ' . $e->getMessage());
        }
    }
}
