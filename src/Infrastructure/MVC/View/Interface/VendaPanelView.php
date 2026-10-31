<?php

namespace Framework\Infrastructure\MVC\View\Interface;

use Framework\Infrastructure\MVC\View\Components\VendaPanel\VendaPanel;

abstract class VendaPanelView extends View
{
    protected function instanciaViewComponent(): void
    {
        $this->setViewComponent(new VendaPanel());
    }

    protected function getPanel(): VendaPanel
    {
        return $this->getViewComponent();
    }

    public function render($aData = []): void
    {
        $window = [
            'window' => [
                'title' => $this->getTitulo(),
                'route' => $this->getRota(),
                'fullscreen' => true,
            ],
        ];

        $component = array_merge($window, $this->getViewComponent()->toArray());
        echo json_encode($component);
    }
}
