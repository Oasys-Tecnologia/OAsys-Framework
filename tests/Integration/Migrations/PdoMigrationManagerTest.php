<?php

namespace Framework\Tests\Integration\Migrations;

use Framework\Infrastructure\DB\Migrations\MigrationFalhou;
use Framework\Infrastructure\DB\Migrations\PdoMigrationManager;

final class PdoMigrationManagerTest extends MigrationsTestCase
{
    private array $saida = [];

    private function manager(string $banco): PdoMigrationManager
    {
        return new PdoMigrationManager($this->storage($banco), $this->dir, function (string $linha) {
            $this->saida[] = $linha;
        });
    }

    public function test_executa_as_pendentes_com_o_formato_legado_antes_do_formato_com_data(): void
    {
        $banco = $this->criarBanco();
        $depois = $this->migration('2026_11_20_090000_depois', 'CREATE TABLE c (id int)');
        $antes = $this->migration('2026_11_15_143000_antes', 'CREATE TABLE b (id int)');
        $legada = $this->migration('0000', 'CREATE TABLE a (id int)', legada: true);

        $executadas = $this->manager($banco)->runMigrations();

        $this->assertSame([$legada, $antes, $depois], $executadas);
        $this->assertSame([$legada, $antes, $depois], $this->executadas($banco));
    }

    public function test_nao_reexecuta_o_que_ja_rodou(): void
    {
        $banco = $this->criarBanco();
        $this->migration('2026_11_15_143000_a', 'CREATE TABLE a (id int)');
        $this->manager($banco)->runMigrations();

        $nova = $this->migration('2026_11_16_100000_b', 'CREATE TABLE b (id int)');

        $this->assertSame([$nova], $this->manager($banco)->pendentes());
        $this->assertSame([$nova], $this->manager($banco)->runMigrations());
        $this->assertSame([], $this->manager($banco)->runMigrations());
    }

    public function test_migration_que_falha_nao_deixa_nada_aplicado_e_interrompe_as_seguintes(): void
    {
        $banco = $this->criarBanco();
        $ok = $this->migration('2026_11_15_100000_ok', 'CREATE TABLE ok (id int)');
        $quebrada = $this->migration('2026_11_15_110000_quebrada', 'CREATE TABLE metade (id int); SELECT * FROM tabela_que_nao_existe');
        $this->migration('2026_11_15_120000_seguinte', 'CREATE TABLE seguinte (id int)');

        try {
            $this->manager($banco)->runMigrations();
            $this->fail('Esperava MigrationFalhou.');
        } catch (MigrationFalhou $e) {
            $this->assertSame($quebrada, $e->migration);
        }

        $this->assertSame([$ok], $this->executadas($banco));
        $this->assertFalse($this->tabelaExiste($banco, 'metade'), 'A parte da migration que rodou antes do erro precisa ser desfeita.');
        $this->assertFalse($this->tabelaExiste($banco, 'seguinte'));
    }

    public function test_depois_da_falha_o_banco_segue_migravel(): void
    {
        $banco = $this->criarBanco();
        $quebrada = $this->migration('2026_11_15_110000_quebrada', 'SELECT * FROM tabela_que_nao_existe');

        try {
            $this->manager($banco)->runMigrations();
        } catch (MigrationFalhou) {
        }

        unlink($this->dir . "/{$quebrada}.php");
        $corrigida = $this->migration('2026_11_15_110000_corrigida', 'CREATE TABLE corrigida (id int)');

        // Mesma conexão do banco: o lock da execução que falhou precisa ter sido solto.
        $this->assertSame([$corrigida], $this->manager($banco)->runMigrations());
    }

    public function test_arquivo_sql_roda_dentro_da_transacao_da_migration(): void
    {
        $banco = $this->criarBanco();
        $sql = $this->dir . '/script.sql';
        file_put_contents($sql, 'CREATE TABLE do_arquivo (id int); SELECT * FROM tabela_que_nao_existe;');

        $nome = '2026_11_15_100000_arquivo_' . bin2hex(random_bytes(4));
        file_put_contents($this->dir . "/{$nome}.php", <<<PHP
<?php
class Migration_{$nome} extends \Framework\Infrastructure\DB\Migrations\StorageMigration
{
    function up() { \$this->oStorage->exec("CREATE TABLE antes_do_arquivo (id int)"); \$this->oStorage->executarArquivoSQL('{$sql}'); }
    function down() {}
}
PHP);

        try {
            $this->manager($banco)->runMigrations();
            $this->fail('Esperava MigrationFalhou.');
        } catch (MigrationFalhou) {
        }
        unlink($sql);

        $this->assertFalse($this->tabelaExiste($banco, 'antes_do_arquivo'));
        $this->assertFalse($this->tabelaExiste($banco, 'do_arquivo'));
        $this->assertSame([], $this->executadas($banco));
    }

    public function test_migration_sem_transacao_roda_comandos_que_o_postgres_nao_aceita_em_transacao(): void
    {
        $banco = $this->criarBanco();
        $this->migration('2026_11_15_100000_tabela', 'CREATE TABLE t (id int)');
        $indice = $this->migration('2026_11_15_110000_indice', 'CREATE INDEX CONCURRENTLY idx_t ON t (id)', emTransacao: false);

        $this->manager($banco)->runMigrations();

        $this->assertContains($indice, $this->executadas($banco));
    }

    public function test_recusa_rodar_enquanto_outra_execucao_segura_o_lock(): void
    {
        $banco = $this->criarBanco();
        $this->migration('2026_11_15_100000_a', 'CREATE TABLE a (id int)');

        $outraExecucao = $this->pdo($banco);
        $outraExecucao->query('SELECT pg_advisory_lock(7241906)');

        try {
            $this->manager($banco)->runMigrations();
            $this->fail('Esperava a recusa pelo lock.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('em andamento', $e->getMessage());
        }

        $this->assertFalse($this->tabelaExiste($banco, 'a'));
    }

    public function test_up_e_down_de_uma_migration_especifica(): void
    {
        $banco = $this->criarBanco();
        $nome = $this->migration('2026_11_15_100000_a', 'CREATE TABLE a (id int)', 'DROP TABLE a');

        $this->manager($banco)->down($nome);
        $this->assertContains("Migration '{$nome}' ainda não foi executada.", $this->saida);

        $this->manager($banco)->up($nome);
        $this->assertTrue($this->tabelaExiste($banco, 'a'));

        $this->manager($banco)->up($nome);
        $this->assertContains("Migration '{$nome}' já foi executada.", $this->saida);

        $this->manager($banco)->down($nome);
        $this->assertFalse($this->tabelaExiste($banco, 'a'));
        $this->assertSame([], $this->executadas($banco));
    }

    public function test_rollback_reverte_as_ultimas_na_ordem_inversa(): void
    {
        $banco = $this->criarBanco();
        $this->migration('2026_11_15_100000_a', 'CREATE TABLE a (id int PRIMARY KEY)', 'DROP TABLE a');
        // b depende de a: se o rollback não fosse na ordem inversa, o DROP de a falharia.
        $this->migration('2026_11_15_110000_b', 'CREATE TABLE b (id int REFERENCES a (id))', 'DROP TABLE b');
        $this->manager($banco)->runMigrations();

        $this->manager($banco)->rollbackMigrations(2);

        $this->assertFalse($this->tabelaExiste($banco, 'a'));
        $this->assertFalse($this->tabelaExiste($banco, 'b'));
        $this->assertSame([], $this->executadas($banco));
    }

    public function test_ignora_arquivos_fora_dos_dois_formatos(): void
    {
        $banco = $this->criarBanco();
        file_put_contents($this->dir . '/rascunho.php', '<?php throw new \Exception("não deveria ser carregado");');
        $nome = $this->migration('2026_11_15_100000_a', 'CREATE TABLE a (id int)');

        $this->assertSame([$nome], $this->manager($banco)->disponiveis());
        $this->assertSame([$nome], $this->manager($banco)->runMigrations());
    }
}
