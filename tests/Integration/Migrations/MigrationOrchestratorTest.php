<?php

namespace Framework\Tests\Integration\Migrations;

use Framework\Infrastructure\DB\Migrations\MigrationOrchestrator;

final class MigrationOrchestratorTest extends MigrationsTestCase
{
    private function orquestrador(array $bancos): MigrationOrchestrator
    {
        $alvos = [];
        foreach ($bancos as $banco) {
            $alvos[$banco] = fn() => $this->storage($banco);
        }

        return new MigrationOrchestrator($alvos, $this->dir, static fn() => null);
    }

    public function test_aplica_as_pendentes_em_todos_os_bancos(): void
    {
        $a = $this->criarBanco();
        $b = $this->criarBanco();
        $nome = $this->migration('2026_11_15_100000_t', 'CREATE TABLE t (id int)');

        $relatorio = $this->orquestrador([$a, $b])->executar();

        $this->assertTrue($relatorio->sucesso());
        $this->assertSame([$nome], $relatorio->executadas($a));
        $this->assertSame([$nome], $relatorio->executadas($b));
        $this->assertTrue($this->tabelaExiste($a, 't'));
        $this->assertTrue($this->tabelaExiste($b, 't'));
    }

    public function test_falha_em_um_banco_nao_impede_os_outros_e_aparece_no_relatorio(): void
    {
        $a = $this->criarBanco();
        $quebrado = $this->criarBanco();
        $c = $this->criarBanco();
        // Só no banco "quebrado" a tabela já existe, então só nele a migration falha.
        $this->pdo($quebrado)->exec('CREATE TABLE t (id int)');
        $primeira = $this->migration('2026_11_15_090000_antes', 'CREATE TABLE antes (id int)');
        $this->migration('2026_11_15_100000_t', 'CREATE TABLE t (id int)');

        $relatorio = $this->orquestrador([$a, $quebrado, $c])->executar();

        $this->assertFalse($relatorio->sucesso());
        $this->assertSame([$quebrado], array_keys($relatorio->falhas()));
        $this->assertSame([$primeira], $relatorio->executadas($quebrado));
        $this->assertTrue($this->tabelaExiste($a, 't'));
        $this->assertTrue($this->tabelaExiste($c, 't'));
        $this->assertStringContainsString('1 com falha', $relatorio->resumo()[0]);
    }

    public function test_banco_inacessivel_vira_falha_no_relatorio(): void
    {
        $a = $this->criarBanco();
        $this->migration('2026_11_15_100000_t', 'CREATE TABLE t (id int)');

        $orquestrador = new MigrationOrchestrator([
            'fora_do_ar' => fn() => throw new \RuntimeException('sem conexão'),
            $a => fn() => $this->storage($a),
        ], $this->dir, static fn() => null);

        $relatorio = $orquestrador->executar();

        $this->assertSame(['fora_do_ar' => 'sem conexão'], $relatorio->falhas());
        $this->assertTrue($this->tabelaExiste($a, 't'));
    }

    public function test_status_lista_as_pendentes_de_cada_banco_sem_executar(): void
    {
        $emDia = $this->criarBanco();
        $atrasado = $this->criarBanco();
        $this->migration('2026_11_15_100000_a', 'CREATE TABLE a (id int)');
        $this->orquestrador([$emDia])->executar();
        $nova = $this->migration('2026_11_16_100000_b', 'CREATE TABLE b (id int)');
        $this->orquestrador([$emDia])->executar();

        $status = $this->orquestrador([$emDia, $atrasado])->status();

        $this->assertSame([], $status[$emDia]);
        $this->assertCount(2, $status[$atrasado]);
        $this->assertContains($nova, $status[$atrasado]);
        $this->assertFalse($this->tabelaExiste($atrasado, 'a'));
    }
}
