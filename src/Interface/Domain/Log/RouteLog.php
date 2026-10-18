<?php

namespace Framework\Interface\Domain\Log;

use Framework\Infrastructure\MVC\Model\Model;

class RouteLog extends Model
{
    protected ?int $id;
    protected $usuario;
    protected $rota;
    protected ?string $data;
    protected ?int $duracaoMs = null;

    public function __construct(?int $usuarioId = null, ?int $rotaId = null, ?int $duracaoMs = null)
    {
        parent::__construct();
        $this->usuario = $usuarioId;
        $this->rota = $rotaId;
        $this->data = date('Y-m-d H:i:s');
        $this->duracaoMs = $duracaoMs;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsuario()
    {
        return $this->usuario;
    }

    public function setUsuario($usuario): void
    {
        $this->usuario = $usuario;
    }

    public function getRota()
    {
        return $this->rota;
    }

    public function setRota($rota): void
    {
        $this->rota = $rota;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): void
    {
        $this->data = $data;
    }

    public function getDuracaoMs(): ?int
    {
        return $this->duracaoMs;
    }

    public function setDuracaoMs(?int $duracaoMs): void
    {
        $this->duracaoMs = $duracaoMs;
    }
}
