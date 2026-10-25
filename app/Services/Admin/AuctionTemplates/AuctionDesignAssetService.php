<?php

declare(strict_types=1);

namespace App\Services\Admin\AuctionTemplates;

use App\Exceptions\Auctions\InvalidDesignAssetException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AuctionDesignAssetService
{
    private const MAX_BYTES = 10485760;

    private const MIME = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    public function finalizeSchema(array $schema, array $uploads, string $targetPrefix, array $allowedSourcePrefixes = ['auction_template_samples/', 'auction_templates/']): array
    {
        $disk = Storage::disk('central_public');
        $created = [];
        try {
            foreach ($uploads as $key => $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    throw new InvalidDesignAssetException('Invalid design upload.');
                }
                $this->validateUpload($file);
                $path = trim($targetPrefix, '/').'/'.Str::uuid().'.'.$this->extension($file);
                $disk->putFileAs(dirname($path), $file, basename($path));
                $created[] = $path;
                $this->replaceAssetReference($schema, (string) $key, $path);
            }
            $this->copyReferencedAssets($schema, $targetPrefix, $allowedSourcePrefixes, $created);

            return $schema;
        } catch (\Throwable $e) {
            $this->deleteCreatedFiles($created);
            if ($e instanceof InvalidDesignAssetException) {
                throw $e;
            } throw new InvalidDesignAssetException($e->getMessage(), 0, $e);
        }
    }

    public function publicUrl(string $path): string
    {
        $this->assertSafePath($path);

        return Storage::disk('central_public')->url($path);
    }

    public function dataUri(string $path): string
    {
        $this->assertSafePath($path);
        $disk = Storage::disk('central_public');
        if (! $disk->exists($path)) {
            throw new InvalidDesignAssetException('Design asset not found.');
        } $mime = mime_content_type($disk->path($path)) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }

    public function deleteCreatedFiles(array $paths): void
    {
        $disk = Storage::disk('central_public');
        foreach ($paths as $path) {
            if (is_string($path) && preg_match('#^[^/]+/[^/]+/#', $path)) {
                $disk->delete($path);
            }
        }
    }

    private function validateUpload(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new InvalidDesignAssetException('Image exceeds 10 MiB.');
        } $mime = mime_content_type($file->getRealPath());
        if (! isset(self::MIME[$mime])) {
            throw new InvalidDesignAssetException('Only PNG, JPEG, and WebP images are allowed.');
        } $size = @getimagesize($file->getRealPath());
        if (! $size || $size[0] > 10000 || $size[1] > 10000) {
            throw new InvalidDesignAssetException('Image dimensions are too large.');
        }
    }

    private function extension(UploadedFile $file): string
    {
        $mime = mime_content_type($file->getRealPath());

        return self::MIME[$mime] ?? throw new InvalidDesignAssetException('Unsupported image type.');
    }

    private function replaceAssetReference(array &$schema, string $key, string $path): void
    {
        foreach (($schema['pages'] ?? []) as &$page) {
            if (isset($page['background']['image']) && ($key === $page['id'] || $key === 'page_'.$page['id'])) {
                $page['background']['image'] = $path;
            } foreach (($page['elements'] ?? []) as &$e) {
                if (($e['asset_key'] ?? $e['id'] ?? null) === $key) {
                    $e['asset_path'] = $path;
                }
            }
        }
    }

    private function copyReferencedAssets(array &$schema, string $prefix, array $allowed, array &$created): void
    {
        $disk = Storage::disk('central_public');
        foreach (($schema['pages'] ?? []) as &$page) {
            $ref = $page['background']['image'] ?? null;
            if (! $ref) {
                continue;
            } $this->assertSafePath($ref);
            if (str_starts_with($ref, trim($prefix, '/').'/')) {
                continue;
            } $ok = false;
            foreach ($allowed as $p) {
                if (str_starts_with($ref, trim($p, '/').'/')) {
                    $ok = true;
                }
            } if (! $ok || ! $disk->exists($ref)) {
                throw new InvalidDesignAssetException('Referenced asset is not allowed.');
            } $dest = trim($prefix, '/').'/'.Str::uuid().'-'.basename($ref);
            $disk->put($dest, $disk->get($ref));
            $created[] = $dest;
            $page['background']['image'] = $dest;
        }
    }

    private function assertSafePath(string $path): void
    {
        if ($path === '' || preg_match('#^(https?:|file:|//|\\\\)#i', $path) || str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new InvalidDesignAssetException('Unsafe asset path.');
        }
    }
}
