<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

function applicationLogger(): Logger
{
    static $logger = null;

    if ($logger instanceof Logger) {
        return $logger;
    }

    $logDirectory = getenv('LOG_PATH') ?: __DIR__ . '/../logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0770, true) && !is_dir($logDirectory)) {
        throw new RuntimeException("Unable to create log directory: {$logDirectory}");
    }

    $handler = new StreamHandler($logDirectory . '/app_log.txt', Level::Debug, true, 0660);
    $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true));

    $logger = new Logger('pulseroom');
    $logger->pushHandler($handler);
    $logger->pushProcessor('addRequestLogContext');

    return $logger;
}

function addRequestLogContext(LogRecord $record): LogRecord
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $urlPath = $requestUri === '' ? null : parse_url($requestUri, PHP_URL_PATH);
    $sessionData = isset($_SESSION['username']) ? redactLogData($_SESSION) : null;

    return $record->with(extra: array_merge($record->extra, [
        'client_ip' => getLogClientIp(),
        'action' => $record->context['action'] ?? null,
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'url' => is_string($urlPath) ? $urlPath : null,
        'query_params' => redactLogData($_GET ?? []),
        'body_params' => redactLogData($_POST ?? []),
        'session_data' => $sessionData,
        'response_code' => $record->context['response_code'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]));
}

function logEvent(
    Logger $logger,
    string $level,
    string $action,
    string $message = '',
    int $responseCode = 200
): void {
    $logger->log($level, $message, [
        'action' => $action,
        'response_code' => $responseCode,
    ]);
}

function redactLogData(mixed $data): mixed
{
    if (!is_array($data)) {
        return $data;
    }

    $sensitiveFields = [
        'password',
        'new_password',
        'conf_new_password',
        'csrf_token',
        'token',
        'conf_password',
        'phpsessid',
        'authorization',
        'cookie',
    ];

    foreach ($data as $key => $value) {
        if (is_string($key) && in_array(strtolower($key), $sensitiveFields, true)) {
            $data[$key] = '[FILTERED]';
            continue;
        }

        $data[$key] = redactLogData($value);
    }

    return $data;
}

function getLogClientIp(): ?string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return $_SERVER['REMOTE_ADDR'] ?? null;
}
