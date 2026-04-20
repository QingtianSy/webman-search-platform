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
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        return (new Response())->withFile($tmpFile)->withHeaders([
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
