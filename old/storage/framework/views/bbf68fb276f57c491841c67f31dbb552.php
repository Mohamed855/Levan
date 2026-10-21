<?php
    $latest = $template->versions?->first();
    $schema = old('schema', $latest ? json_encode($latest->schema, JSON_UNESCAPED_UNICODE) : null);
    $isEdit = $template->exists;
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <?php if (isset($component)) { $__componentOriginal9a9c56e4d1de153f8d0dfeb8ac276209 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9a9c56e4d1de153f8d0dfeb8ac276209 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.form.controls.input','data' => ['name' => 'name','label' => 'اسم القالب','value' => $template->name,'required' => true,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','label' => 'اسم القالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($template->name),'required' => true,'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9a9c56e4d1de153f8d0dfeb8ac276209)): ?>
<?php $attributes = $__attributesOriginal9a9c56e4d1de153f8d0dfeb8ac276209; ?>
<?php unset($__attributesOriginal9a9c56e4d1de153f8d0dfeb8ac276209); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9a9c56e4d1de153f8d0dfeb8ac276209)): ?>
<?php $component = $__componentOriginal9a9c56e4d1de153f8d0dfeb8ac276209; ?>
<?php unset($__componentOriginal9a9c56e4d1de153f8d0dfeb8ac276209); ?>
<?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEdit): ?>
                    <?php if (isset($component)) { $__componentOriginal9a9c56e4d1de153f8d0dfeb8ac276209 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9a9c56e4d1de153f8d0dfeb8ac276209 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.form.controls.input','data' => ['name' => 'type_display','label' => 'نوع القالب','value' => $template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type,'disabled' => true,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'type_display','label' => 'نوع القالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type),'disabled' => true,'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9a9c56e4d1de153f8d0dfeb8ac276209)): ?>
<?php $attributes = $__attributesOriginal9a9c56e4d1de153f8d0dfeb8ac276209; ?>
<?php unset($__attributesOriginal9a9c56e4d1de153f8d0dfeb8ac276209); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9a9c56e4d1de153f8d0dfeb8ac276209)): ?>
<?php $component = $__componentOriginal9a9c56e4d1de153f8d0dfeb8ac276209; ?>
<?php unset($__componentOriginal9a9c56e4d1de153f8d0dfeb8ac276209); ?>
<?php endif; ?>
                <?php else: ?>
                    <?php if (isset($component)) { $__componentOriginald2b41363b3c894a75f14566dbeb9936c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald2b41363b3c894a75f14566dbeb9936c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.form.controls.select','data' => ['name' => 'type','label' => 'نوع القالب','value' => $template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type,'options' => ['brochure' => 'كتيب مزاد متعدد الصفحات', 'billboard' => 'لوحة إعلانية', 'social' => 'منشور اجتماعي'],'required' => true,'select2' => false,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'type','label' => 'نوع القالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type),'options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['brochure' => 'كتيب مزاد متعدد الصفحات', 'billboard' => 'لوحة إعلانية', 'social' => 'منشور اجتماعي']),'required' => true,'select2' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald2b41363b3c894a75f14566dbeb9936c)): ?>
<?php $attributes = $__attributesOriginald2b41363b3c894a75f14566dbeb9936c; ?>
<?php unset($__attributesOriginald2b41363b3c894a75f14566dbeb9936c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald2b41363b3c894a75f14566dbeb9936c)): ?>
<?php $component = $__componentOriginald2b41363b3c894a75f14566dbeb9936c; ?>
<?php unset($__componentOriginald2b41363b3c894a75f14566dbeb9936c); ?>
<?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal446279e20c9101bc81bf1a076af05274 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal446279e20c9101bc81bf1a076af05274 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.form.controls.textarea','data' => ['name' => 'description','label' => 'وصف القالب','value' => $template->description,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.form.controls.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'description','label' => 'وصف القالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($template->description),'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal446279e20c9101bc81bf1a076af05274)): ?>
<?php $attributes = $__attributesOriginal446279e20c9101bc81bf1a076af05274; ?>
<?php unset($__attributesOriginal446279e20c9101bc81bf1a076af05274); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal446279e20c9101bc81bf1a076af05274)): ?>
<?php $component = $__componentOriginal446279e20c9101bc81bf1a076af05274; ?>
<?php unset($__componentOriginal446279e20c9101bc81bf1a076af05274); ?>
<?php endif; ?>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" id="is_active" name="is_active" value="1" type="checkbox" <?php if(old('is_active', $template->is_active ?? true)): echo 'checked'; endif; ?>>
                    <label class="form-check-label" for="is_active">القالب نشط</label>
                </div>
                <hr>
                <h6>خصائص العنصر المحدد</h6>
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
            <div class="card-body" data-template-designer data-type="<?php echo e($isEdit ? ($template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type) : old('type', 'brochure')); ?>" data-schema="<?php echo e(e($schema)); ?>">
                <input type="hidden" name="schema" data-schema-input value="<?php echo e($schema); ?>">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="text">إضافة نص</button>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="variable">إضافة متغير</button>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="image">إضافة صورة</button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-add-page>إضافة صفحة</button>
                    <label class="d-flex align-items-center gap-2 ms-auto small">
                        لون الخلفية 
                        <input class="form-control form-control-color" data-background-color type="color" value="#ffffff">
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
        <button class="btn btn-label-secondary" type="button" data-wizard-draft data-save-draft>
            <i class="ti ti-device-floppy me-1"></i>حفظ كمسودة
        </button>
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