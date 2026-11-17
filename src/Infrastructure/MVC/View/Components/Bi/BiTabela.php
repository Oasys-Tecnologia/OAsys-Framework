<?php

namespace Framework\Infrastructure\MVC\View\Components\Bi;

/**
 * Tabela com linhas hierárquicas (expansíveis), linha de total e exportação CSV.
 * Dados esperados:
 * `{ linhas: [{ <coluna>: valor, _filhos?: linha[], _destaque?: bool }], total?: { <coluna>: valor } }`
 */
class BiTabela extends BiWidget
{
    private array $colunas = [];
    private bool $exportavel = true;
    private bool $expandida = false;

    public function addColuna(string $name, string $label, string $formato = BiPainel::FORMATO_TEXTO): self
    {
        $this->colunas[] = compact('name', 'label', 'formato');
        return $this;
    }

    public function setExportavel(bool $exportavel): self
    {
        $this->exportavel = $exportavel;
        return $this;
    }

    /** Abre todas as linhas filhas ao carregar. */
    public function setExpandida(bool $expandida): self
    {
        $this->expandida = $expandida;
        return $this;
    }

    protected function tipo(): string
    {
        return 'tabela';
    }

    protected function config(): array
    {
        return [
            'colunas' => $this->colunas,
            'exportavel' => $this->exportavel,
            'expandida' => $this->expandida,
        ];
    }
}
