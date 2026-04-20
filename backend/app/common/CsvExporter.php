<?php

namespace app\common;

use Webman\Http\Response;

class CsvExporter
{
    public static function export(string $filename, array $headers, iterable $rows): Response
    {
        $csvDir = runtime_path() . '/csv_export';
        if (!is_dir($csvDir)) {
            @mkdir($csvDir, 0755, true);
        }
        self::cleanOldFiles($csvDir);

        $tmpFile = tempnam($csvDir, 'csv_');
        $fp = fopen($tmpFile, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, array_map([self::class, 'escapeCell'], $headers));
        foreach ($rows as $row) {
            fputcsv($fp, array_map([self::class, 'escapeCell'], (array) $row));
        }
        fclose($fp);

        return (new Response())->withFile($tmpFile)->withHeaders([
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // 以 =+-@\t\r 开头的单元格在 Excel/WPS 中会被当作公式执行（CSV 注入）。
    // 统一在前缀加单引号阻断，参考 OWASP CSV Injection Prevention Cheat Sheet。
    private static function escapeCell($value): string
    {
        if ($value === null) {
            return '';
        }
        $str = (string) $value;
        if ($str === '') {
            return $str;
        }
        $first = $str[0];
        if ($first === '=' || $first === '+' || $first === '-' || $first === '@' || $first === "\t" || $first === "\r") {
            return "'" . $str;
        }
        return $str;
    }

    private static function cleanOldFiles(string $dir): void
    {
        $cutoff = time() - 3600;
        foreach (glob($dir . '/csv_*') as $old) {
            if (is_file($old) && filemtime($old) < $cutoff) {
                @unlink($old);
            }
        }
    }
}
