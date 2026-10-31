<?php

namespace Framework\Infrastructure\MVC\View\Components\VendaPanel;

use Framework\Infrastructure\MVC\View\Components\IComponent;

/**
 * Painel da tela Vender: itens à esquerda e, à direita, totais, crédito, dinheiro/troco, parcelas e os desfechos
 * (pendente, concluído, NFC-e, NF-e). Buscas, prévia e finalização são feitas pelo JS nas rotas informadas.
 */
class VendaPanel implements IComponent
{
    private array $dados = [];
    private array $routes = [];

    public function getName(): string
    {
        return 'VendaPanelComponent';
    }

    /** @param array<string, mixed> $dados estado inicial (defaults, listas pequenas, pedido existente, permissões) */
    public function setDados(array $dados): self
    {
        $this->dados = $dados;
        return $this;
    }

    /** @param array<string, string> $routes nome lógico => rota (ex.: simular, finalizar, buscarProdutos) */
    public function setRoutes(array $routes): self
    {
        $this->routes = $routes;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'component' => 'VendaPanelComponent',
            'VendaPanelComponent' => [
                'dados' => $this->dados,
                'routes' => $this->routes,
            ],
        ];
    }
}
