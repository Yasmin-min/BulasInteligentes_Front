<?php

namespace App\Jobs;

use App\Models\PrescriptionUpload;
use App\Services\PrescriptionParserService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProcessPrescriptionUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 90;

    public function __construct(
        public int $uploadId,
    ) {
    }

    public function handle(PrescriptionParserService $parser): void
    {
        $upload = PrescriptionUpload::find($this->uploadId);

        if (! $upload) {
            return;
        }

        $upload->update(['status' => 'processing']);

        try {
            $filePath = $this->resolveFilePath($upload);

            if (! $filePath) {
                throw new RuntimeException("Arquivo {$upload->file_path} não encontrado para OCR.");
            }

            $result = $parser->extract($filePath);

            $upload->forceFill([
                'status' => $result['status'],
                'extracted_text' => $result['text'],
                'parsed_payload' => $result['structured'],
                'failure_reason' => $result['message'] ?? null,
                'processed_at' => now(),
            ])->save();
        } catch (Exception $exception) {
            Log::error('Falha inesperada ao processar prescrição.', [
                'upload_id' => $upload->id,
                'exception' => $exception->getMessage(),
            ]);

            $upload->forceFill([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
                'processed_at' => now(),
            ])->save();
        }
    }

    protected function resolveFilePath(PrescriptionUpload $upload): ?string
    {
        $disks = array_filter([
            'local',
            config('filesystems.default'),
            'private',
        ]);

        foreach (array_unique($disks) as $disk) {
            $storage = Storage::disk($disk);
            foreach ($this->candidatePaths($upload->file_path) as $candidate) {
                if ($storage->exists($candidate)) {
                    return $storage->path($candidate);
                }
            }
        }

        foreach ($this->candidatePaths($upload->file_path) as $candidate) {
            $absolute = storage_path('app/'.$candidate);
            if (file_exists($absolute)) {
                return $absolute;
            }
        }

        return null;
    }

    protected function candidatePaths(string $path): array
    {
        if (str_starts_with($path, 'private/')) {
            return [$path, preg_replace('/^private\\//', '', $path)];
        }

        return [$path, 'private/'.$path];
    }
}
