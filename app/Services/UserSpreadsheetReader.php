<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

class UserSpreadsheetReader
{
    /**
     * @return list<list<string>>
     */
    public function rows(string $absolutePath): array
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($absolutePath),
            'xlsx' => $this->readOpenSpout(new XlsxReader, $absolutePath),
            'xls' => throw new RuntimeException('فقط فرمت xlsx پشتیبانی می‌شود؛ فایل را در اکسل با پسوند xlsx ذخیره کنید یا CSV خروجی بگیرید.'),
            default => throw new RuntimeException('فرمت فایل پشتیبانی نمی‌شود. از CSV یا XLSX استفاده کنید.'),
        };
    }

    /**
     * @return list<list<string>>
     */
    private function readCsv(string $absolutePath): array
    {
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException('خواندن فایل ممکن نشد.');
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        $firstLine = strtok($contents, "\r\n");
        if ($firstLine === false || $firstLine === '') {
            return [];
        }

        $delimiter = $this->detectDelimiter($firstLine);
        $stream = fopen('php://memory', 'r+b');

        if ($stream === false) {
            throw new RuntimeException('خواندن فایل ممکن نشد.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        $rows = [];

        while (($data = fgetcsv($stream, 0, $delimiter)) !== false) {
            $rows[] = array_map(fn ($cell): string => trim((string) ($cell ?? '')), $data);
        }

        fclose($stream);

        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t"];

        $best = ',';
        $bestCount = 0;

        foreach ($candidates as $delimiter) {
            $count = substr_count($line, $delimiter);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $delimiter;
            }
        }

        return $best;
    }

    /**
     * @return list<list<string>>
     */
    private function readOpenSpout(XlsxReader|CsvReader $reader, string $absolutePath): array
    {
        $reader->open($absolutePath);
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $this->rowToStrings($row);
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function rowToStrings(Row $row): array
    {
        $cells = [];

        foreach ($row->getCells() as $cell) {
            $cells[] = trim((string) $cell->getValue());
        }

        return $cells;
    }
}