<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Log;

// RNF-03. Revisa el contenido, no solo el nombre. Guarda fuera de public/
final class Subida
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    // Tipo => extensión => inicio del MIME real
    private const PERMITIDOS = [
        1 => ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'],
        2 => ['txt' => 'text/', 'log' => 'text/'],
    ];

    /**
     * Null si sirve
     *
     * @param array{name: string, tmp_name: string, size: int, error: int}|null $archivo
     */
    public static function error(?array $archivo, int $tipo): ?string
    {
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        $permitidos = self::PERMITIDOS[$tipo];
        $esperado = $permitidos[strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION))] ?? null;

        $motivo = match (true) {
            $archivo === null, $error === UPLOAD_ERR_NO_FILE => 'Es obligatorio.',
            in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true), $archivo['size'] > self::MAX_BYTES => 'Máximo 5 MB.',
            $error !== UPLOAD_ERR_OK, !is_uploaded_file($archivo['tmp_name']) => 'No se pudo subir el archivo.',
            $esperado === null, !str_starts_with((string) (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']), $esperado)
                => 'Solo ' . strtoupper(implode(', ', array_keys($permitidos))) . '.',
            default => null,
        };
        if ($motivo !== null && $archivo !== null && $error !== UPLOAD_ERR_NO_FILE) {
            Log::evento('critical', 'upload_validation:{archivo},{motivo}', ['archivo' => mb_substr($archivo['name'], 0, 255), 'motivo' => $motivo]);
        }

        return $motivo;
    }

    /**
     * Ya revisado con error(). Da el nombre guardado: sha256 del contenido
     * Archivo idéntico se guarda 1 vez, #118. $nuevo = false si ya estaba
     *
     * @param array{name: string, tmp_name: string, size: int, error: int} $archivo
     * @param-out bool $nuevo
     */
    public static function guardar(array $archivo, ?bool &$nuevo = null): string
    {
        $guardado = hash_file('sha256', $archivo['tmp_name']) . '.' . strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nuevo = !is_file(self::ruta($guardado));
        if ($nuevo && !move_uploaded_file($archivo['tmp_name'], self::ruta($guardado))) {
            throw new \RuntimeException('No se pudo guardar la evidencia.');
        }
        Log::evento('info', 'upload_complete:{id},{archivo},{tipo}', ['archivo' => $guardado, 'tipo' => self::mime($guardado)]);

        return $guardado;
    }

    public static function ruta(string $guardado): string
    {
        return RAIZ . '/storage/evidencias/' . basename($guardado);
    }

    // Extensión propia: ya pasó error()
    public static function mime(string $guardado): string
    {
        return match (pathinfo($guardado, PATHINFO_EXTENSION)) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'text/plain; charset=utf-8',
        };
    }
}
