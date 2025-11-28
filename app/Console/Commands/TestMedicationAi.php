<?php

namespace App\Console\Commands;

use App\Services\MedicationInfoService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use JsonException;

class TestMedicationAi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'medications:test
        {name : Nome do medicamento a ser consultado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta a IA para obter dados de bulas e validar a integração.';

    public function __construct(private readonly MedicationInfoService $service)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = (string) $this->argument('name');
        $this->components->info("Consultando IA para: {$name}");

        $result = $this->service->fetch($name);

        if ($result['status'] === 'error') {
            $this->components->error($result['message'] ?? 'Consulta falhou');

            return self::FAILURE;
        }

        if ($result['status'] === 'missing') {
            $this->components->warn($result['message'] ?? 'Medicamento não encontrado.');

            return self::SUCCESS;
        }

        $medication = $result['medication'];
        $fromCache = $result['from_cache'] ? 'sim' : 'não';

        $this->components->twoColumnDetail('Cache hit', $fromCache);
        $this->components->twoColumnDetail('Nome', $medication->name);
        $this->components->twoColumnDetail('Slug', $medication->slug);

        $this->newLine();
        $this->line('<fg=yellow>Resumo humanizado:</>');
        $this->line($medication->human_summary ?? 'N/D');

        $this->newLine();
        $this->line('<fg=yellow>Posologia:</>');
        $this->line($medication->posology ?? 'N/D');

        $this->newLine();
        $this->line('<fg=yellow>Indicações:</>');
        $this->line($medication->indications ?? 'N/D');

        $this->newLine();
        $this->line('<fg=yellow>Contraindicações:</>');
        $this->line($medication->contraindications ?? 'N/D');

        if ($medication->interaction_alerts) {
            $this->newLine();
            $this->line('<fg=yellow>Interações:</>');
            $this->line($medication->interaction_alerts);
        }

        if ($medication->composition) {
            $this->newLine();
            $this->table(
                ['Componente', 'Forma', 'Força', 'Meia-vida', 'Observações'],
                collect($medication->composition)->map(function (array $item) {
                    return [
                        $item['component'] ?? 'N/D',
                        $item['dosage_form'] ?? 'N/D',
                        $item['strength'] ?? 'N/D',
                        $item['half_life_hours'] ?? 'N/D',
                        $item['notes'] ?? '',
                    ];
                })
            );
        }

        $this->newLine();
        $this->line('<fg=yellow>Disclaimer:</>');
        $this->line($medication->disclaimer ?? 'Este sistema não substitui avaliação médica.');

        if (! empty($result['response']['usage'] ?? null)) {
            $usage = $result['response']['usage'];
            $this->newLine();
            $this->components->twoColumnDetail('Tokens prompt', (string) ($usage['prompt_tokens'] ?? 'N/D'));
            $this->components->twoColumnDetail('Tokens completion', (string) ($usage['completion_tokens'] ?? 'N/D'));
            $this->components->twoColumnDetail('Tokens totais', (string) ($usage['total_tokens'] ?? 'N/D'));
        }

        return self::SUCCESS;
    }
}
