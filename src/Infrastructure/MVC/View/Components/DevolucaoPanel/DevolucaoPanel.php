<?php

namespace Framework\Infrastructure\MVC\View\Components\DevolucaoPanel;

use Framework\Infrastructure\MVC\View\Components\IComponent;

/**
 * Painel de devolução de pedido: itens do pedido à esquerda (2/3) e, à direita (1/3), pedido, dados da
 * devolução, forma de reembolso e prévia. A prévia e a confirmação são feitas pelo JS nas rotas informadas.
 */
class DevolucaoPanel implements IComponent
{
    private ?array $pedido = null;
    private array $itens = [];
    private array $formas = [];
    private array $routes = [];
    private ?string $dataDevolucao = null;

    public function getName(): string
    {
        return 'DevolucaoPanelComponent';
    }

    /**
     * @param array{id: int, cliente: string, documento?: string|null, tipo: string, data?: string|null}|null $pedido
     */
    public function setPedido(?array $pedido): self
    {
        $this->pedido = $pedido;
        return $this;
    }

    /**
     * @param array<int, array{id: int, codigo: string, descricao: string, unidade: string, vendido: float, devolvido: float, valorUnitario: float}> $itens
     */
    public function setItens(array $itens): self
    {
        $this->itens = $itens;
        return $this;
    }

    /**
     * @param array<int, array{forma: int, titulo: string, descricao: string, icone: string}> $formas
     */
    public function setFormas(array $formas): self
    {
        $this->formas = $formas;
        return $this;
    }

    /**
     * @param array<string, string> $routes nome lógico => rota (ex.: simular, confirmar, carregarPedido)
     */
    public function setRoutes(array $routes): self
    {
        $this->routes = $routes;
        return $this;
    }

    public function setDataDevolucao(?string $dataDevolucao): self
    {
        $this->dataDevolucao = $dataDevolucao;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'component' => 'DevolucaoPanelComponent',
            'DevolucaoPanelComponent' => [
                'pedido' => $this->pedido,
                'itens' => $this->itens,
                'formas' => $this->formas,
                'routes' => $this->routes,
                'dataDevolucao' => $this->dataDevolucao,
            ],
        ];
    }
}
