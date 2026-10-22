<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestPayment;
use App\Models\Invitation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExcelImportService
{
    public function import(UploadedFile $file, Event $event): array
    {
        $extension = $file->getClientOriginalExtension();
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        if (! in_array($extension, ['csv', 'xlsx', 'xls'])) {
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
                $fullName = trim($row['full_name'] ?? '');
                $phoneNumber = trim($row['phone_number'] ?? '');

                if ($fullName === '' || $phoneNumber === '') {
                    $missing = array_filter([
                        $fullName === '' ? 'Full name' : null,
                        $phoneNumber === '' ? 'Phone number' : null,
                    ]);

                    $results['errors'][] = "Row {$rowNum}"
                        .($fullName !== '' ? " ({$fullName})" : '')
                        .': '.implode(' and ', $missing).' '.(\count($missing) > 1 ? 'are' : 'is').' missing.';
                    $results['failed']++;

                    continue;
                }

                $existingGuest = Guest::where('event_id', $event->id)
                    ->where('phone_number', $phoneNumber)
                    ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($fullName)])
                    ->first();

                if ($existingGuest) {
                    $results['errors'][] = "Row {$rowNum}: Guest '{$fullName}' with phone {$phoneNumber} already exists.";
                    $results['failed']++;

                    continue;
                }

                $paidAmount = $this->parseAmount($row['amount_paid'] ?? 0);

                $guest = Guest::create([
                    'event_id' => $event->id,
                    'full_name' => $fullName,
                    'phone_number' => $phoneNumber,
                    'amount_contributed' => $this->parseAmount($row['amount_contributed'] ?? 0),
                    'paid_amount' => $paidAmount,
                    'last_payment_at' => $paidAmount > 0 ? now() : null,
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

                if ($paidAmount > 0) {
                    GuestPayment::create([
                        'event_id' => $event->id,
                        'guest_id' => $guest->id,
                        'user_id' => Auth::id(),
                        'amount' => $paidAmount,
                        'method' => 'import',
                        'notes' => 'Opening balance from imported guest list.',
                        'paid_at' => now()->toDateString(),
                    ]);
                }

                $results['success']++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Excel import failed: '.$e->getMessage());
            $results['errors'][] = 'Import failed: '.$e->getMessage();
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

        if (! $handle) {
            return [];
        }

        $delimiter = $this->detectDelimiter($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader($header), $headers);

        $headerMap = $this->mapHeaders($headers);

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty(array_filter($data))) {
                continue;
            }

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

        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            return $this->parseCsv($file);
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml) {
            $xml = simplexml_load_string($sharedStringsXml);
            foreach ($xml->si as $si) {
                $sharedStrings[] = $this->readSharedString($si);
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (! $sheetXml) {
            $zip->close();

            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        $allRows = [];

        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $nextIndex = 0;

            foreach ($row->c as $cell) {
                $type = (string) $cell['t'];
                $cellIndex = $this->columnIndex((string) $cell['r']) ?? $nextIndex;

                if ($type === 's') {
                    $index = intval((string) $cell->v);
                    $value = $sharedStrings[$index] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                } elseif ($type === 'str') {
                    $value = (string) $cell->v;
                } else {
                    $value = (string) $cell->v;
                }

                $rowData[$cellIndex] = $value;
                $nextIndex = $cellIndex + 1;
            }

            if (! empty($rowData)) {
                $maxIndex = max(array_keys($rowData));
                $ordered = [];
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $ordered[$i] = $rowData[$i] ?? '';
                }
                $allRows[] = $ordered;
            }
        }

        $zip->close();

        if (empty($allRows)) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader($header), $allRows[0]);

        $headerMap = $this->mapHeaders($headers);

        for ($i = 1; $i < count($allRows); $i++) {
            if (empty(array_filter($allRows[$i]))) {
                continue;
            }

            $row = [];
            foreach ($headerMap as $field => $index) {
                $row[$field] = $allRows[$i][$index] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function readSharedString(\SimpleXMLElement $si): string
    {
        if (isset($si->t)) {
            return (string) $si->t;
        }

        $value = '';
        foreach ($si->r as $run) {
            $value .= (string) $run->t;
        }

        return $value;
    }

    /**
     * A1 -> 0, B1 -> 1, AA1 -> 26.
     */
    private function columnIndex(string $cellReference): ?int
    {
        if (! preg_match('/^([A-Z]+)/i', $cellReference, $matches)) {
            return null;
        }

        $index = 0;
        foreach (str_split(strtoupper($matches[1])) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function detectDelimiter($handle): string
    {
        $position = ftell($handle);
        $line = fgets($handle) ?: '';
        fseek($handle, $position);

        $candidates = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($candidates);

        return array_key_first($candidates) ?: ',';
    }

    private function normalizeHeader(?string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
        $header = str_replace("\xC2\xA0", ' ', (string) $header);

        return strtolower(trim(str_replace([' ', '-'], '_', $header)));
    }

    private function parseAmount($value): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    }

    private function mapHeaders(array $headers): array
    {
        $map = [];
        $fieldMappings = [
            'full_name' => ['full_name', 'fullname', 'name', 'jina', 'guest_name', 'jina_kamili'],
            'phone_number' => ['phone_number', 'phone', 'simu', 'namba_ya_simu', 'mobile', 'tel'],
            'amount_contributed' => ['amount_contributed', 'pledged_amount', 'pledge', 'amount', 'kiasi', 'contribution', 'mchango'],
            'amount_paid' => ['amount_paid', 'paid_amount', 'paid', 'kiasi_kilicholipwa', 'malipo', 'amount_paid_tzs'],
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
