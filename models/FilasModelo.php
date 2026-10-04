<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Conexion;

// Filas de los formularios 2 a 5: (requerimiento_id, orden), sus columnas y guardado_por/guardado_en. $tabla y las columnas vienen del servicio.
final class FilasModelo
{
    /** @return list<array<string, mixed>> */
    public static function deRequerimiento(string $tabla, int $requerimientoId): array
    {
        $sql = Conexion::pdo()->prepare("SELECT * FROM {$tabla} WHERE requerimiento_id = ? ORDER BY orden");
        $sql->execute([$requerimientoId]);

        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Quién guardó la matriz y cuándo. Null si no tiene filas.
     *
     * @return array{autor: string, guardado_en: string}|null
     */
    public static function guardado(string $tabla, int $requerimientoId): ?array
    {
        $sql = Conexion::pdo()->prepare(
            "SELECT u.nombre AS autor, f.guardado_en FROM {$tabla} f JOIN usuarios u ON u.id = f.guardado_por WHERE f.requerimiento_id = ? ORDER BY f.guardado_en DESC LIMIT 1"
        );
        $sql->execute([$requerimientoId]);

        return $sql->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Solo los requerimientos de la página: a lo más 20 grupos (#108).
     *
     * @param list<int> $requerimientos
     * @return array<int, int> requerimiento_id => filas
     */
    public static function contar(string $tabla, array $requerimientos): array
    {
        if ($requerimientos === []) {
            return [];
        }
        $sql = Conexion::pdo()->prepare(
            "SELECT requerimiento_id, COUNT(*) FROM {$tabla} WHERE requerimiento_id IN ("
            . implode(', ', array_fill(0, count($requerimientos), '?')) . ') GROUP BY requerimiento_id'
        );
        $sql->execute($requerimientos);

        return array_map(intval(...), $sql->fetchAll(\PDO::FETCH_KEY_PAIR));
    }

    /**
     * Un solo INSERT para todas las filas (#97). Sin filas no ejecuta nada.
     *
     * @param list<string> $columnas
     * @param list<list<mixed>> $filas  valores en el orden de $columnas
     */
    public static function insertar(string $tabla, array $columnas, array $filas): void
    {
        if ($filas === []) {
            return;
        }
        $fila = '(' . implode(', ', array_fill(0, count($columnas), '?')) . ')';
        Conexion::pdo()->prepare(
            "INSERT INTO {$tabla} (" . implode(', ', $columnas) . ') VALUES ' . implode(', ', array_fill(0, count($filas), $fila))
        )->execute(array_merge(...$filas));
    }

    /**
     * Guarda por diferencia (#98). Dentro de una transacción.
     * Inserta las filas nuevas, borra las quitadas y actualiza las cambiadas. Sin cambios no escribe nada.
     *
     * @param array<string, int> $dueno  p. ej. ['requerimiento_id' => 5]
     * @param list<string> $llave  columnas que distinguen la fila dentro del dueño, p. ej. ['orden']
     * @param list<array<string, mixed>> $filas  la llave y los datos; todas con las mismas columnas
     * @param array<string, mixed> $autor  solo en filas nuevas o cambiadas, p. ej. guardado_por
     */
    public static function sincronizar(string $tabla, array $dueno, array $llave, array $filas, array $autor = []): void
    {
        $pdo = Conexion::pdo();
        $igual = fn (array $columnas): string => implode(' AND ', array_map(fn (string $c): string => "{$c} = ?", $columnas));
        $deLlave = fn (array $f): array => array_map(fn (string $c): mixed => $f[$c], $llave);
        $id = fn (array $f): string => implode('|', $deLlave($f));
        $texto = fn (mixed $v): ?string => $v === null ? null : (string) $v;
        $deDueno = $igual(array_keys($dueno));

        $sql = $pdo->prepare("SELECT * FROM {$tabla} WHERE {$deDueno}");
        $sql->execute(array_values($dueno));
        $antes = [];
        foreach ($sql->fetchAll(\PDO::FETCH_ASSOC) as $f) {
            $antes[$id($f)] = $f;
        }

        $nuevas = [];
        foreach ($filas as $f) {
            $vieja = $antes[$id($f)] ?? null;
            unset($antes[$id($f)]);
            if ($vieja === null) {
                $nuevas[] = array_values($dueno + $f + $autor);
                continue;
            }
            $cambios = [];
            foreach (array_diff_key($f, array_flip($llave)) as $c => $v) {
                if ($texto($v) !== $texto($vieja[$c])) {
                    $cambios[$c] = $v;
                }
            }
            if ($cambios !== []) {
                $set = $cambios + $autor;
                $pdo->prepare('UPDATE ' . $tabla . ' SET ' . implode(', ', array_map(fn (string $c): string => "{$c} = ?", array_keys($set)))
                    . " WHERE {$deDueno} AND " . $igual($llave))
                    ->execute([...array_values($set), ...array_values($dueno), ...$deLlave($f)]);
            }
        }
        if ($nuevas !== []) {
            self::insertar($tabla, array_keys($dueno + $filas[0] + $autor), $nuevas);
        }
        // Las que quedaron en $antes ya no vienen: se borran en un DELETE.
        if ($antes !== []) {
            $tupla = '(' . implode(', ', array_fill(0, count($llave), '?')) . ')';
            $pdo->prepare("DELETE FROM {$tabla} WHERE {$deDueno} AND (" . implode(', ', $llave) . ') IN ('
                . implode(', ', array_fill(0, count($antes), $tupla)) . ')')
                ->execute([...array_values($dueno), ...array_merge(...array_map($deLlave, array_values($antes)))]);
        }
    }

    /**
     * Borra y vuelve a insertar con orden 1, 2, 3… Dentro de una transacción.
     *
     * @param list<string> $columnas
     * @param list<array<string, string>> $filas
     */
    public static function reemplazar(string $tabla, int $requerimientoId, array $columnas, array $filas, int $usuarioId): void
    {
        $pdo = Conexion::pdo();
        $pdo->prepare("DELETE FROM {$tabla} WHERE requerimiento_id = ?")->execute([$requerimientoId]);
        $sql = $pdo->prepare(
            "INSERT INTO {$tabla} (requerimiento_id, orden, guardado_por, " . implode(', ', $columnas) . ')
             VALUES (?, ?, ?' . str_repeat(', ?', count($columnas)) . ')'
        );
        foreach ($filas as $i => $fila) {
            $valores = [$requerimientoId, $i + 1, $usuarioId];
            foreach ($columnas as $columna) {
                $valores[] = $fila[$columna];
            }
            $sql->execute($valores);
        }
    }
}
