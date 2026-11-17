<?php

namespace Framework\Infrastructure\MVC\View\Components\Bi;

/**
 * Gráfico Chart.js.
 * Dados esperados:
 * `{ labels: string[], series: [{ name, label, data: number[], cor?, tipo? }], anotacoes?: [{ eixo: 'x'|'y', valor, label }] }`
 * `tipo` por série permite misturar barra e linha. Anotações desenham linhas de referência (ex.: ponto de equilíbrio).
 */
class BiGrafico extends BiWidget
{
    const TIPO_BARRA = 'bar',
          TIPO_LINHA = 'line',
          TIPO_PIZZA = 'pie',
          TIPO_ROSCA = 'doughnut';

    private bool $empilhado = false;
    private bool $horizontal = false;

    public function __construct(string $name, string $titulo, private string $grafico = self::TIPO_BARRA, private string $formato = BiPainel::FORMATO_MOEDA)
    {
        parent::__construct($name, $titulo);
    }

    public function setEmpilhado(bool $empilhado): self
    {
        $this->empilhado = $empilhado;
        return $this;
    }

    public function setHorizontal(bool $horizontal): self
    {
        $this->horizontal = $horizontal;
        return $this;
    }

    protected function tipo(): string
    {
        return 'grafico';
    }

    protected function config(): array
    {
        return [
            'grafico' => $this->grafico,
            'formato' => $this->formato,
            'empilhado' => $this->empilhado,
            'horizontal' => $this->horizontal,
        ];
    }
}
