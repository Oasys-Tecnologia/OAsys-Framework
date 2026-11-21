<?php

namespace Framework\Tests\Integration\Migrations;

use Framework\Infrastructure\DB\Persistence\Storage\PdoStorage;
use PHPUnit\Framework\TestCase;

/**
 * Base dos testes de migrations: bancos Postgres descartáveis e um diretório temporário de migrations.
 */
abstract class MigrationsTestCase extends TestCase
{
    protected string $dir;
    /** @var string[] */
    private array $bancos = [];
    private static int $sequencia = 0;

    protected function setUp(): void
    {
        if (!getenv('OASYS_TEST_PG_HOST')) {
            $this->markTestSkipped('Defina OASYS_TEST_PG_HOST (e PORT, USER, PASSWORD) para rodar os testes de integração.');
        }

        $this->dir = sys_get_temp_dir() . '/oasys_migrations_' . bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach ($this->bancos as $banco) {
            $this->pdo('postgres')->exec("DROP DATABASE IF EXISTS {$banco} WITH (FORCE)");
        }

        if (isset($this->dir) && is_dir($this->dir)) {
            array_map('unlink', glob($this->dir . '/*'));
            rmdir($this->dir);
        }
    }

    /** Cria um banco vazio e devolve o nome. */
    protected function criarBanco(): string
    {
        $banco = 'oasys_teste_' . getmypid() . '_' . (++self::$sequencia);
        $admin = $this->pdo('postgres');
        $admin->exec("DROP DATABASE IF EXISTS {$banco} WITH (FORCE)");
        $admin->exec("CREATE DATABASE {$banco}");
        $this->bancos[] = $banco;

        return $banco;
    }

    protected function pdo(string $banco): \PDO
    {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', getenv('OASYS_TEST_PG_HOST'), getenv('OASYS_TEST_PG_PORT') ?: '5432', $banco);

        return new \PDO($dsn, getenv('OASYS_TEST_PG_USER') ?: 'postgres', getenv('OASYS_TEST_PG_PASSWORD') ?: '', [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
    }

    protected function storage(string $banco): PdoStorage
    {
        return new PdoStorage($this->pdo($banco));
    }

    /**
     * Escreve uma migration no diretório temporário. O sufixo aleatório evita colisão de nome de classe
     * entre testes, já que o PHP não esquece uma classe declarada.
     *
     * @return string nome da migration
     */
    protected function migration(string $prefixo, string $sqlUp, string $sqlDown = 'SELECT 1', bool $legada = false, bool $emTransacao = true): string
    {
        $nome = $legada ? $prefixo . random_int(100000, 999999) : $prefixo . '_' . bin2hex(random_bytes(4));
        $classe = ($legada ? 'version_' : 'Migration_') . $nome;
        $transacao = $emTransacao ? '' : 'public function emTransacao(): bool { return false; }';

        file_put_contents($this->dir . "/{$nome}.php", <<<PHP
<?php
class {$classe} extends \Framework\Infrastructure\DB\Migrations\StorageMigration
{
    function up() { \$this->oStorage->exec("{$sqlUp}"); }
    function down() { \$this->oStorage->exec("{$sqlDown}"); }
    {$transacao}
}
PHP);

        return $nome;
    }

    /** @return string[] */
    protected function executadas(string $banco): array
    {
        return $this->pdo($banco)->query('SELECT migration FROM oasys.migrations ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN);
    }

    protected function tabelaExiste(string $banco, string $tabela): bool
    {
        return (bool) $this->pdo($banco)->query("SELECT to_regclass('public.{$tabela}') IS NOT NULL")->fetchColumn();
    }
}
