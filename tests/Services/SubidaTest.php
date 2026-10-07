<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\Subida;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

// RNF-03. Lo que exige un archivo subido de verdad (is_uploaded_file) no se prueba aquí.
#[CoversClass(Subida::class)]
#[Small]
final class SubidaTest extends TestCase
{
    private const CONTENIDO = 'log de prueba de Subida';

    private const SHA = '70672b460b9bd3ab0543192f49dc8ca53700a92730df8c093aabae41575fbad8';

    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = (string) tempnam(sys_get_temp_dir(), 'sub');
        file_put_contents($this->tmp, self::CONTENIDO);
    }

    protected function tearDown(): void
    {
        @unlink($this->tmp);
        @unlink(Subida::ruta(self::SHA . '.txt'));
    }

    /** @return array<string, array{?array{name: string, tmp_name: string, size: int, error: int}, string}> */
    public static function errores(): array
    {
        $archivo = ['name' => 'log.txt', 'tmp_name' => __FILE__, 'size' => 10, 'error' => UPLOAD_ERR_OK];

        return [
            'sin archivo' => [null, 'Es obligatorio.'],
            'campo vacío' => [['error' => UPLOAD_ERR_NO_FILE] + $archivo, 'Es obligatorio.'],
            'pasa upload_max_filesize' => [['error' => UPLOAD_ERR_INI_SIZE] + $archivo, 'Máximo 5 MB.'],
            'pasa MAX_FILE_SIZE' => [['error' => UPLOAD_ERR_FORM_SIZE] + $archivo, 'Máximo 5 MB.'],
            '5 MB + 1' => [['size' => 5 * 1024 * 1024 + 1] + $archivo, 'Máximo 5 MB.'],
            '5 MB no es grande' => [['size' => 5 * 1024 * 1024] + $archivo, 'No se pudo subir el archivo.'],
            'error de PHP' => [['error' => UPLOAD_ERR_PARTIAL] + $archivo, 'No se pudo subir el archivo.'],
            'no vino por HTTP' => [$archivo, 'No se pudo subir el archivo.'],
        ];
    }

    /** @param array{name: string, tmp_name: string, size: int, error: int}|null $archivo */
    #[DataProvider('errores')]
    public function testErrorDeLaSubida(?array $archivo, string $esperado): void
    {
        $error = Subida::error($archivo, 2);

        $this->assertSame($esperado, $error);
    }

    // #118: un archivo igual ya guardado no se mueve otra vez.
    public function testGuardarArchivoRepetidoDevuelveElMismoNombre(): void
    {
        $nombre = self::SHA . '.txt';
        file_put_contents(Subida::ruta($nombre), self::CONTENIDO);

        $guardado = Subida::guardar(['name' => 'LOG.TXT', 'tmp_name' => $this->tmp, 'size' => 10, 'error' => UPLOAD_ERR_OK], $nuevo);

        $this->assertSame([$nombre, false], [$guardado, $nuevo]);
    }

    public function testRutaNoSaleDeStorage(): void
    {
        $ruta = Subida::ruta('../../.env');

        $this->assertSame(RAIZ . '/storage/evidencias/.env', $ruta);
    }

    /** @return array<string, array{string, string}> */
    public static function tipos(): array
    {
        return [
            'png' => ['a.png', 'image/png'],
            'jpg' => ['a.jpg', 'image/jpeg'],
            'jpeg' => ['a.jpeg', 'image/jpeg'],
            'txt' => ['a.txt', 'text/plain; charset=utf-8'],
            'log' => ['a.log', 'text/plain; charset=utf-8'],
        ];
    }

    #[DataProvider('tipos')]
    public function testMimeSaleDeLaExtension(string $archivo, string $esperado): void
    {
        $mime = Subida::mime($archivo);

        $this->assertSame($esperado, $mime);
    }
}
