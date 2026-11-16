<?php

namespace Framework\Infrastructure\DB\Migrations;

/**
 * Cria o arquivo de uma migration nova no formato AAAA_MM_DD_HHMMSS_nome.php.
 * A data no nome evita o conflito de numeração entre branches que o sequencial causava.
 */
final class MigrationCreator
{
    public function __construct(private string $migrationsDir)
    {
        $this->migrationsDir = rtrim($migrationsDir, '/');
    }

    /**
     * @return string caminho do arquivo criado
     */
    public function criar(string $descricao, ?\DateTimeImmutable $agora = null): string
    {
        $slug = self::slug($descricao);

        if ($slug === '') {
            throw new \InvalidArgumentException('Informe um nome para a migration (letras, números e _).');
        }

        $nome = ($agora ?? new \DateTimeImmutable())->format('Y_m_d_His') . '_' . $slug;
        $caminho = $this->migrationsDir . '/' . $nome . '.php';

        if (file_exists($caminho)) {
            throw new \RuntimeException("Já existe a migration '{$nome}'.");
        }

        $classe = PdoMigrationManager::getMigrationClassName($nome);

        $conteudo = <<<PHP
<?php

use Framework\Infrastructure\DB\Migrations\StorageMigration;

class {$classe} extends StorageMigration
{
    function up()
    {
        \$this->oStorage->exec("");
    }

    function down()
    {
        \$this->oStorage->exec("");
    }
}

PHP;

        if (file_put_contents($caminho, $conteudo) === false) {
            throw new \RuntimeException("Não foi possível escrever '{$caminho}'.");
        }

        return $caminho;
    }

    private static function slug(string $descricao): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $descricao) ?: $descricao;
        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($ascii));

        return trim($slug, '_');
    }
}
