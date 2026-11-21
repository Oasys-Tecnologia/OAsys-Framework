<?php

namespace Framework\Infrastructure\DB\Migrations;

/**
 * Uma migration falhou. Se rodava em transação, nada dela ficou aplicado no banco.
 */
final class MigrationFalhou extends \RuntimeException
{
    public function __construct(public readonly string $migration, \Throwable $causa)
    {
        parent::__construct("Migration '{$migration}' falhou: {$causa->getMessage()}", 0, $causa);
    }
}
