<?php

declare(strict_types=1);

namespace App\Jobs\Tenants\Auctions;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class GenerateAuctionRegulatoryFormJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 420;
    public int $tries = 3;
    public int $backoff = 60;
    public bool $failOnTimeout = true;

    public function __construct(
        private readonly string $tenantId,
        private readonly int $auctionId,
        private readonly int $generationId,
        private readonly string $revisionToken,
    ) {}

    public function handle(AuctionRegulatoryForms $forms, BrowserDocumentRenderer $renderer): void
    {
        $this->inTenant(fn () => $this->generate($forms, $renderer));
    }

    private function inTenant(callable $callback): void
    {
        $previous = tenancy()->initialized ? tenant() : null;
        $sameTenant = $previous && (string) $previous->getTenantKey() === $this->tenantId;
        try {
            if (! $sameTenant) {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
                tenancy()->initialize($this->tenantId);
            }
            $callback();
        } finally {
            if (! $sameTenant) {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
                if ($previous) {
                    tenancy()->initialize($previous);
                }
            }
        }
    }

    private function generate(AuctionRegulatoryForms $forms, BrowserDocumentRenderer $renderer): void
    {
        $generation = DB::transaction(function (): ?AuctionFormGeneration {
            $auction = Auction::query()->whereKey($this->auctionId)->lockForUpdate()->first();
            $generation = AuctionFormGeneration::query()->whereKey($this->generationId)
                ->where('auction_id', $this->auctionId)->where('revision_token', $this->revisionToken)->first();
            if (! $auction || ! $generation || $generation->isReady() || $generation->status === 'superseded') {
                return null;
            }
            if (! $this->isLatest($generation)) {
                $generation->update(['status' => 'superseded', 'completed_at' => now()]);

                return null;
            }
            if ($generation->status === 'processing') {
                return null;
            }
            $generation->update(['status' => 'processing', 'progress' => 10, 'started_at' => now(), 'completed_at' => null, 'error_message' => null]);

            return $generation;
        });
        if (! $generation) {
            return;
        }

        $temporaryBase = tempnam(sys_get_temp_dir(), 'auction-form-');
        $temporary = $temporaryBase === false ? false : $temporaryBase.'.pdf';
        $path = 'auction-regulatory-forms/'.$this->auctionId.'/'.$generation->type.'/'.$this->revisionToken.'.pdf';
        try {
            if ($temporary === false || ! rename($temporaryBase, $temporary)) {
                throw new RuntimeException('Could not create a temporary regulatory PDF file.');
            }
            $html = $forms->html($generation->snapshot, $generation->type);
            $generation->update(['progress' => 30]);
            $renderer->pdf($html, $temporary);
            unset($html);
            $this->assertValidPdf($temporary);
            $generation->update(['progress' => 85]);
            if (! $this->isLatest($generation)) {
                $generation->update(['status' => 'superseded', 'completed_at' => now()]);

                return;
            }
            $disk = Storage::disk('tenant_private');
            if (! $disk->put($path, file_get_contents($temporary)) || ! $disk->exists($path)) {
                throw new RuntimeException('Could not save the regulatory PDF in private storage.');
            }
            $generation->update(['progress' => 95]);
            $published = DB::transaction(function () use ($generation, $path): bool {
                $auction = Auction::query()->whereKey($this->auctionId)->lockForUpdate()->first();
                if (! $auction || ! $this->isLatest($generation)) {
                    $generation->update(['status' => 'superseded', 'completed_at' => now()]);

                    return false;
                }
                $generation->update(['status' => 'ready', 'progress' => 100, 'file_path' => $path, 'error_message' => null, 'completed_at' => now()]);

                return true;
            });
            if (! $published) {
                $disk->delete($path);
            }
        } catch (Throwable $exception) {
            Storage::disk('tenant_private')->delete($path);
            AuctionFormGeneration::query()->whereKey($generation->id)->whereNotIn('status', ['ready', 'superseded'])->update([
                'status' => 'failed', 'progress' => 0, 'file_path' => null,
                'error_message' => $exception->getMessage(), 'completed_at' => now(),
            ]);
            Log::error('Regulatory form generation failed.', ['tenant_id' => $this->tenantId, 'generation_id' => $this->generationId, 'exception' => $exception]);
            throw $exception;
        } finally {
            if (is_string($temporary) && is_file($temporary)) {
                unlink($temporary);
            }
            if (is_string($temporaryBase) && is_file($temporaryBase)) {
                unlink($temporaryBase);
            }
        }
    }

    private function isLatest(AuctionFormGeneration $generation): bool
    {
        return (int) AuctionFormGeneration::query()->where('auction_id', $this->auctionId)
            ->where('type', $generation->type)->max('id') === $this->generationId;
    }

    private function assertValidPdf(string $path): void
    {
        if (! is_file($path) || filesize($path) < 10 || file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('PDF renderer did not produce a valid regulatory document.');
        }
        $tail = file_get_contents($path, false, null, max(0, filesize($path) - 1024));
        if (! str_contains($tail, '%%EOF')) {
            throw new RuntimeException('PDF renderer produced an incomplete regulatory document.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->inTenant(function () use ($exception): void {
            AuctionFormGeneration::query()->whereKey($this->generationId)->where('auction_id', $this->auctionId)
                ->where('revision_token', $this->revisionToken)->whereIn('status', ['queued', 'processing'])->update([
                    'status' => 'failed', 'progress' => 0,
                    'error_message' => $exception?->getMessage() ?? 'Regulatory form generation failed.', 'completed_at' => now(),
                ]);
        });
    }
}
