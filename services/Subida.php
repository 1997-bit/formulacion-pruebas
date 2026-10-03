<?php

declare(strict_types=1);

namespace App\Services;

// RNF-03: revisa el contenido, no solo el nombre, y guarda fuera de public/.
final class Subida
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    // Tipo de evidencia => extensión => inicio del tipo MIME real.
    private const PERMITIDOS = [
        1 => ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'],
        2 => ['txt' => 'text/', 'log' => 'text/'],
    ];

    /**
     * Null si sirve.
     *
     * @param array{name: string, tmp_name: string, size: int, error: int}|null $archivo
     */
    public static function error(?array $archivo, int $tipo): ?string
    {
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        $permitidos = self::PERMITIDOS[$tipo];
        $esperado = $permitidos[strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION))] ?? null;

        return match (true) {
            $archivo === null, $error === UPLOAD_ERR_NO_FILE => 'Es obligatorio.',
            in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true), $archivo['size'] > self::MAX_BYTES => 'Máximo 5 MB.',
            $error !== UPLOAD_ERR_OK, !is_uploaded_file($archivo['tmp_name']) => 'No se pudo subir el archivo.',
            $esperado === null, !str_starts_with((string) (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']), $esperado)
                => 'Solo ' . strtoupper(implode(', ', array_keys($permitidos))) . '.',
            default => null,
        };
    }

    /**
     * Ya revisado con error(). Devuelve el nombre guardado.
     *
     * @param array{name: string, tmp_name: string, size: int, error: int} $archivo
     */
    public static function guardar(array $archivo): string
    {
        $guardado = bin2hex(random_bytes(16)) . '.' . strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!move_uploaded_file($archivo['tmp_name'], self::ruta($guardado))) {
            throw new \RuntimeException('No se pudo guardar la evidencia.');
        }

        return $guardado;
    }

    public static function ruta(string $guardado): string
    {
        return RAIZ . '/storage/evidencias/' . basename($guardado);
    }

    // Por la extensión propia, no por el contenido: lo guardado ya pasó error().
    public static function mime(string $guardado): string
    {
        return match (pathinfo($guardado, PATHINFO_EXTENSION)) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'text/plain; charset=utf-8',
        };
    }
}
