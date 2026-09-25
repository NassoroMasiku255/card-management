<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExcelImportService
{
    public function import(UploadedFile $file, Event $event): array
    {
        $extension = $file->getClientOriginalExtension();
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            $results['errors'][] = 'Invalid file format. Please upload CSV, XLS, or XLSX file.';
            return $results;
        }

        $rows = $this->parseFile($file, $extension);

        if (empty($rows)) {
            $results['errors'][] = 'No data found in the file.';
            return $results;
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                if (empty($row['full_name']) || empty($row['phone_number'])) {
                    $results['errors'][] = "Row {$rowNum}: Full name and phone number are required.";
                    $results['failed']++;
                    continue;
                }

                $existingGuest = Guest::where('event_id', $event->id)
                    ->where('phone_number', $row['phone_number'])
                    ->first();

                if ($existingGuest) {
                    $results['errors'][] = "Row {$rowNum}: Guest with phone {$row['phone_number']} already exists.";
                    $results['failed']++;
                    continue;
                }

                $guest = Guest::create([
                    'event_id' => $event->id,
                    'full_name' => trim($row['full_name']),
                    'phone_number' => trim($row['phone_number']),
                    'amount_contributed' => floatval($row['amount_contributed'] ?? 0),
                    'card_type' => in_array(strtolower($row['card_type'] ?? ''), ['double', 'couple']) ? 'double' : 'single',
                    'email' => trim($row['email'] ?? ''),
                    'table_number' => trim($row['table_number'] ?? ''),
                    'category' => trim($row['category'] ?? ''),
                    'notes' => trim($row['notes'] ?? ''),
                ]);

                Invitation::create([
                    'event_id' => $event->id,
                    'guest_id' => $guest->id,
                    'qr_code_data' => $guest->unique_id,
                ]);

                $results['success']++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Excel import failed: " . $e->getMessage());
            $results['errors'][] = 'Import failed: ' . $e->getMessage();
        }

        return $results;
    }

    private function parseFile(UploadedFile $file, string $extension): array
    {
        if ($extension === 'csv') {
            return $this->parseCsv($file);
        }

        return $this->parseXlsx($file);
    }

    private function parseCsv(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return [];
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers = array_map(function ($header) {
            return strtolower(trim(str_replace([' ', '-'], '_', $header)));
        }, $headers);

        $headerMap = $this->mapHeaders($headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (empty(array_filter($data))) continue;

            $row = [];
            foreach ($headerMap as $field => $index) {
                $row[$field] = $data[$index] ?? '';
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function parseXlsx(UploadedFile $file): array
    {
        $rows = [];
        $filePath = $file->getRealPath();

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return $this->parseCsv($file);
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml) {
            $xml = simplexml_load_string($sharedStringsXml);
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            $zip->close();
            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        $allRows = [];

        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $value = '';
                $type = (string)$cell['t'];

                if ($type === 's') {
                    $index = intval((string)$cell->v);
                    $value = $sharedStrings[$index] ?? '';
                } else {
                    $value = (string)$cell->v;
                }
                $rowData[] = $value;
            }
            $allRows[] = $rowData;
        }

        $zip->close();

        if (empty($allRows)) {
            return [];
        }

        $headers = array_map(function ($header) {
            return strtolower(trim(str_replace([' ', '-'], '_', $header)));
        }, $allRows[0]);

        $headerMap = $this->mapHeaders($headers);

        for ($i = 1; $i < count($allRows); $i++) {
            if (empty(array_filter($allRows[$i]))) continue;

            $row = [];
            foreach ($headerMap as $field => $index) {
                $row[$field] = $allRows[$i][$index] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function mapHeaders(array $headers): array
    {
        $map = [];
        $fieldMappings = [
            'full_name' => ['full_name', 'fullname', 'name', 'jina', 'guest_name', 'jina_kamili'],
            'phone_number' => ['phone_number', 'phone', 'simu', 'namba_ya_simu', 'mobile', 'tel'],
            'amount_contributed' => ['amount_contributed', 'amount', 'kiasi', 'contribution', 'mchango'],
            'card_type' => ['card_type', 'type', 'aina', 'card', 'aina_ya_kadi'],
            'email' => ['email', 'barua_pepe'],
            'table_number' => ['table_number', 'table', 'meza'],
            'category' => ['category', 'group', 'kundi', 'jamii'],
            'notes' => ['notes', 'note', 'maelezo', 'comment'],
        ];

        foreach ($fieldMappings as $field => $possibleNames) {
            foreach ($possibleNames as $name) {
                $index = array_search($name, $headers);
                if ($index !== false) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }
}
