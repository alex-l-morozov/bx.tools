<?php

namespace Campus\Services\Tools\Logger;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Psr\Log\LoggerInterface;
use Monolog\Level;

class SystemLogger
{
    private static array $loggers = [];

    // Настройки по умолчанию
    private const DEFAULT_LOG_RETENTION_DAYS = 10;
    private const DEFAULT_LOG_MAX_FILES = 10;
    private const DEFAULT_LOG_LEVEL = Level::Info;

    /**
     * Создает или возвращает существующий логгер
     */
    public static function create(
        string $name = 'campus-services',
        string $logDir = null,
        int $retentionDays = self::DEFAULT_LOG_RETENTION_DAYS,
        int $maxFiles = self::DEFAULT_LOG_MAX_FILES,
        Level $logLevel = self::DEFAULT_LOG_LEVEL
    ): LoggerInterface {
        $key = md5($name . ($logDir ?? '') . $retentionDays . $maxFiles . $logLevel->value);

        if (isset(self::$loggers[$key])) {
            return self::$loggers[$key];
        }

        $logger = new Logger($name);

        // Определяем базовый путь к логам
        if ($logDir === null) {
            $logDir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/' . str_replace('-', '_', $name);
        }

        // Создаем директорию если не существует
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Основной лог-файл с ротацией по дням
        $mainLogPath = $logDir . '/' . $name . '.log';
        $mainHandler = new RotatingFileHandler(
            $mainLogPath,
            $maxFiles,
            $logLevel
        );

        // Отдельный файл для ошибок
        $errorLogPath = $logDir . '/' . $name . '-errors.log';
        $errorHandler = new RotatingFileHandler(
            $errorLogPath,
            $maxFiles,
            Level::Error
        );

        // Форматтер для основных логов
        $mainFormatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );

        // Форматтер для ошибок (более подробный)
        $errorFormatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message%\nContext: %context%\nExtra: %extra%\n" . str_repeat('-', 80) . "\n",
            'Y-m-d H:i:s'
        );

        $mainHandler->setFormatter($mainFormatter);
        $errorHandler->setFormatter($errorFormatter);

        // Добавляем процессоры для дополнительной информации
        $logger->pushProcessor(new ProcessIdProcessor());

        // Проверяем, существует ли MemoryUsageProcessor
        if (class_exists(MemoryUsageProcessor::class)) {
            $logger->pushProcessor(new MemoryUsageProcessor());
        } else {
            // Добавляем кастомный процессор для памяти если класс не существует
            $logger->pushProcessor(function ($record) {
                $record['extra']['memory_usage'] = self::formatBytes(memory_get_usage(true));
                $record['extra']['memory_peak'] = self::formatBytes(memory_get_peak_usage(true));
                return $record;
            });
        }

        // Добавляем обработчики
        $logger->pushHandler($mainHandler);
        $logger->pushHandler($errorHandler);

        self::$loggers[$key] = $logger;

        return $logger;
    }

    /**
     * Создает логгер для планировщика задач
     */
    public static function createSchedulerLogger(): LoggerInterface
    {
        return self::create(
            'scheduler',
            $_SERVER['DOCUMENT_ROOT'] . '/local/logs/cron',
            10,
            10,
            Level::Info
        );
    }

    /**
     * Создает логгер для задач
     */
    public static function createTaskLogger(string $taskName): LoggerInterface
    {
        return self::create(
            'task-' . $taskName,
            $_SERVER['DOCUMENT_ROOT'] . '/local/logs/tasks',
            10,
            10,
            Level::Info
        );
    }


    /**
     * Создает логгер для API запросов
     */
    public static function createApiLogger(): LoggerInterface
    {
        return self::create(
            'api-requests',
            $_SERVER['DOCUMENT_ROOT'] . '/local/logs/api',
            30,
            15,
            Level::Debug
        );
    }

    /**
     * Создает логгер для интеграций
     */
    public static function createIntegrationLogger(string $integration): LoggerInterface
    {
        return self::create(
            'integration-' . $integration,
            $_SERVER['DOCUMENT_ROOT'] . '/local/logs/integrations',
            15,
            10,
            Level::Info
        );
    }

    /**
     * Создает логгер для ошибок
     */
    public static function createErrorLogger(): LoggerInterface
    {
        return self::create(
            'errors',
            $_SERVER['DOCUMENT_ROOT'] . '/local/logs/errors',
            30,
            20,
            Level::Warning
        );
    }

    /**
     * Форматирует размер памяти в человекочитаемый вид
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Очищает старые лог-файлы в указанной директории
     */
    public static function cleanOldLogFiles(string $logDir, int $retentionDays): int
    {
        if (!is_dir($logDir)) {
            return 0;
        }

        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        $files = glob($logDir . '/*.log*');
        $deletedCount = 0;

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Получает информацию о лог-файлах в указанной директории
     */
    public static function getLogFilesInfo(string $logDir): array
    {
        if (!is_dir($logDir)) {
            return [];
        }

        $files = glob($logDir . '/*.log*');
        $info = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $info[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'size_human' => self::formatBytes(filesize($file)),
                    'created' => date('Y-m-d H:i:s', filectime($file)),
                    'modified' => date('Y-m-d H:i:s', filemtime($file)),
                    'age_days' => floor((time() - filemtime($file)) / (24 * 60 * 60))
                ];
            }
        }

        // Сортируем по дате изменения (новые сначала)
        usort($info, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });

        return $info;
    }

    /**
     * Очищает все кэшированные логгеры
     */
    public static function clearCache(): void
    {
        self::$loggers = [];
    }

    /**
     * Логирует выполнение функции с замером времени и памяти
     */
    public static function logExecution(
        LoggerInterface $logger,
        string $operationName,
        callable $callback,
        array $context = []
    ) {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $logger->info("Starting operation: {$operationName}", array_merge($context, [
            'memory_before' => self::formatBytes($startMemory)
        ]));

        try {
            $result = $callback();

            $executionTime = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage(true) - $startMemory;

            $logger->info("Operation completed: {$operationName}", array_merge($context, [
                'execution_time_ms' => round($executionTime * 1000, 2),
                'memory_used' => self::formatBytes($memoryUsed),
                'success' => true
            ]));

            return $result;
        } catch (\Throwable $e) {
            $executionTime = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage(true) - $startMemory;

            $logger->error("Operation failed: {$operationName}", array_merge($context, [
                'execution_time_ms' => round($executionTime * 1000, 2),
                'memory_used' => self::formatBytes($memoryUsed),
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'success' => false
            ]));

            throw $e;
        }
    }

    /**
     * Создает контекст для логирования с базовой информацией
     */
    public static function createContext(array $additionalContext = []): array
    {
        return array_merge([
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ], $additionalContext);
    }
}
