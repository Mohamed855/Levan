@props([
    'file' => null,
    'name' => null,
    'url' => null,
    'downloadUrl' => null,
    'size' => null,
    'meta' => null,
    'extension' => null,
    'isImage' => null,
    'removable' => false,
    'deleteAction' => null,
    'layout' => 'card', // 'card' or 'list'
])

@php
    // If an Eloquent model / array is passed
    if ($file) {
        $name = $name ?? ($file->original_name ?? $file->file_name ?? $file->name ?? 'ملف مرفق');
        if (!$url) {
            $filePath = $file->file_path ?? $file->path ?? null;
            if ($filePath) {
                $url = function_exists('tasset') ? tasset($filePath) : asset('storage/' . $filePath);
            } else {
                $url = $file->url ?? null;
            }
        }
        $size = $size ?? ($file->file_size ?? $file->size ?? null);
        $extension = $extension ?? ($file->extension ?? null);
        $mime = $file->mime_type ?? null;
    } else {
        $name = $name ?? 'ملف مرفق';
        $mime = null;
    }

    $downloadUrl = $downloadUrl ?? $url;

    // Detect extension
    if (!$extension && $name) {
        $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
    }
    if (!$extension && $url) {
        $cleanUrl = parse_url((string) $url, PHP_URL_PATH);
        if ($cleanUrl) {
            $extension = strtolower(pathinfo($cleanUrl, PATHINFO_EXTENSION));
        }
    }
    $ext = strtolower($extension ?: 'file');

    // Detect if image
    if ($isImage === null) {
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)
            || ($mime && str_starts_with((string) $mime, 'image/'))
            || ($url && str_starts_with((string) $url, 'data:image/'));
    }

    // Category and icon
    $typeCategory = 'generic';
    $iconClass = 'ti-file';
    $badgeColor = 'secondary';
    $typeName = 'مستند';

    if ($isImage) {
        $typeName = 'صورة';
        $badgeColor = 'info';
    } elseif ($ext === 'pdf') {
        $typeCategory = 'pdf';
        $iconClass = 'ti-file-type-pdf';
        $badgeColor = 'danger';
        $typeName = 'مستند PDF';
    } elseif (in_array($ext, ['doc', 'docx'], true)) {
        $typeCategory = 'word';
        $iconClass = 'ti-file-description';
        $badgeColor = 'primary';
        $typeName = 'مستند Word';
    } elseif (in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
        $typeCategory = 'excel';
        $iconClass = 'ti-file-spreadsheet';
        $badgeColor = 'success';
        $typeName = 'جدول بيانات';
    } elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'], true)) {
        $typeCategory = 'zip';
        $iconClass = 'ti-file-zip';
        $badgeColor = 'warning';
        $typeName = 'أرشيف مضغوط';
    }

    // Format size / meta label
    $sizeFormatted = $meta;
    if (!$sizeFormatted && $size !== null) {
        if (is_numeric($size)) {
            $sizeFormatted = $size >= 1048576
                ? number_format($size / 1048576, 1) . ' MB'
                : number_format($size / 1024, 1) . ' KB';
        } else {
            $sizeFormatted = (string) $size;
        }
    }
    if (!$sizeFormatted) {
        $sizeFormatted = $typeName;
    }
@endphp

<article {{ $attributes->merge(['class' => 'assignment-file-card app-file-card'])->except('data-file-type') }} data-file-type="{{ $ext }}">
    <div class="assignment-file-card__preview">
        @if ($isImage && $url)
            <img src="{{ $url }}" alt="{{ $name }}" class="assignment-file-card__img" loading="lazy">
            <span class="assignment-file-card__type-pill">{{ strtoupper($ext ?: 'IMG') }}</span>
        @else
            <div class="assignment-file-card__doc-wrap type-{{ $typeCategory }}">
                <div class="assignment-file-card__icon-circle">
                    <i class="ti {{ $iconClass }}"></i>
                </div>
                <span class="assignment-file-card__type-pill">{{ strtoupper($ext ?: 'FILE') }}</span>
            </div>
        @endif

        @if ($url)
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="assignment-file-card__action" title="معاينة الملف" aria-label="معاينة">
                <i class="ti ti-eye"></i>
            </a>
        @endif
        @if ($removable && $deleteAction)
            <button type="button" class="assignment-file-card__action text-danger" {!! $deleteAction !!} title="حذف الملف" aria-label="حذف" style="left: auto; right: 8px;">
                <i class="ti ti-trash"></i>
            </button>
        @endif
    </div>
    <div class="assignment-file-card__info">
        <span class="assignment-file-card__name" title="{{ $name }}">{{ $name }}</span>
        <div class="assignment-file-card__meta">
            <span class="text-muted text-truncate me-1">{{ $sizeFormatted }}</span>
            <span class="badge bg-label-{{ $badgeColor }}">{{ strtoupper($ext ?: 'FILE') }}</span>
        </div>
    </div>
</article>
