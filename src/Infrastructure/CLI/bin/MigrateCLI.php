<?php

namespace Framework\Infrastructure\CLI\bin;

use Framework\Core\Main;
use Framework\Infrastructure\DB\Migrations\MigrationCreator;
use Framework\Infrastructure\DB\Migrations\MigrationOrchestrator;
use Framework\Infrastructure\DB\Migrations\PdoMigrationManager;

/**
 * Linha de comando das migrations.
 *
 *   make <nome>                          cria o arquivo de uma migration nova
 *   run [--tenant=<nome>]                executa as pendentes em todos os bancos (ou em um)
 *   status [--tenant=<nome>]             lista as pendentes de cada banco
 *   up <migration> --tenant=<nome>       executa uma migration em um banco
 *   down <migration> --tenant=<nome>     reverte uma migration em um banco
 *   rollback [passos] --tenant=<nome>    reverte as últimas migrations de um banco
 *
 * execute() devolve o código de saída: 0 quando tudo deu certo, 1 quando algum banco falhou.
 *
 * @author Iago Oliveira <prog.iago.oliveira@gmail.com>
 * @since 15/06/2025
 */
class MigrateCLI
{
    protected $migrationsDir;
    /** @var array<string, callable> nome do banco => como conectar nele */
    protected array $alvos;
    protected array $args = [];
    protected array $opcoes = [];

    /**
     * @param array<string, callable>|null $alvos bancos a migrar; sem isso, usa só a conexão atual do framework
     * @param string[]|null $argv argumentos da linha de comando; por padrão os do processo
     */
    public function __construct(string $migrationsDir, ?array $alvos = null, ?array $argv = null)
    {
        $this->migrationsDir = $migrationsDir;
        $this->alvos = $alvos ?? ['default' => static fn() => Main::getPdoStorage()];

        foreach (array_slice($argv ?? $GLOBALS['argv'] ?? [], 1) as $arg) {
            if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m)) {
                $this->opcoes[$m[1]] = $m[2] ?? true;
            } else {
                $this->args[] = $arg;
            }
        }
    }

    public function execute(): int
    {
        $comando = $this->args[0] ?? null;

        try {
            switch ($comando) {
                case 'make':
                    return $this->make();
                case 'run':
                    return $this->run();
                case 'status':
                    return $this->status();
                case 'up':
                case 'down':
                    if (!isset($this->args[1])) {
                        return $this->erro('Nome da migration não fornecido.');
                    }
                    $this->managerDoAlvo()->{$comando}($this->args[1]);
                    return 0;
                case 'rollback':
                    $this->managerDoAlvo()->rollbackMigrations(isset($this->args[1]) ? (int) $this->args[1] : 1);
                    return 0;
                case null:
                    $this->uso();
                    return 1;
                default:
                    return $this->erro("Comando desconhecido: {$comando}");
            }
        } catch (\Throwable $e) {
            return $this->erro($e->getMessage());
        }
    }

    protected function make(): int
    {
        if (!isset($this->args[1])) {
            return $this->erro('Informe o nome: make <nome>');
        }

        $caminho = (new MigrationCreator($this->migrationsDir))->criar(implode('_', array_slice($this->args, 1)));
        echo "Migration criada: {$caminho}\n";

        return 0;
    }

    protected function run(): int
    {
        $relatorio = (new MigrationOrchestrator($this->alvosSelecionados(), $this->migrationsDir))->executar();

        echo "\n" . implode("\n", $relatorio->resumo()) . "\n";

        return $relatorio->sucesso() ? 0 : 1;
    }

    protected function status(): int
    {
        $status = (new MigrationOrchestrator($this->alvosSelecionados(), $this->migrationsDir))->status();
        $comProblema = 0;

        foreach ($status as $alvo => $pendentes) {
            if (is_string($pendentes)) {
                $comProblema++;
                echo "{$alvo}: ERRO ao consultar: {$pendentes}\n";
            } elseif ($pendentes === []) {
                echo "{$alvo}: em dia\n";
            } else {
                $comProblema++;
                echo "{$alvo}: " . count($pendentes) . " pendente(s)\n";
                foreach ($pendentes as $migration) {
                    echo "    {$migration}\n";
                }
            }
        }

        echo "\n" . count($status) . " banco(s), {$comProblema} com pendência ou erro.\n";

        return $comProblema === 0 ? 0 : 1;
    }

    /**
     * @return array<string, callable>
     */
    protected function alvosSelecionados(): array
    {
        $tenant = $this->opcoes['tenant'] ?? null;

        if ($tenant === null) {
            return $this->alvos;
        }

        if (!is_string($tenant) || !isset($this->alvos[$tenant])) {
            throw new \InvalidArgumentException('Banco não encontrado: ' . (is_string($tenant) ? $tenant : '(vazio)'));
        }

        return [$tenant => $this->alvos[$tenant]];
    }

    /**
     * up, down e rollback agem em um banco só: com vários cadastrados, --tenant é obrigatório.
     */
    protected function managerDoAlvo(): PdoMigrationManager
    {
        $alvos = $this->alvosSelecionados();

        if (count($alvos) !== 1) {
            throw new \InvalidArgumentException('Este comando age em um banco só: informe --tenant=<nome>.');
        }

        return new PdoMigrationManager(reset($alvos)(), $this->migrationsDir);
    }

    protected function erro(string $mensagem): int
    {
        fwrite(STDERR, "Erro: {$mensagem}\n");

        return 1;
    }

    protected function uso(): void
    {
        echo "Uso: migrate <comando> [argumentos]\n";
        echo "Comandos disponíveis:\n";
        echo "  make <nome>                        Cria o arquivo de uma migration nova.\n";
        echo "  run [--tenant=<nome>]              Executa as migrations pendentes em todos os bancos (ou em um).\n";
        echo "  status [--tenant=<nome>]           Lista as migrations pendentes de cada banco.\n";
        echo "  up <migration> --tenant=<nome>     Executa o 'up' de uma migration em um banco.\n";
        echo "  down <migration> --tenant=<nome>   Executa o 'down' de uma migration em um banco.\n";
        echo "  rollback [passos] --tenant=<nome>  Reverte as últimas migrations de um banco.\n";
    }
}
