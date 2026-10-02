<?php

declare(strict_types=1);

namespace App\Helpers;

// Lee config/catalogos.php y pinta sus valores. Así un texto o un color se cambia en un solo lugar.
// La clave es el número que se guarda en la base; acepta int (de PDO) o string (de $_POST).
//   Catalogo::insignia('estado_caso', $caso['estado'])        -> <span class="insignia …">FAULT</span>
//   Catalogo::opciones('tipo_prueba', $caso['tipo_prueba'])   -> <option>… para un <select>
//   Catalogo::existe('severidad', $_POST['severidad'])        -> validar en el servidor antes de guardar
final class Catalogo
{
    /** @var array<string, array{nombre: string, valores: array<int, array{texto: string, variante: string, sigla?: string}>}>|null */
    private static ?array $catalogos = null;

    /** @return array<string, array{nombre: string, valores: array<int, array{texto: string, variante: string, sigla?: string}>}> */
    public static function todos(): array
    {
        return self::$catalogos ??= require RAIZ . '/config/catalogos.php';
    }

    /** @return array<int, array{texto: string, variante: string, sigla?: string}> */
    public static function valores(string $catalogo): array
    {
        return self::todos()[$catalogo]['valores']
            ?? throw new \InvalidArgumentException("Catálogo no encontrado: {$catalogo}");
    }

    // '2' y 2 existen; '', '2.0' y ' 2' no.
    public static function existe(string $catalogo, int|string|null $clave): bool
    {
        return $clave !== null && isset(self::valores($catalogo)[$clave]);
    }

    // Texto para mostrar. Si la clave ya no está en el catálogo, se muestra tal cual en vez de fallar.
    public static function texto(string $catalogo, int|string|null $clave): string
    {
        return self::valores($catalogo)[$clave ?? '']['texto'] ?? (string) $clave;
    }

    public static function insignia(string $catalogo, int|string|null $clave): string
    {
        $variante = self::valores($catalogo)[$clave ?? '']['variante'] ?? 'borde';

        return '<span class="insignia insignia-' . Html::e($variante) . '">'
            . Html::e(self::texto($catalogo, $clave)) . '</span>';
    }

    // <option> de cada valor, con la elegida marcada. El "Elegir…" o "Todos" va aparte, en la vista.
    public static function opciones(string $catalogo, int|string|null $elegida = null): string
    {
        $html = '';
        foreach (self::valores($catalogo) as $clave => $valor) {
            $marcada = $elegida !== null && (string) $clave === (string) $elegida ? ' selected' : '';
            $html .= '<option value="' . $clave . '"' . $marcada . '>' . Html::e($valor['texto']) . '</option>';
        }

        return $html;
    }
}
