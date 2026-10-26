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
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$fieldHelp" :col="$col" :wrapper-id="$wrapperId" :wrapper-style="$wrapperStyle">
    <div class="file-upload-control" data-file-upload-wrapper>
        <input
            id="{{ $fieldId }}"
            name="{{ $fieldName }}"
            type="file"
            class="d-none"
            @if ($multiple) multiple @endif
            @if ($accept) accept="{{ $accept }}" @endif
            data-form-control
            data-file-input
            data-max-size-mb="{{ $maxSize }}"
            aria-describedby="{{ $errorId }}"
            aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            {{ $attributes->except(['class', 'value', 'title', 'desc', 'compact', 'maxSize', 'hint', 'help']) }}
        >

        <div
            class="file-upload-area {{ $compact ? 'file-upload-compact' : '' }} {{ $errors->has($name) ? 'is-invalid' : '' }}"
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
            @if ($value)
                @php
                    $existingFiles = is_array($value) ? $value : [$value];
                @endphp
                @foreach ($existingFiles as $fileUrl)
                    @if (!empty($fileUrl))
                        @php
                            $rawPath = is_string($fileUrl) ? $fileUrl : (is_object($fileUrl) ? ($fileUrl->file_path ?? $fileUrl->url ?? '') : '');
                            $ext = strtolower(pathinfo(parse_url($rawPath, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                            $isExistingImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']) || str_starts_with($rawPath, 'data:image/');
                            $baseName = basename(parse_url($rawPath, PHP_URL_PATH) ?? '') ?: 'الملف المرفق';
                            $resolvedUrl = is_string($fileUrl) ? $fileUrl : ($rawPath ? (function_exists('tasset') ? tasset($rawPath) : asset('storage/' . $rawPath)) : '#');
                        @endphp
                        <div class="file-item" data-existing-file-card>
                            @if ($isExistingImg)
                                <img src="{{ $resolvedUrl }}" alt="{{ $label ?: $baseName }}" class="file-item-preview">
                            @else
                                <div class="file-item-icon {{ $ext === 'pdf' ? 'file-item-icon-pdf' : '' }}">
                                    <i class="ti {{ $ext === 'pdf' ? 'ti-file-type-pdf' : 'ti-file' }}"></i>
                                </div>
                            @endif
                            <div class="file-item-info">
                                <div class="file-item-name" title="{{ $baseName }}">{{ $baseName }}</div>
                                <div class="file-item-size text-success small">ملف محفوظ مسبقاً</div>
                            </div>
                            @if ($resolvedUrl !== '#')
                                <a href="{{ $resolvedUrl }}" target="_blank" class="btn-view-file me-1" title="معاينة الملف" aria-label="معاينة الملف">
                                    <i class="ti ti-eye"></i>
                                </a>
                            @endif
                            <button type="button" class="btn-remove-file" data-remove-existing-file="remove_{{ str_replace(['[', ']', '.'], '_', $name) }}" title="إزالة الملف" aria-label="إزالة الملف">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
    {{ $slot }}
</x-form.layout.field>
