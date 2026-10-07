<?php

declare(strict_types=1);

namespace Tests\Apoyo;

// La prueba abre la transacción; las de los servicios pasan a SAVEPOINT. Al final, ROLLBACK: la base queda igual.
final class PdoPruebas extends \PDO
{
    private int $nivel = 0;

    public function beginTransaction(): bool
    {
        if ($this->nivel++ === 0) {
            return parent::beginTransaction();
        }
        $this->exec("SAVEPOINT p{$this->nivel}");

        return true;
    }

    public function commit(): bool
    {
        if (--$this->nivel === 0) {
            return parent::commit();
        }
        $this->exec('RELEASE SAVEPOINT p' . ($this->nivel + 1));

        return true;
    }

    public function rollBack(): bool
    {
        if (--$this->nivel === 0) {
            return parent::rollBack();
        }
        $this->exec('ROLLBACK TO SAVEPOINT p' . ($this->nivel + 1));

        return true;
    }
}
