<?php

namespace Framework\Tests\Integration\Migrations;

use Framework\Infrastructure\DB\Migrations\MigrationCreator;
use Framework\Infrastructure\DB\Migrations\PdoMigrationManager;

final class MigrationCreatorTest extends MigrationsTestCase
{
    public function test_cria_arquivo_com_data_e_nome_que_o_manager_executa(): void
    {
        $banco = $this->criarBanco();
        $agora = new \DateTimeImmutable('2026-11-15 14:30:00');

        $caminho = (new MigrationCreator($this->dir))->criar('Cria Tabela de Cobrança ' . bin2hex(random_bytes(3)), $agora);

        $nome = basename($caminho, '.php');
        $this->assertMatchesRegularExpression('/^2026_11_15_143000_cria_tabela_de_cobranca_[a-f0-9]{6}$/', $nome);
        $this->assertTrue(PdoMigrationManager::nomeValido($nome));

        // O modelo gerado vem com SQL vazio; trocamos por um comando real para provar que a classe carrega.
        file_put_contents($caminho, str_replace('exec("")', 'exec("SELECT 1")', file_get_contents($caminho)));
        $manager = new PdoMigrationManager($this->storage($banco), $this->dir, static fn() => null);

        $this->assertSame([$nome], $manager->runMigrations());
    }

    public function test_recusa_nome_vazio_e_arquivo_repetido(): void
    {
        $criador = new MigrationCreator($this->dir);
        $agora = new \DateTimeImmutable('2026-11-15 14:30:00');
        $criador->criar('repetida', $agora);

        try {
            $criador->criar('repetida', $agora);
            $this->fail('Esperava recusa do arquivo repetido.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Já existe', $e->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        $criador->criar('!!!');
    }
}
