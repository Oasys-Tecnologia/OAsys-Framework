<?php

namespace Framework\Infrastructure\DB\Migrations;

use Framework\Infrastructure\DB\Persistence\Storage\PdoStorage;

/**
 * Roda as migrations pendentes em vários bancos (um por tenant), um de cada vez.
 *
 * A falha de um banco não interrompe os demais: ela desfaz só a migration que quebrou naquele banco
 * e aparece no relatório. Quem chama decide o que fazer com RelatorioMigrations::sucesso().
 */
final class MigrationOrchestrator
{
    /** @var callable */
    private $log;

    /**
     * @param array<string, callable(): PdoStorage> $alvos nome do banco => como conectar nele
     */
    public function __construct(private array $alvos, private string $migrationsDir, ?callable $log = null)
    {
        $this->log = $log ?? static function (string $linha): void {
            echo $linha . "\n";
        };
    }

    public function executar(): RelatorioMigrations
    {
        $relatorio = new RelatorioMigrations();

        foreach ($this->alvos as $nome => $conectar) {
            ($this->log)(">> {$nome}");
            $executadas = [];

            try {
                $manager = new PdoMigrationManager($conectar(), $this->migrationsDir, function (string $linha) use (&$executadas) {
                    if (preg_match("/^Migration '(.+)' executada com sucesso\.$/", $linha, $m)) {
                        $executadas[] = $m[1];
                    }
                    ($this->log)('   ' . $linha);
                });
                $manager->runMigrations();
                $relatorio->registrar($nome, $executadas);
            } catch (\Throwable $e) {
                ($this->log)("   FALHOU: {$e->getMessage()}");
                $relatorio->registrar($nome, $executadas, $e->getMessage());
            }
        }

        return $relatorio;
    }

    /**
     * Pendências de cada banco, sem executar nada.
     * @return array<string, string[]|string> nome => migrations pendentes, ou a mensagem de erro ao consultar
     */
    public function status(): array
    {
        $status = [];

        foreach ($this->alvos as $nome => $conectar) {
            try {
                $status[$nome] = (new PdoMigrationManager($conectar(), $this->migrationsDir, static fn() => null))->pendentes();
            } catch (\Throwable $e) {
                $status[$nome] = $e->getMessage();
            }
        }

        return $status;
    }
}
