<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Helpers\Html;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Html::class)]
#[Small]
final class HtmlTest extends TestCase
{
    /** @return array<string, array{?string, string}> */
    public static function textos(): array
    {
        return [
            'etiqueta' => ['<script>x</script>', '&lt;script&gt;x&lt;/script&gt;'],
            'comilla doble' => ['a"b', 'a&quot;b'],
            'comilla simple' => ["a'b", 'a&#039;b'],
            'ampersand' => ['a&b', 'a&amp;b'],
            'null' => [null, ''],
            'UTF-8 inválido' => ["a\xC3(", "a\u{FFFD}("],
            'tildes intactas' => ['ñandú', 'ñandú'],
        ];
    }

    // RNF-01
    #[DataProvider('textos')]
    public function testEscapa(?string $texto, string $esperado): void
    {
        $html = Html::e($texto);

        $this->assertSame($esperado, $html);
    }

    /** @return array<string, array{string, string}> */
    public static function nombres(): array
    {
        return [
            '1 palabra' => ['Ana', 'A'],
            '2 palabras' => ['Juan Garcia', 'JG'],
            '3 palabras usa 2' => ['Juan Carlos Garcia', 'JC'],
            'espacios extra' => ["  juan \t  garcia  ", 'JG'],
            'vacío' => ['', '?'],
            'solo espacios' => ['   ', '?'],
            'tilde' => ['ángel úrsula', 'ÁÚ'],
        ];
    }

    #[DataProvider('nombres')]
    public function testIniciales(string $nombre, string $esperado): void
    {
        $iniciales = Html::iniciales($nombre);

        $this->assertSame($esperado, $iniciales);
    }
}
