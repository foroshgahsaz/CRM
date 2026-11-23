<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * بکاپ MySQL بدون نیاز به mysqldump (مناسب کانتینرهای بدون mysql-client).
 */
class PhpMysqlDatabaseExporter
{
    private const int CHUNK_ROWS = 400;

    public function exportToFile(string $targetPath, ?string $connection = null): void
    {
        $connection ??= (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('اکسپورت PHP فقط برای MySQL/MariaDB است.');
        }

        $handle = fopen($targetPath, 'wb');

        if ($handle === false) {
            throw new RuntimeException('نوشتن فایل بکاپ پایگاه ممکن نشد.');
        }

        try {
            fwrite($handle, "-- CRM PHP backup ".now()->toIso8601String()."\n");
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = collect(DB::connection($connection)->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']))
                ->map(function (object $row): string {
                    $values = (array) $row;

                    return (string) reset($values);
                })
                ->values()
                ->all();

            foreach ($tables as $table) {
                $this->exportTable($handle, $connection, $table);
            }

            fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }
    }

    private function exportTable(mixed $handle, string $connection, string $table): void
    {
        $create = DB::connection($connection)->selectOne('SHOW CREATE TABLE `'.$this->escapeIdentifier($table).'`');
        $createSql = (string) (collect((array) $create)->skip(1)->first() ?? '');

        if ($createSql === '') {
            return;
        }

        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, $createSql.";\n\n");

        $pdo = DB::connection($connection)->getPdo();
        $offset = 0;

        while (true) {
            $rows = DB::connection($connection)
                ->table($table)
                ->offset($offset)
                ->limit(self::CHUNK_ROWS)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $columns = array_keys((array) $row);
                $values = array_map(function (mixed $value) use ($pdo): string {
                    if ($value === null) {
                        return 'NULL';
                    }

                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }

                    if (is_int($value) || is_float($value)) {
                        return (string) $value;
                    }

                    return $pdo->quote((string) $value);
                }, array_values((array) $row));

                $line = 'INSERT INTO `'.$table.'` (`'.implode('`,`', $columns).'`) VALUES ('.implode(',', $values).");\n";
                fwrite($handle, $line);
            }

            fwrite($handle, "\n");
            $offset += self::CHUNK_ROWS;
        }
    }

    private function escapeIdentifier(string $table): string
    {
        return str_replace('`', '``', $table);
    }
}