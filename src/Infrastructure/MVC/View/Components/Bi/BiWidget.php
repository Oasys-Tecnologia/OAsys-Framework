<?php

namespace Framework\Infrastructure\MVC\View\Components\Bi;

abstract class BiWidget
{
    /** Ocupa a linha inteira (true) ou metade dela (false). */
    protected bool $larguraTotal = true;

    public function __construct(protected string $name, protected string $titulo = '')
    {
    }

    public function setLarguraTotal(bool $larguraTotal): static
    {
        $this->larguraTotal = $larguraTotal;
        return $this;
    }

    abstract protected function tipo(): string;

    protected function config(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo(),
            'name' => $this->name,
            'titulo' => $this->titulo,
            'larguraTotal' => $this->larguraTotal,
        ] + $this->config();
    }
}
