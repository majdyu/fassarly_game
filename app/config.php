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

const TOURNAMENT_STATUSES = [
    'started' => 'قيد اللعب',
    'completed' => 'مكتمل',
    'game_over' => 'خاسر',
    'abandoned' => 'منسحب',
];

const TOURNAMENT_PENALTY_SECONDS = 999999;

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

function status_label(?string $status): string
{
    if (!$status) {
        return 'غير ملعوب';
    }

    return TOURNAMENT_STATUSES[$status] ?? $status;
}

function admin_status_label(?string $status): string
{
    return match ($status) {
        'completed' => 'Succès',
        'game_over' => 'Game over',
        'abandoned' => 'Abandon',
        'started' => 'En cours',
        default => 'Non joué',
    };
}

function format_duration(?int $seconds): string
{
    if ($seconds === null) {
        return '-';
    }

    if ($seconds >= TOURNAMENT_PENALTY_SECONDS) {
        return 'Éliminé';
    }

    $minutes = intdiv(max(0, $seconds), 60);
    $remainingSeconds = max(0, $seconds) % 60;
    return "{$minutes} min {$remainingSeconds} s";
}

function random_code(int $bytes = 4): string
{
    return strtoupper(bin2hex(random_bytes($bytes)));
}
