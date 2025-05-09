<?php

// app/Services/CsvProcessingService.php
namespace App\Services;

use App\Models\Product;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CsvProcessingService
{
    public function process(Upload $upload)
    {
        try {
            $file = Storage::path($upload->path);

            if (!file_exists($file)) {
                throw new \Exception("File not found: {$upload->path}");
            }

            $handle = fopen($file, 'r');
            if ($handle === false) {
                throw new \Exception("Unable to open file: {$upload->path}");
            }

            $header = fgetcsv($handle);
            if ($header === false) {
                throw new \Exception("Unable to read header row from file: {$upload->path}");
            }

            $columnMap = $this->getColumnMap($header);

            $totalRows = $this->countRows($file) - 1;
            $upload->update(['total_rows' => $totalRows, 'status' => 'processing']);

            $processedRows = 0;
            $batchSize = 100;
            $batch = [];

            while (($data = fgetcsv($handle)) !== false) {
                $rowData = [];
                foreach ($columnMap as $column => $index) {
                    if ($index !== null && isset($data[$index])) {
                        $rowData[$column] = $this->cleanNonUtf8($data[$index]);
                    } else {
                        $rowData[$column] = null;
                    }
                }

                if (empty($rowData['unique_key'])) {
                    continue;
                }

                $batch[] = $rowData;

                if (count($batch) >= $batchSize) {
                    $this->processBatch($batch);
                    $batch = [];
                    $processedRows += $batchSize;
                    $upload->updateStatus('processing', $processedRows);
                }
            }

            if (!empty($batch)) {
                $this->processBatch($batch);
                $processedRows += count($batch);
            }

            fclose($handle);

            $upload->updateStatus('completed', $totalRows);

            return true;
        } catch (\Exception $e) {
            Log::error('CSV Processing Error: ' . $e->getMessage(), [
                'upload_id' => $upload->id,
                'file' => $upload->path,
                'trace' => $e->getTraceAsString()
            ]);

            $upload->updateStatus('failed', $upload->processed_rows);

            return false;
        }
    }

    private function processBatch($batch)
    {
        foreach ($batch as $rowData) {
            $uniqueKey = $rowData['unique_key'];

            Product::updateOrCreate(
                ['unique_key' => $uniqueKey],
                $rowData
            );
        }
    }

    private function cleanNonUtf8($string)
    {
        if ($string === null) return null;

        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }


    private function getColumnMap($header)
    {
        $expectedColumns = [
            'unique_key' => 'UNIQUE_KEY',
            'product_title' => 'PRODUCT_TITLE',
            'description' => 'PRODUCT_DESCRIPTION',
            'style' => 'STYLE#',
            'sanmar_mainframe_color' => 'SANMAR_MAINFRAME_COLOR',
            'size' => 'SIZE',
            'color' => 'COLOR_NAME',
            'piece_price' => 'PIECE_PRICE'
        ];

        $columnMap = [];

        foreach ($expectedColumns as $column => $possibleName) {
            $columnMap[$column] = null;
            $index = array_search($possibleName, $header);
            if ($index !== false) {
                $columnMap[$column] = $index;
            }
        }

        return $columnMap;
    }

    private function countRows($file)
    {
        $count = 0;
        $handle = fopen($file, 'r');

        while (fgetcsv($handle) !== false) {
            $count++;
        }

        fclose($handle);
        return $count;
    }
}
