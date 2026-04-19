<?php

namespace app\common;

use Webman\Http\Response;

class CsvExporter
{
    public static function export(string $filename, array $headers, iterable $rows): Response
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'csv_');
        $fp = fopen($tmpFile, 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        register_shutdown_function(function () use ($tmpFile) {
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        });

        return (new Response())->withFile($tmpFile)->withHeaders([
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
