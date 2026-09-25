<?php

/**
 * Escape output safely for HTML.
 */
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Redirect to a URL relative to BASE_URL.
 */
function redirect(string $path): never
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}


/**
 * Format a price for display.
 */
function formatPrice(float|int $price): string
{
    return number_format($price, 2);
}


/**
 * Format a date for display.
 */
function formatDate(string $date): string
{
    return date('M d, Y', strtotime($date));
}


/**
 * Format a date and time for display.
 */
function formatDateTime(string $datetime): string
{
    return date('M d, Y h:i A', strtotime($datetime));
}


/**
 * Return a shortened version of text.
 */
function truncateText(string $text, int $length = 100): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . '...';
}


/**
 * Generate the user's initials.
 */
function getInitials(string $name): string
{
    $name = trim($name);

    if ($name === '') {
        return '?';
    }

    $parts = preg_split('/\s+/', $name);

    if (count($parts) === 1) {
        return strtoupper(mb_substr($parts[0], 0, 1));
    }

    return strtoupper(
        mb_substr($parts[0], 0, 1) .
        mb_substr($parts[count($parts) - 1], 0, 1)
    );
}


/**
 * Check whether a value is a valid positive integer ID.
 */
function isValidId(mixed $id): bool
{
    return filter_var(
        $id,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) !== false;
}


/**
 * Get a safe value from an array.
 */
function old(array $data, string $key, string $default = ''): string
{
    return e($data[$key] ?? $default);
}