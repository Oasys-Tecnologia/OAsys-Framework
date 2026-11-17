<?php

namespace Framework\Infrastructure\MVC\View\Components\Bi;

use Framework\Infrastructure\MVC\View\Components\IComponent;

/**
 * Painel de BI (backend do BiPainelComponent.js).
 *
 * Uma única rota alimenta todos os widgets: ela recebe os filtros (POST JSON) e
 * responde `{ <nome do widget>: <dados do widget> }`. Formato dos dados de cada
 * widget está documentado em BiMetricas, BiGrafico e BiTabela.
 */
class BiPainel implements IComponent
{
    const FORMATO_MOEDA = 'moeda',
          FORMATO_NUMERO = 'numero',
          FORMATO_PERCENTUAL = 'percentual',
          FORMATO_TEXTO = 'texto',
          FORMATO_DATA = 'data';

    /** @var BiWidget[] */
    private array $widgets = [];
    private ?string $route = null;
    private ?string $lazyCall = null;

    public function __construct(private string $name, private string $titulo = '')
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** Rota que devolve os dados de todos os widgets. */
    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Evento JS que recarrega o painel: document.dispatchEvent(new CustomEvent(<lazyCall>, {detail: {filters}})).
     * Sem lazyCall o painel carrega sozinho ao abrir, sem filtros.
     */
    public function setLazyCall(string $lazyCall): self
    {
        $this->lazyCall = $lazyCall;
        return $this;
    }

    public function addWidget(BiWidget $widget): self
    {
        $this->widgets[] = $widget;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'component' => 'BiPainelComponent',
            'BiPainelComponent' => [
                'name' => $this->name,
                'titulo' => $this->titulo,
                'route' => $this->route,
                'lazyCall' => $this->lazyCall,
                'widgets' => array_map(fn(BiWidget $w) => $w->toArray(), $this->widgets),
            ],
        ];
    }
}
