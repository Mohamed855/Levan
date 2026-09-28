@extends('tenants.layouts.layoutMaster')

@section('title', 'رفع تذكرة ')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenants/tickets/form-validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">معلومات التذكرة</span>
                                <span class="bs-stepper-subtitle">البيانات الأساسية</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ tenant_route_name('tickets.store') }}" method="POST"
                        enctype="multipart/form-data" onSubmit="return false">
                        @csrf

                        <div id="info-validation" class="content">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">عنوان التذكرة <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        value="{{ old('title') }}" placeholder="أدخل عنوان التذكرة" required>
                                </div>
                                 <div class="col-md-6">
                                    <label for="ticketing_sections_id" class="form-label"> تصنيف التذكرة <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select select2" id="ticketing_sections_id"
                                        name="ticketing_sections_id" required data-placeholder="اختر التصنيف">
                                        <option value=""> </option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section['id'] ?? '' }}">{{ $section['name'] ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="description" class="form-label">وصف المشكلة <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="5"
                                        placeholder="اشرح تفاصيل المشكلة التي تواجهها..." required>{{ old('description') }}</textarea>
                                </div>

                                <div class="col-md-12">
                                    <label for="attachments" class="form-label">المرفقات (اختياري)</label>
                                    <input type="file" class="form-control" id="attachments" name="attachments[]"
                                        multiple accept="image/*,application/pdf,.doc,.docx,.txt" onchange="previewFiles()">
                                    <small class="text-muted">يمكنك رفع عدة صور أو ملفات (حد أقصى 5 ملفات، كل ملف حتى
                                        5MB)</small>
                                    <div id="file-count" class="mt-2" style="display: none;">
                                        <span class="badge bg-primary">عدد الملفات المحددة: <span
                                                id="count">0</span></span>
                                    </div>
                                    
                                    <!-- Preview Container -->
                                    <div id="preview-container" class="preview-container"></div>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end mt-5">
                                <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                    <i class="bx bx-save me-1"></i>
                                    <span>حفظ </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedFiles = [];

        function previewFiles() {
            const input = document.getElementById('attachments');
            const previewContainer = document.getElementById('preview-container');
            const fileCountDiv = document.getElementById('file-count');
            const countSpan = document.getElementById('count');
            
            // Clear previous previews
            previewContainer.innerHTML = '';
            selectedFiles = Array.from(input.files);

            if (selectedFiles.length > 0) {
                fileCountDiv.style.display = 'block';
                countSpan.textContent = selectedFiles.length;

                selectedFiles.forEach((file, index) => {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    previewItem.dataset.index = index;

                    // Create remove button
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-file';
                    removeBtn.innerHTML = '×';
                    removeBtn.type = 'button';
                    removeBtn.onclick = () => removeFile(index);

                    // Check if file is an image
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            previewItem.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        // For non-image files, show icon
                        const fileIcon = document.createElement('div');
                        fileIcon.className = 'file-icon';
                        
                        // Choose icon based on file type
                        let iconClass = 'bx-file';
                        if (file.type.includes('pdf')) {
                            iconClass = 'bx-file-pdf';
                        } else if (file.type.includes('word') || file.name.endsWith('.doc') || file.name.endsWith('.docx')) {
                            iconClass = 'bx-file-doc';
                        } else if (file.type.includes('text')) {
                            iconClass = 'bx-file-txt';
                        }
                        
                        fileIcon.innerHTML = `<i class="bx ${iconClass}"></i>`;
                        previewItem.appendChild(fileIcon);
                    }

                    // Add file name
                    const fileName = document.createElement('div');
                    fileName.className = 'file-name';
                    fileName.textContent = file.name;
                    fileName.title = file.name;
                    previewItem.appendChild(fileName);

                    previewItem.appendChild(removeBtn);
                    previewContainer.appendChild(previewItem);
                });
            } else {
                fileCountDiv.style.display = 'none';
            }
        }

        function removeFile(index) {
            // Remove file from selectedFiles array
            selectedFiles.splice(index, 1);

            // Update the file input
            const input = document.getElementById('attachments');
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            input.files = dt.files;

            // Refresh preview
            previewFiles();
        }
    </script>
@endsection
