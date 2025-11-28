<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

class PrescriptionParserService
{
    public function __construct(private readonly GoogleVisionOcrService $vision)
    {
    }

    /**
     * Attempt to extract structured data from a prescription image using local OCR.
     *
     * @return array{status:string,text:?string,structured:?array,message:?string}
     */
    public function extract(string $path): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException("Arquivo {$path} não encontrado para OCR.");
        }

        // Prioritize Google Vision to avoid delays with OCR local
        $cloudResult = $this->attemptCloudOcr($path);

        if ($cloudResult !== null) {
            return $cloudResult;
        }

        // Só tenta OCR local se o Google Vision não estiver habilitado
        $manualReviewMessage = null;

        if (! $this->vision->isEnabled()) {
            if ($this->tesseractAvailable()) {
                try {
                    $text = $this->runTesseract($path);

                    if ($text) {
                        $structured = $this->basicParser($text);

                        return [
                            'status' => $structured ? 'parsed' : 'text_extracted',
                            'text' => $text,
                            'structured' => $structured,
                            'message' => $structured ? null : 'Texto extraído, aguardando enriquecimento.',
                        ];
                    }

                    $manualReviewMessage = 'OCR local não obteve resultado legível.';
                } catch (Throwable $exception) {
                    Log::warning('Falha ao executar OCR local.', [
                        'path' => $path,
                        'exception' => $exception->getMessage(),
                    ]);

                    $manualReviewMessage = 'Erro ao processar OCR local.';
                }
            } else {
                $manualReviewMessage = 'OCR local indisponível. Necessário lançamento manual.';
            }
        }

        $this->logCloudFallbackDisabled();

        return [
            'status' => 'manual_review',
            'text' => null,
            'structured' => null,
            'message' => $manualReviewMessage ?? 'Não foi possível interpretar a receita automaticamente.',
        ];
    }

    protected function tesseractAvailable(): bool
    {
        static $cached;

        if ($cached !== null) {
            return $cached;
        }

        $process = new Process(['which', 'tesseract']);
        $process->run();

        return $cached = $process->isSuccessful();
    }

    protected function runTesseract(string $path): ?string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ocr_');
        $outputBase = $tempFile ?: sys_get_temp_dir().'/ocr_'.uniqid();

        $process = new Process(['tesseract', $path, $outputBase, '-l', 'por']);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $outputPath = $outputBase.'.txt';
        $contents = File::exists($outputPath) ? File::get($outputPath) : null;

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        if ($tempFile && File::exists($tempFile)) {
            File::delete($tempFile);
        }

        return $contents ? trim($contents) : null;
    }

    protected function attemptCloudOcr(string $path): ?array
    {
        if (! $this->vision->isEnabled()) {
            return null;
        }

        try {
            $result = $this->vision->extractText($path);
            $text = $result['text'] ?? null;

            if (! $text) {
                return [
                    'status' => 'manual_review',
                    'text' => null,
                    'structured' => null,
                    'message' => 'Google Vision não retornou texto para esta imagem.',
                ];
            }

            $structured = $this->basicParser($text);

            return [
                'status' => $structured ? 'parsed' : 'text_extracted',
                'text' => $text,
                'structured' => $structured,
                'message' => $structured ? null : 'Texto extraído via Google Vision. Aguardando enriquecimento.',
            ];
        } catch (Throwable $exception) {
            Log::warning('Falha ao executar OCR via Google Vision.', [
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'text' => null,
                'structured' => null,
                'message' => $this->friendlyVisionMessage($exception->getMessage()),
            ];
        }
    }

    protected function friendlyVisionMessage(string $error): string
    {
        $normalized = strtoupper($error);

        if (str_contains($normalized, 'BILLING_DISABLED')) {
            return 'Google Vision sem cobrança habilitada. Ative o billing do projeto ou use outra chave.';
        }

        if (str_contains($normalized, 'API_KEY_SERVICE_BLOCKED')) {
            return 'Chave do Google Vision bloqueada para este serviço. Confirme permissões da API ou troque a chave.';
        }

        if (str_contains($normalized, 'PERMISSION_DENIED')) {
            return 'Permissão negada no Google Vision. Revise billing e escopos da chave.';
        }

        return 'Falha ao consultar o Google Vision. Verifique a chave e billing.';
    }

    protected function logCloudFallbackDisabled(): void
    {
        if ($this->vision->isEnabled()) {
            return;
        }

        Log::info('Google Vision não configurado. Mantendo receita para revisão manual.');
    }

    /**
     * Very basic parser that looks for medication lines in the extracted text.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function basicParser(string $text): ?array
    {
        $lines = collect(preg_split('/\r?\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            return null;
        }

        $items = [];

        foreach ($lines as $line) {
            if (! preg_match('/(?P<name>[A-Za-zÀ-ÖØ-öø-ÿ0-9\s\-]+)\s+(?P<dosage>\d+(?:mg|g|ml|mcg))/u', $line, $matches)) {
                continue;
            }

            $items[] = [
                'medication_name' => trim($matches['name']),
                'dosage' => trim($matches['dosage']),
                'instructions' => $line,
            ];
        }

        return empty($items) ? null : $items;
    }
}
