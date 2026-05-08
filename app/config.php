<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const LEVEL_LABELS = [
    'easy' => 'سهل',
    'medium' => 'متوسط',
    'hard' => 'صعب',
];

function env_value(string $key, string $default): string
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function level_label(string $level): string
{
    return LEVEL_LABELS[$level] ?? $level;
}

function random_code(int $bytes = 4): string
{
    return strtoupper(bin2hex(random_bytes($bytes)));
}
