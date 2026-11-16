<?php

namespace Framework\Tests\Integration\Migrations;

use Framework\Infrastructure\CLI\bin\MigrateCLI;

final class MigrateCLITest extends MigrationsTestCase
{
    private function cli(array $bancos, string ...$args): int
    {
        $alvos = [];
        foreach ($bancos as $banco) {
            $alvos[$banco] = fn() => $this->storage($banco);
        }

        ob_start();
        try {
            return (new MigrateCLI($this->dir, $alvos, ['migrate', ...$args]))->execute();
        } finally {
            $this->saida = ob_get_clean();
        }
    }

    private string $saida = '';

    public function test_run_sai_com_zero_quando_todos_os_bancos_migram(): void
    {
        $a = $this->criarBanco();
        $b = $this->criarBanco();
        $this->migration('2026_11_15_100000_t', 'CREATE TABLE t (id int)');

        $this->assertSame(0, $this->cli([$a, $b], 'run'));
        $this->assertStringContainsString('2 banco(s): 2 ok, 0 com falha.', $this->saida);
    }

    public function test_run_sai_com_um_quando_algum_banco_falha(): void
    {
        $a = $this->criarBanco();
        $quebrado = $this->criarBanco();
        $this->pdo($quebrado)->exec('CREATE TABLE t (id int)');
        $this->migration('2026_11_15_100000_t', 'CREATE TABLE t (id int)');

        $this->assertSame(1, $this->cli([$a, $quebrado], 'run'));
        $this->assertStringContainsString("{$quebrado}:", $this->saida);
    }

    public function test_run_com_tenant_migra_so_o_banco_pedido(): void
    {
        $a = $this->criarBanco();
        $b = $this->criarBanco();
        $this->migration('2026_11_15_100000_t', 'CREATE TABLE t (id int)');

        $this->assertSame(0, $this->cli([$a, $b], 'run', "--tenant={$a}"));
        $this->assertTrue($this->tabelaExiste($a, 't'));
        $this->assertFalse($this->tabelaExiste($b, 't'));
    }

    public function test_status_sai_com_um_enquanto_houver_pendencia(): void
    {
        $a = $this->criarBanco();
        $this->migration('2026_11_15_100000_t', 'CREATE TABLE t (id int)');

        $this->assertSame(1, $this->cli([$a], 'status'));
        $this->assertStringContainsString('1 pendente(s)', $this->saida);

        $this->cli([$a], 'run');

        $this->assertSame(0, $this->cli([$a], 'status'));
        $this->assertStringContainsString('em dia', $this->saida);
    }

    public function test_down_exige_tenant_quando_ha_mais_de_um_banco(): void
    {
        $a = $this->criarBanco();
        $b = $this->criarBanco();
        $nome = $this->migration('2026_11_15_100000_t', 'CREATE TABLE t (id int)', 'DROP TABLE t');
        $this->cli([$a, $b], 'run');

        $this->assertSame(1, $this->cli([$a, $b], 'down', $nome));
        $this->assertTrue($this->tabelaExiste($a, 't'));

        $this->assertSame(0, $this->cli([$a, $b], 'down', $nome, "--tenant={$a}"));
        $this->assertFalse($this->tabelaExiste($a, 't'));
        $this->assertTrue($this->tabelaExiste($b, 't'));
    }

    public function test_make_cria_o_arquivo(): void
    {
        $this->assertSame(0, $this->cli([], 'make', 'cria', 'tabela', 'x'));
        $this->assertCount(1, glob($this->dir . '/*_cria_tabela_x.php'));
    }
}
