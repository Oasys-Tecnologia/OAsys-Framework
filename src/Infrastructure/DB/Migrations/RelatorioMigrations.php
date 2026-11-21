<?php

namespace Framework\Infrastructure\DB\Migrations;

/**
 * Resultado de uma execução de migrations em vários bancos.
 */
final class RelatorioMigrations
{
    /** @var array<string, array{executadas: string[], erro: ?string}> */
    private array $alvos = [];

    public function registrar(string $alvo, array $executadas, ?string $erro = null): void
    {
        $this->alvos[$alvo] = ['executadas' => $executadas, 'erro' => $erro];
    }

    public function sucesso(): bool
    {
        return $this->falhas() === [];
    }

    /**
     * @return array<string, string> alvo => erro
     */
    public function falhas(): array
    {
        return array_filter(array_map(static fn(array $alvo) => $alvo['erro'], $this->alvos), static fn($erro) => $erro !== null);
    }

    /**
     * @return string[] migrations executadas no alvo
     */
    public function executadas(string $alvo): array
    {
        return $this->alvos[$alvo]['executadas'] ?? [];
    }

    /**
     * @return string[] linhas do resumo final
     */
    public function resumo(): array
    {
        $falhas = $this->falhas();
        $total = count($this->alvos);
        $linhas = [sprintf('%d banco(s): %d ok, %d com falha.', $total, $total - count($falhas), count($falhas))];

        foreach ($falhas as $alvo => $erro) {
            $linhas[] = "  {$alvo}: {$erro}";
        }

        return $linhas;
    }
}
