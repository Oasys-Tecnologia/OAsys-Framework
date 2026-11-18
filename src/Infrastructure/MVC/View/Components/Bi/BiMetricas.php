<?php

namespace Framework\Infrastructure\MVC\View\Components\Bi;

/**
 * Linha de cards de métrica.
 * Dados esperados: `{ <nome do card>: number|string|null }`.
 */
class BiMetricas extends BiWidget
{
    private array $cards = [];

    public function addCard(string $name, string $label, string $formato = BiPainel::FORMATO_MOEDA, string $cor = 'var(--oasys-primary)'): self
    {
        $this->cards[] = compact('name', 'label', 'formato', 'cor');
        return $this;
    }

    protected function tipo(): string
    {
        return 'metricas';
    }

    protected function config(): array
    {
        return ['cards' => $this->cards];
    }
}
