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
        if ($tmpFile === false) {
            throw new \RuntimeException('CSV 导出失败：无法创建临时文件');
        }
        $fp = fopen($tmpFile, 'w');
        if ($fp === false) {
            @unlink($tmpFile);
            throw new \RuntimeException('CSV 导出失败：无法打开临时文件');
        }
        // try-finally 保证 fwrite/fputcsv 中途抛异常时也能关闭 fd，并删除半成品 tmp，避免 fd 泄漏 + 垃圾文件堆积。
        try {
            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, array_map([self::class, 'escapeCell'], $headers));
            foreach ($rows as $row) {
                fputcsv($fp, array_map([self::class, 'escapeCell'], (array) $row));
            }
        } catch (\Throwable $e) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            @unlink($tmpFile);
            throw $e;
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
