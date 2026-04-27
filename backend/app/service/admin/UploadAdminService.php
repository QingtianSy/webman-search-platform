<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\repository\mysql\SystemConfigRepository;

/**
 * 通用文件上传服务（管理端）。
 * - 按 scene 分子目录：runtime/uploads/{scene}/yyyy/mm/<uuid>.<ext>
 * - 真实 MIME 用 finfo 读魔数校验；白名单 image/jpeg|png|gif|webp
 * - 最大尺寸读 system_configs.upload_max_size（字节），默认 5MB
 * - 返回相对 URL /uploads/{scene}/yyyy/mm/<uuid>.<ext>；nginx 静态托管
 */
class UploadAdminService
{
    private const ALLOWED_SCENES = ['announcement', 'doc', 'question'];
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    private const DEFAULT_MAX_SIZE = 5 * 1024 * 1024;

    /**
     * @param mixed $file webman UploadFile 实例（$request->file('file')）
     * @return array{url:string,size:int,mime:string}
     */
    public function upload($file, string $scene): array
    {
        $scene = strtolower(trim($scene));
        if (!in_array($scene, self::ALLOWED_SCENES, true)) {
            throw new BusinessException('不支持的上传场景', 40001);
        }
        if (!$file || !$file->isValid()) {
            throw new BusinessException('请选择要上传的文件', 40001);
        }

        $size = (int) $file->getSize();
        if ($size <= 0) {
            throw new BusinessException('文件为空', 40001);
        }
        $maxSize = $this->readMaxSize();
        if ($size > $maxSize) {
            $mb = number_format($maxSize / 1024 / 1024, 1);
            throw new BusinessException("文件不能超过 {$mb}MB", 40001);
        }

        $tmpPath = $file->getRealPath();
        if (!$tmpPath || !is_file($tmpPath)) {
            throw new BusinessException('临时文件不可读', 50001);
        }

        // 真实 MIME（不信任客户端 Content-Type）
        $mime = $this->detectMime($tmpPath);
        if (!isset(self::ALLOWED_MIMES[$mime])) {
            throw new BusinessException('仅支持 jpg/png/gif/webp 图片', 40001);
        }
        $ext = self::ALLOWED_MIMES[$mime];

        // 目录：runtime/uploads/{scene}/yyyy/mm/
        $now = date('Y/m');
        $relDir = 'uploads/' . $scene . '/' . $now;
        $baseDir = $this->baseDir();
        $absDir = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relDir);
        if (!is_dir($absDir) && !@mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            throw new BusinessException('上传目录不可写', 50001);
        }

        $name = $this->uuid() . '.' . $ext;
        $absPath = $absDir . DIRECTORY_SEPARATOR . $name;

        if (!$file->move($absPath)) {
            throw new BusinessException('文件保存失败', 50001);
        }

        $url = '/' . $relDir . '/' . $name;
        return [
            'url' => $url,
            'size' => $size,
            'mime' => $mime,
        ];
    }

    private function detectMime(string $path): string
    {
        if (!function_exists('finfo_open')) {
            return '';
        }
        $fi = @finfo_open(FILEINFO_MIME_TYPE);
        if (!$fi) {
            return '';
        }
        $mime = @finfo_file($fi, $path) ?: '';
        finfo_close($fi);
        return strtolower((string) $mime);
    }

    private function readMaxSize(): int
    {
        try {
            $row = (new SystemConfigRepository())->findByKey('upload_max_size');
            $val = isset($row['config_value']) ? (int) $row['config_value'] : 0;
            if ($val > 0) {
                return $val;
            }
        } catch (\Throwable $e) {
            // 静默，取默认
        }
        return self::DEFAULT_MAX_SIZE;
    }

    private function baseDir(): string
    {
        if (function_exists('runtime_path')) {
            return runtime_path();
        }
        if (defined('BASE_PATH')) {
            return BASE_PATH . DIRECTORY_SEPARATOR . 'runtime';
        }
        return getcwd() . DIRECTORY_SEPARATOR . 'runtime';
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
