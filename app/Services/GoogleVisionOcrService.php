<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GoogleVisionOcrService
{
    private array $config;

    public function __construct(private readonly HttpFactory $http)
    {
        $this->config = config('services.google_vision', []);
    }

    public function isEnabled(): bool
    {
        return ! empty($this->config['api_key']);
    }

    /**
     * Perform OCR using Google Cloud Vision.
     *
     * @return array{text:?string,locale:?string}
     */
    public function extractText(string $path): array
    {
        if (! $this->isEnabled()) {
            return [
                'text' => null,
                'locale' => null,
            ];
        }

        if (! File::exists($path)) {
            throw new RuntimeException("Arquivo {$path} não encontrado para OCR.");
        }

        $binary = File::get($path);

        if ($binary === false) {
            throw new RuntimeException('Falha ao ler arquivo para envio ao Google Vision.');
        }

        $payload = [
            'requests' => [
                [
                    'image' => [
                        'content' => base64_encode($binary),
                    ],
                    'features' => [
                        [
                            'type' => $this->config['feature'] ?? 'DOCUMENT_TEXT_DETECTION',
                            'maxResults' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->sendRequest($payload);

        $annotation = $response['responses'][0]['fullTextAnnotation'] ?? null;
        $text = trim((string) ($annotation['text'] ?? ''));

        return [
            'text' => $text === '' ? null : $text,
            'locale' => $annotation['locale'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    protected function sendRequest(array $payload): array
    {
        $endpoint = $this->config['endpoint'] ?? 'https://vision.googleapis.com/v1/images:annotate';
        $apiKey = $this->config['api_key'] ?? null;

        if (! $apiKey) {
            throw new RuntimeException('GOOGLE_VISION_API_KEY não configurada.');
        }

        $url = $endpoint.(str_contains($endpoint, '?') ? '&' : '?').'key='.$apiKey;

        try {
            /** @var \Illuminate\Http\Client\Response $httpResponse */
            $httpResponse = $this->http
                ->asJson()
                ->timeout((int) ($this->config['timeout'] ?? 30))
                ->post($url, $payload);
        } catch (Throwable $exception) {
            Log::warning('Falha na requisição ao Google Vision.', [
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Não foi possível conectar ao Google Vision.', 0, $exception);
        }

        if ($httpResponse->failed()) {
            throw new RuntimeException(
                'Google Vision retornou erro: '.$httpResponse->body(),
                $httpResponse->status()
            );
        }

        return $httpResponse->json();
    }
}
