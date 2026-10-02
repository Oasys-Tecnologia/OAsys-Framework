<?php

namespace Framework\Infrastructure\MVC\View\Interface;

use Framework\Infrastructure\MVC\View\Components\DevolucaoPanel\DevolucaoPanel;

abstract class DevolucaoPanelView extends View
{
    protected function instanciaViewComponent(): void
    {
        $this->setViewComponent(new DevolucaoPanel());
    }

    protected function getPanel(): DevolucaoPanel
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
