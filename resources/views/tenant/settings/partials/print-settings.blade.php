<div class="tab-pane fade" id="printSettings">
    <div class="card mb-6">
        <div class="card-body">
            <form id="formPrintSettings" method="POST" action="{{ tenant_route_name('settings.update') }}"
                enctype="multipart/form-data" novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="active_tab" id="" value="#printSettings">

                <div class="row mb-12">
                    <div class="col-md-4">
                        <!-- الترويسة الأفقية -->
                        <div class="">
                            <img src="{{ tasset($settings->horizontal_header_image) }}" alt="الترويسة "
                                class="d-block w-100 mb-5 h-px-150 rounded object-fit-contain"
                                id="uploadedHorizontalHeader" />

                            <div class="button-wrapper">
                                <label for="uploadHorizontalHeader" class="btn btn-primary me-3 mb-4" tabindex="0">
                                    <span class="d-none d-sm-block">تحميل الترويسة </span>
                                    <i class="fas fa-upload d-block d-sm-none"></i>
                                    <input type="file" id="uploadHorizontalHeader" name="horizontal_header_image"
                                        class="account-file-input" hidden accept="image/png, image/jpeg, image/gif" />
                                </label>
                                @if ($errors->has('horizontal_header_image'))
                                    <p class="text-danger small">{{ $errors->first('horizontal_header_image') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- الفوتر الأفقي -->
                        <div class="">

                            <img src="{{ tasset($settings->horizontal_footer_image) }}" alt="الفوتر "
                                class="d-block w-100 mb-5 h-px-150 rounded object-fit-contain"
                                id="uploadedHorizontalFooter" />
                            <div class="button-wrapper">
                                <label for="uploadHorizontalFooter" class="btn btn-primary me-3 mb-4" tabindex="0">
                                    <span class="d-none d-sm-block">تحميل الفوتر </span>
                                    <i class="fas fa-upload d-block d-sm-none"></i>
                                    <input type="file" id="uploadHorizontalFooter" name="horizontal_footer_image"
                                        class="account-file-input" hidden accept="image/png, image/jpeg, image/gif" />
                                </label>
                                @if ($errors->has('horizontal_footer_image'))
                                    <p class="text-danger small">{{ $errors->first('horizontal_footer_image') }}</p>
                                @endif
                            </div>
                        </div>



                    </div>

                    <!-- الختم الخاص بالمؤسسة -->
                    <div class="col-md-4">
                        <div class="">

                            <img src="{{ tasset($settings->signature) }}" alt="الختم الخاص بالمؤسسة"
                                class="d-block w-100 mb-5 h-px-150 rounded object-fit-contain" id="uploadedSignature" />
                            <div class="button-wrapper">
                                <label for="uploadSignature" class="btn btn-primary me-3 mb-4" tabindex="0">
                                    <span class="d-none d-sm-block">تحميل الختم</span>
                                    <i class="fas fa-upload d-block d-sm-none"></i>
                                    <input type="file" id="uploadSignature" name="signature"
                                        class="account-file-input" hidden accept="image/png, image/jpeg, image/gif" />
                                </label>
                                @if ($errors->has('signature'))
                                    <p class="text-danger small">{{ $errors->first('signature') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <script>
                        document.getElementById('uploadSignature').addEventListener('change', function() {
                            if (this.files && this.files[0]) {
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    document.getElementById('uploadedSignature').src = e.target.result;
                                }
                                reader.readAsDataURL(this.files[0]);
                            }
                        });
                    </script>


                </div>
                <!-- زر الحفظ -->
                <div class="mt-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary me-3" data-bs-toggle="modal"
                        data-bs-target="#previewModal">معاينة</button>

                    <button type="submit" class="btn btn-primary me-3">تحديث </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="ti ti-eye text-primary me-2"></i>معاينة الترويسة والفوتر
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="text-right mb-4">
                    <img src="{{ tasset('storage/' . $settings->horizontal_header_image) }}" alt="الترويسة"
                        class="mb-3 w-25 h-auto" id="previewHorizontalHeader" />
                </div>

                <div class="text-end" style="margin-top: 200px">
                    <img src="{{ tasset('storage/' . $settings->signature) }}" alt="الختم" class="mb-3 w-20 h-20"
                        id="previewSignature" />
                </div>

                <div class="text-center mb-4">
                    <img src="{{ tasset('storage/' . $settings->horizontal_footer_image) }}" alt="الفوتر"
                        class="mb-3 w-100 h-auto" id="previewHorizontalFooter" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
