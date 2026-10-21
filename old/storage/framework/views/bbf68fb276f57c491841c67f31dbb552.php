<?php
    $latest = $template->versions?->first();
    $schema = old('schema', $latest ? json_encode($latest->schema, JSON_UNESCAPED_UNICODE) : null);
    $isEdit = $template->exists;
    $typeValue = $isEdit ? ($template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type) : old('type', 'brochure');
?>

<div class="row g-4" data-template-designer data-type="<?php echo e($typeValue); ?>" data-schema="<?php echo e(e($schema)); ?>">
    <input type="hidden" name="schema" data-schema-input value="<?php echo e($schema); ?>">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'name','label' => 'اسم القالب','value' => $template->name,'required' => true,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','label' => 'اسم القالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($template->name),'required' => true,'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $attributes = $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $component = $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEdit): ?>
                    <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'type_display','label' => 'نوع القالب','value' => $template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type,'disabled' => true,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'type_display','label' => 'نوع القالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type),'disabled' => true,'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $attributes = $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $component = $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
                <?php else: ?>
                    <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'type','label' => 'نوع القالب','value' => $typeValue,'options' => ['brochure' => 'كتيب مزاد متعدد الصفحات', 'billboard' => 'لوحة إعلانية', 'social' => 'منشور اجتماعي'],'required' => true,'select2' => false,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'type','label' => 'نوع القالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeValue),'options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['brochure' => 'كتيب مزاد متعدد الصفحات', 'billboard' => 'لوحة إعلانية', 'social' => 'منشور اجتماعي']),'required' => true,'select2' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $attributes = $__attributesOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__attributesOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $component = $__componentOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__componentOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.textarea','data' => ['name' => 'description','label' => 'وصف القالب','value' => $template->description,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'description','label' => 'وصف القالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($template->description),'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d)): ?>
<?php $attributes = $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d; ?>
<?php unset($__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d)): ?>
<?php $component = $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d; ?>
<?php unset($__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d); ?>
<?php endif; ?>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" id="is_active" name="is_active" value="1" type="checkbox" <?php if(old('is_active', $template->is_active ?? true)): echo 'checked'; endif; ?>>
                    <label class="form-check-label" for="is_active">القالب نشط</label>
                </div>
                <hr>
                <h6 class="mb-3">خصائص العنصر المحدد</h6>
                <div data-properties></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">مصمم القالب</h5>
                    <small class="text-muted">ارسم المحتوى وحدد مواضع البيانات والصور على كل صفحة من القالب.</small>
                </div>
                <div class="d-flex gap-2" data-page-tabs></div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                    <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="text">
                        <i class="ti ti-typography me-1"></i>إضافة نص
                    </button>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="variable">
                        <i class="ti ti-variable me-1"></i>إضافة متغير
                    </button>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="image">
                        <i class="ti ti-photo me-1"></i>إضافة صورة
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-add-page>
                        <i class="ti ti-plus me-1"></i>إضافة صفحة
                    </button>
                    <label class="d-flex align-items-center gap-2 ms-auto small mb-0">
                        لون الخلفية
                        <input class="form-control form-control-color" data-background-color type="color" value="#ffffff" title="اختر لون خلفية الصفحة">
                    </label>
                </div>
                <div class="template-designer__canvas template-canvas" data-canvas></div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['schema'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="text-danger small mt-2"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <a href="<?php echo e(route('admin.auction-templates.index')); ?>" class="btn btn-label-secondary">العودة للرئيسية</a>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $isEdit): ?>
        <?php if (isset($component)) { $__componentOriginal0281d78d2d16329c5a422ee3e125697d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0281d78d2d16329c5a422ee3e125697d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0281d78d2d16329c5a422ee3e125697d)): ?>
<?php $attributes = $__attributesOriginal0281d78d2d16329c5a422ee3e125697d; ?>
<?php unset($__attributesOriginal0281d78d2d16329c5a422ee3e125697d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0281d78d2d16329c5a422ee3e125697d)): ?>
<?php $component = $__componentOriginal0281d78d2d16329c5a422ee3e125697d; ?>
<?php unset($__componentOriginal0281d78d2d16329c5a422ee3e125697d); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <button class="btn btn-primary" type="submit"><?php echo e($isEdit ? 'حفظ التعديلات' : 'إنشاء القالب'); ?></button>
</div>

<div class="modal fade" id="designer-preview-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">معاينة القالب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-preview-body></div>
        </div>
    </div>
</div>
<?php /**PATH D:\Levan\old\resources\views/admin/auction-templates/partials/form.blade.php ENDPATH**/ ?>