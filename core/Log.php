<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

// RNF-11. Canales http, seguridad y app. Una línea JSON por evento
final class Log
{
    // RF o RNF de la ruta, de index.php
    public static ?string $rf = null;

    private static ?Logger $base = null;

    private static ?UidProcessor $uid = null;

    /** @var array<string, Logger> */
    private static array $canales = [];

    public static function canal(string $nombre): Logger
    {
        self::$base ??= self::crear(new RotatingFileHandler(
            RAIZ . '/storage/logs/app.log',
            (int) Env::get('LOG_DIAS', '14'),
            Env::get('LOG_NIVEL', 'info'),
        ));

        return self::$canales[$nombre] ??= self::$base->withName($nombre);
    }

    // OWASP Logging Vocabulary. {id}: usuario de la sesión
    /** @param array<string, mixed> $contexto */
    public static function evento(string $nivel, string $mensaje, array $contexto = []): void
    {
        self::canal('seguridad')->log($nivel, $mensaje, $contexto + ['id' => Sesion::usuario()['id'] ?? null]);
    }

    // Id de la petición: X-Request-Id, página 500 y cada línea
    public static function uid(): string
    {
        self::canal('app');

        return self::$uid->getUid();
    }

    // Pruebas: otro handler, TestHandler
    public static function usar(HandlerInterface $handler): void
    {
        self::$base = self::crear($handler);
        self::$canales = [];
    }

    private static function crear(HandlerInterface $handler): Logger
    {
        if ($handler instanceof RotatingFileHandler) {
            $handler->setFormatter((new JsonFormatter())->includeStacktraces());
        }
        self::$uid = new UidProcessor(8);

        return new Logger('app', [$handler], [
            self::$uid,
            new WebProcessor(null, ['url', 'ip', 'http_method', 'referrer']),
            new MemoryPeakUsageProcessor(),
            new PsrLogMessageProcessor(),
            static function (LogRecord $r): LogRecord {
                $r->extra['user_id'] = Sesion::usuario()['id'] ?? null;
                $r->extra['rf'] = self::$rf;

                return $r;
            },
        ]);
    }
}
