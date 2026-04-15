<?php

/**
 * 路由引导占位层。
 *
 * 当前阶段：
 * - 统一通过 config/routes.php 加载路由
 *
 * 后续阶段：
 * - 根据 Webman 官方结构接入真实路由加载方式
 */
return require __DIR__ . '/../config/routes.php';
