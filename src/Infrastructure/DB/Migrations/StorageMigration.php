<?php

namespace Framework\Infrastructure\DB\Migrations;

use Framework\Infrastructure\DB\Persistence\Storage\GenericStorage;

/**
 * Abstração das classes de atualização das databases.
 * Deve ser estentidada por todas classes de atualização (Migration).
 *
 * @since 16/03/2025
 * @author Iago Oliveira <prog.iago.oliveira@gmail.com>
 */
abstract class StorageMigration implements IMigration
{
    /** @var GenericStorage */
    protected $oStorage;

    public function __construct(GenericStorage $oStorage)
    {
        $this->oStorage = $oStorage;
    }

    /**
     * Por padrão a migration roda dentro de uma transação, junto do registro em oasys.migrations.
     * Sobrescreva retornando false só para comandos que o Postgres não aceita em transação
     * (ex.: CREATE INDEX CONCURRENTLY).
     */
    public function emTransacao(): bool
    {
        return true;
    }
}
