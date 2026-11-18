@props([
    'name',
    'label' => null,
    'id' => null,
    'accept' => null,
    'required' => false,
    'multiple' => false,
    'help' => null,
    'hint' => null,
    'col' => 'col-md-6',
    'value' => null,
    'title' => null,
    'desc' => null,
    'maxSize' => 10,
    'compact' => false,
    'wrapperId' => null,
    'wrapperStyle' => null,
])

@php
    $fieldId = $id ?: str_replace(['[', ']', '.'], '-', $name);
    $fieldName = $multiple && ! str_ends_with($name, '[]') ? $name . '[]' : $name;
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
    $fieldHelp = $help ?: $hint;

    $isImageOnly = $accept && (
        str_starts_with($accept, 'image/') ||
        ! preg_match('/\b(pdf|doc|docx|xls|xlsx|csv)\b/i', $accept)
    );

    $defaultTitle = $title ?: ($multiple
        ? ($isImageOnly ? 'انقر أو اسحب الصور هنا' : 'انقر أو اسحب الملفات هنا')
        : ($isImageOnly ? 'انقر أو اسحب الصورة هنا' : 'انقر أو اسحب الملف هنا')
    );

    $defaultDesc = $desc ?: ($isImageOnly
        ? 'صيغ JPG · PNG · WebP — أقصى حجم ' . $maxSize . ' ميجابايت'
        : 'صيغ PDF · صور · مستندات — أقصى حجم ' . $maxSize . ' ميجابايت'
    );

    $existingFiles = [];
    if (!empty($value)) {
        if (is_array($value) && !array_is_list($value)) {
            $rawFiles = [$value];
        } else {
            $rawFiles = is_array($value) ? $value : ($value instanceof \Illuminate\Support\Enumerable || is_iterable($value) ? iterator_to_array($value) : [$value]);
        }
        foreach ($rawFiles as $rf) {
            if (!empty($rf)) {
                $existingFiles[] = $rf;
            }
        }
    }
    $hasExisting = count($existingFiles) > 0;
    $baseNameProp = rtrim($name, '[]');
    $cleanPropName = str_replace(['[', ']', '.'], '_', $baseNameProp);
    $existingInputName = 'existing_' . ($multiple ? $baseNameProp . '[]' : $baseNameProp);
    $dotName = str_replace(['[', ']'], ['.', ''], $baseNameProp);
    $hasFieldError = isset($errors) && ($errors->has($name) || $errors->has($dotName));
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$fieldHelp" :col="$col" :wrapper-id="$wrapperId" :wrapper-style="$wrapperStyle">
    <div
        class="file-upload-control"
        data-file-upload-wrapper
        data-has-existing="{{ $hasExisting ? 'true' : 'false' }}"
        data-was-required="{{ $required ? 'true' : 'false' }}"
        data-is-multiple="{{ $multiple ? 'true' : 'false' }}"
    >
        <input
            id="{{ $fieldId }}"
            name="{{ $fieldName }}"
            type="file"
            class="d-none"
            @if ($multiple) multiple @endif
            @if ($accept) accept="{{ $accept }}" @endif
            @if ($required && !$hasExisting) required @endif
            data-was-required="{{ $required ? 'true' : 'false' }}"
            data-has-existing="{{ $hasExisting ? 'true' : 'false' }}"
            data-form-control
            data-file-input
            data-max-size-mb="{{ $maxSize }}"
            aria-describedby="{{ $errorId }}"
            aria-invalid="{{ $hasFieldError ? 'true' : 'false' }}"
            {{ $attributes->except(['class', 'value', 'title', 'desc', 'compact', 'maxSize', 'hint', 'help', 'required']) }}
        >

        <div
            class="file-upload-area {{ $compact ? 'file-upload-compact' : '' }} {{ $hasFieldError ? 'is-invalid' : '' }}"
            id="{{ $fieldId }}-upload-zone"
            data-file-upload-zone
            data-max-size-mb="{{ $maxSize }}"
            tabindex="0"
            role="button"
            aria-label="{{ $label ?: 'رفع ملف' }}"
        >
            <div class="file-upload-icon-wrap">
                <i class="{{ $isImageOnly ? 'ti ti-photo-up' : 'ti ti-cloud-upload' }}"></i>
            </div>
            <h6 class="file-upload-title">{{ $defaultTitle }}</h6>
            <p class="file-upload-desc">{{ $defaultDesc }}</p>
        </div>

        <div class="file-preview-list mt-2" id="{{ $fieldId }}-preview-list" data-file-preview-container>
            @if ($hasExisting)
                @foreach ($existingFiles as $fileUrl)
                    @php
                        $rawPath = is_string($fileUrl)
                            ? $fileUrl
                            : (is_object($fileUrl)
                                ? ($fileUrl->file_path ?? $fileUrl->url ?? $fileUrl->path ?? '')
                                : (is_array($fileUrl) ? ($fileUrl['url'] ?? $fileUrl['file_path'] ?? $fileUrl['path'] ?? '') : ''));

                        $itemId = is_object($fileUrl)
                            ? ($fileUrl->id ?? null)
                            : (is_array($fileUrl) ? ($fileUrl['id'] ?? null) : null);

                        $itemRemoveName = is_array($fileUrl) ? ($fileUrl['remove_name'] ?? null) : null;
                        $itemRemoveValue = is_array($fileUrl) && array_key_exists('remove_value', $fileUrl)
                            ? $fileUrl['remove_value']
                            : ($itemId ?? $rawPath);

                        $itemDownloadUrl = is_object($fileUrl)
                            ? ($fileUrl->download_url ?? null)
                            : (is_array($fileUrl) ? ($fileUrl['download_url'] ?? null) : null);

                        $baseName = is_object($fileUrl)
                            ? ($fileUrl->original_name ?? $fileUrl->file_name ?? $fileUrl->name ?? null)
                            : (is_array($fileUrl) ? ($fileUrl['original_name'] ?? $fileUrl['file_name'] ?? $fileUrl['name'] ?? null) : null);

                        $baseName = $baseName ?: (basename(parse_url((string) $rawPath, PHP_URL_PATH) ?? '') ?: 'الملف المرفق');

                        $ext = strtolower(pathinfo((string) $baseName, PATHINFO_EXTENSION));
                        if (empty($ext)) {
                            $ext = strtolower(pathinfo(parse_url((string) $rawPath, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                        }

                        $mime = is_object($fileUrl)
                            ? ($fileUrl->mime_type ?? $fileUrl->mime ?? '')
                            : (is_array($fileUrl) ? ($fileUrl['mime_type'] ?? $fileUrl['mime'] ?? '') : '');
                        $isExistingImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)
                            || str_starts_with((string) $mime, 'image/')
                            || str_starts_with((string) $rawPath, 'data:image/');

                        if (preg_match('#^(https?:)?//#', (string) $rawPath) || str_starts_with((string) $rawPath, '/') || str_starts_with((string) $rawPath, 'data:')) {
                            $resolvedUrl = $rawPath;
                        } elseif (!empty($rawPath)) {
                            $resolvedUrl = function_exists('tasset') ? tasset($rawPath) : asset('storage/' . $rawPath);
                        } else {
                            $resolvedUrl = '#';
                        }

                        $resolvedDownloadUrl = $itemDownloadUrl ?: $resolvedUrl;
                        $downloadName = $baseName;
                        if ($ext !== '' && !str_ends_with(strtolower((string) $downloadName), '.' . $ext)) {
                            $downloadName .= '.' . $ext;
                        }

                        $removeInputAttrName = $itemRemoveName
                            ?: ($multiple ? 'remove_' . $cleanPropName . '[]' : 'remove_' . $cleanPropName);
                        $removeMode = ($multiple || str_ends_with((string) $removeInputAttrName, '[]')) ? 'multiple' : 'single';
                        $removeValueAttr = $removeMode === 'multiple' ? (string) $itemRemoveValue : '1';
                        $existingInputValue = $itemId !== null && $itemId !== '' ? (string) $itemId : (string) $rawPath;

                        $iconClass = 'ti-file';
                        $modifierClass = '';
                        if ($ext === 'pdf') {
                            $iconClass = 'ti-file-type-pdf';
                            $modifierClass = 'file-item-icon-pdf';
                        } elseif (in_array($ext, ['doc', 'docx'], true)) {
                            $iconClass = 'ti-file-description';
                            $modifierClass = 'file-item-icon-word';
                        } elseif (in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
                            $iconClass = 'ti-file-spreadsheet';
                            $modifierClass = 'file-item-icon-excel';
                        }
                    @endphp
                    <div class="file-item" data-existing-file-card data-file-path="{{ $existingInputValue }}" @if ($itemId !== null && $itemId !== '') data-file-id="{{ $itemId }}" @endif>
                        <input type="hidden" name="{{ $existingInputName }}" value="{{ $existingInputValue }}" data-existing-file-input>
                        @if ($isExistingImg && $resolvedUrl !== '#')
                            <img src="{{ $resolvedUrl }}" alt="{{ $label ?: $baseName }}" class="file-item-preview"
                                onerror="this.classList.add('d-none');this.nextElementSibling?.classList.remove('d-none')">
                            <div class="file-item-icon file-item-icon-image d-none">
                                <i class="ti ti-photo"></i>
                            </div>
                        @else
                            <div class="file-item-icon {{ $modifierClass }}">
                                <i class="ti {{ $iconClass }}"></i>
                            </div>
                        @endif
                        <div class="file-item-info">
                            <div class="file-item-name" title="{{ $baseName }}">{{ $baseName }}</div>
                            <div class="file-item-size text-success small">ملف محفوظ مسبقاً</div>
                        </div>
                        <div class="file-item-actions">
                            @if ($resolvedUrl !== '#')
                                <a href="{{ $resolvedUrl }}" target="_blank" rel="noopener noreferrer" class="btn-view-file text-decoration-none" style="text-decoration: none;" title="معاينة الملف" aria-label="معاينة الملف">
                                    <i class="ti ti-eye"></i>
                                </a>
                            @endif
                            @if ($resolvedDownloadUrl !== '#')
                                <a href="{{ $resolvedDownloadUrl }}" download="{{ $downloadName }}" class="btn-download-file text-decoration-none" style="text-decoration: none;" title="تنزيل الملف" aria-label="تنزيل الملف">
                                    <i class="ti ti-download"></i>
                                </a>
                            @endif
                            <button type="button" class="btn-remove-file"
                                data-remove-existing-file="{{ $removeInputAttrName }}"
                                data-remove-mode="{{ $removeMode }}"
                                data-remove-value="{{ $removeValueAttr }}"
                                title="إزالة الملف" aria-label="إزالة الملف">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
    {{ $slot }}
</x-form.layout.field>
