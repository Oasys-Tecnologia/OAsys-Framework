<?php

namespace Framework\Infrastructure\DB\Migrations;

use Framework\Infrastructure\DB\Persistence\Storage\PdoStorage;

/**
 * Gerencia as migrations de UM banco.
 *
 * Dois formatos de arquivo convivem no mesmo diretório:
 *   - legado:  0000000223.php                         classe version_0000000223
 *   - atual:   2026_11_15_143000_cria_tabela_x.php    classe Migration_2026_11_15_143000_cria_tabela_x
 * A ordem de execução é a ordem alfabética do nome do arquivo, então o legado vem sempre antes.
 *
 * Cada migration roda em transação junto do registro em oasys.migrations: se falhar, nada dela fica
 * aplicado. Um advisory lock impede duas execuções simultâneas no mesmo banco.
 *
 * @since 16/03/2025
 * @author Iago Oliveira <prog.iago.oliveira@gmail.com>
 */
final class PdoMigrationManager implements IMigration
{
    public const FORMATO_ATUAL = '/^\d{4}_\d{2}_\d{2}_\d{6}_[a-z0-9_]+$/';
    private const FORMATO_LEGADO = '/^\d+$/';

    /** Chave do advisory lock (o lock já é por banco). */
    private const LOCK = 7241906;

    private $oStorage;
    private $migrationsDir;
    /** @var callable */
    private $log;

    /**
     * @param callable|null $log recebe cada linha de saída; por padrão escreve na saída padrão.
     */
    public function __construct(PdoStorage $oStorage, $migrationsDir, ?callable $log = null)
    {
        $this->oStorage = $oStorage;
        $this->migrationsDir = rtrim($migrationsDir, '/');
        $this->log = $log ?? static function (string $linha): void {
            echo $linha . "\n";
        };
    }

    /**
     * Executa a migration passada por parâmetro.
     * @throws MigrationFalhou
     */
    public function up($migrationName = null)
    {
        $this->createMigrationsTableIfNotExists();

        if (!$this->existe($migrationName)) {
            $this->log("Migration '{$migrationName}' não encontrada.");
            return;
        }

        if (in_array($migrationName, $this->getExecutedMigrations(), true)) {
            $this->log("Migration '{$migrationName}' já foi executada.");
            return;
        }

        $this->comLock(fn() => $this->aplicar($migrationName));
    }

    /**
     * Desfaz a migration passada por parâmetro.
     * @throws MigrationFalhou
     */
    public function down($migrationName = null)
    {
        $this->createMigrationsTableIfNotExists();

        if (!$this->existe($migrationName)) {
            $this->log("Migration '{$migrationName}' não encontrada.");
            return;
        }

        if (!in_array($migrationName, $this->getExecutedMigrations(), true)) {
            $this->log("Migration '{$migrationName}' ainda não foi executada.");
            return;
        }

        $this->comLock(fn() => $this->reverter($migrationName));
    }

    /**
     * Executa, em ordem, todas as migrations pendentes. Para na primeira que falhar.
     * @return string[] migrations executadas nesta chamada
     * @throws MigrationFalhou
     */
    public function runMigrations(): array
    {
        $this->createMigrationsTableIfNotExists();

        return $this->comLock(function () {
            $executadas = [];

            // Relido já com o lock: outra execução pode ter aplicado migrations enquanto esta esperava.
            foreach ($this->pendentes() as $migrationName) {
                $this->aplicar($migrationName);
                $executadas[] = $migrationName;
            }

            $this->log($executadas ? "Todas migrations disponíveis foram executadas." : "Não existe nenhuma migration disponível parar ser executada.");

            return $executadas;
        });
    }

    /**
     * Reverte as últimas migrations executadas.
     * @param int $iSteps - Quantidade de migrations a serem revertidas.
     * @throws MigrationFalhou
     */
    public function rollbackMigrations($iSteps = 1)
    {
        $this->createMigrationsTableIfNotExists();

        $this->comLock(function () use ($iSteps) {
            $revertidas = 0;

            foreach (array_slice(array_reverse($this->getExecutedMigrations()), 0, $iSteps) as $migrationName) {
                if ($this->existe($migrationName)) {
                    $this->reverter($migrationName);
                    $revertidas++;
                }
            }

            $this->log($revertidas ? "Foram revertidas as últimas {$revertidas} migrations executadas." : "Nenhuma migration a ser revertida.");
        });
    }

    /**
     * Migrations do diretório ainda não executadas neste banco, na ordem de execução.
     * @return string[]
     */
    public function pendentes(): array
    {
        $this->createMigrationsTableIfNotExists();

        return array_values(array_diff($this->disponiveis(), $this->getExecutedMigrations()));
    }

    /**
     * Migrations existentes no diretório, na ordem de execução.
     * @return string[]
     */
    public function disponiveis(): array
    {
        $nomes = [];

        foreach (glob($this->migrationsDir . '/*.php') ?: [] as $file) {
            $nome = basename($file, '.php');

            if (self::nomeValido($nome)) {
                $nomes[] = $nome;
            }
        }

        sort($nomes, SORT_STRING);

        return $nomes;
    }

    public static function nomeValido(string $nome): bool
    {
        return preg_match(self::FORMATO_LEGADO, $nome) === 1 || preg_match(self::FORMATO_ATUAL, $nome) === 1;
    }

    public static function getMigrationClassName(string $migrationName): string
    {
        return (preg_match(self::FORMATO_LEGADO, $migrationName) === 1 ? 'version_' : 'Migration_') . $migrationName;
    }

    private function aplicar(string $migrationName): void
    {
        $migration = $this->instanciar($migrationName);

        $this->emTransacao($migrationName, $migration, function () use ($migration, $migrationName) {
            $migration->up();
            $this->oStorage->exec("INSERT INTO oasys.migrations (migration) VALUES ('{$migrationName}')");
        });

        $this->log("Migration '{$migrationName}' executada com sucesso.");
    }

    private function reverter(string $migrationName): void
    {
        $migration = $this->instanciar($migrationName);

        $this->emTransacao($migrationName, $migration, function () use ($migration, $migrationName) {
            $migration->down();
            $this->oStorage->exec("DELETE FROM oasys.migrations WHERE migration = '{$migrationName}'");
        });

        $this->log("Migration '{$migrationName}' revertida com sucesso.");
    }

    private function emTransacao(string $migrationName, IMigration $migration, callable $passo): void
    {
        $transacional = !method_exists($migration, 'emTransacao') || $migration->emTransacao();

        try {
            if ($transacional) {
                $this->oStorage->beginTransaction();
            }

            $passo();

            if ($transacional) {
                $this->oStorage->commit();
            }
        } catch (\Throwable $e) {
            if ($this->oStorage->inTransaction()) {
                $this->oStorage->rollback();
            }

            throw new MigrationFalhou($migrationName, $e);
        }
    }

    private function instanciar(string $migrationName): IMigration
    {
        // O nome vai para SQL e para o nome de classe: só entra o que bate com um dos dois formatos.
        if (!self::nomeValido($migrationName)) {
            throw new \InvalidArgumentException("Nome de migration inválido: '{$migrationName}'.");
        }

        require_once $this->migrationsDir . '/' . $migrationName . '.php';

        $classe = self::getMigrationClassName($migrationName);

        if (!class_exists($classe, false)) {
            throw new \RuntimeException("O arquivo '{$migrationName}.php' precisa declarar a classe '{$classe}'.");
        }

        return new $classe($this->oStorage);
    }

    private function existe($migrationName): bool
    {
        return is_string($migrationName)
            && self::nomeValido($migrationName)
            && file_exists($this->migrationsDir . '/' . $migrationName . '.php');
    }

    /**
     * Roda o bloco segurando o advisory lock de migrations deste banco.
     * Não espera: se outra execução está em andamento, falha na hora.
     */
    private function comLock(callable $bloco)
    {
        $obtido = $this->oStorage->query('SELECT pg_try_advisory_lock(' . self::LOCK . ') AS obtido');

        if (!$obtido || !$obtido[0]['obtido']) {
            throw new \RuntimeException('Já existe uma execução de migrations em andamento neste banco.');
        }

        try {
            return $bloco();
        } finally {
            $this->oStorage->query('SELECT pg_advisory_unlock(' . self::LOCK . ')');
        }
    }

    private function createMigrationsTableIfNotExists()
    {
        $this->oStorage->exec("CREATE SCHEMA IF NOT EXISTS oasys;");
        $this->oStorage->exec("CREATE TABLE IF NOT EXISTS oasys.migrations (
            id serial4 PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    /**
     * Migrations já executadas, da mais antiga para a mais recente.
     * @return string[]
     */
    private function getExecutedMigrations(): array
    {
        $migrations = $this->oStorage->query("SELECT migration FROM oasys.migrations ORDER BY executed_at ASC, id ASC");

        return is_array($migrations) ? array_column($migrations, 'migration') : [];
    }

    private function log(string $linha): void
    {
        ($this->log)($linha);
    }
}
