<div class="d-flex align-items-center justify-content-between gap-2 mt-4 mb-3">
    <h6 class="mb-0"><i class="ti ti-ruler-2 me-1 text-primary"></i> حدود العقار وأطوالها</h6>
    <button type="button" class="btn btn-sm btn-primary" id="btn-add-border">
        <i class="ti ti-plus me-1"></i> إضافة حد جديد
    </button>
</div>

<?php
    $boundaryDirectionOptions = [
        'north' => 'الشمال',
        'south' => 'الجنوب',
        'east' => 'الشرق',
        'west' => 'الغرب',
        'northeast' => 'الشمال الشرقي',
        'northwest' => 'الشمال الغربي',
        'southeast' => 'الجنوب الشرقي',
        'southwest' => 'الجنوب الغربي',
    ];
    $boundaryTypeOptions = ['street' => 'شارع', 'parcel' => 'قطعة'];
?>

<div class="table-responsive" data-form-field data-field-name="boundaries">
    <table class="table table-striped table-bordered w-100">
        <thead>
            <tr>
                <th style="width: 22%;">الجهة <span class="text-danger">*</span></th>
                <th style="width: 25%;">نوع الحد <span class="text-danger">*</span></th>
                <th style="width: 28%;">وصف الحد <span class="text-danger">*</span></th>
                <th style="width: 17%;">الطول (م) <span class="text-danger">*</span></th>
                <th style="width: 8%;" class="text-center">حذف</th>
            </tr>
        </thead>
        <tbody id="boundaries-list">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $bordersData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $border): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr class="boundary-row">
                    <td>
                        <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'boundaries['.e($idx).'][direction]','id' => 'boundaries-'.e($idx).'-direction','options' => $boundaryDirectionOptions,'value' => $border['direction'] ?? null,'placeholder' => 'اختر الجهة','required' => true,'col' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'boundaries['.e($idx).'][direction]','id' => 'boundaries-'.e($idx).'-direction','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($boundaryDirectionOptions),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($border['direction'] ?? null),'placeholder' => 'اختر الجهة','required' => true,'col' => '']); ?>
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
                    </td>
                    <td>
                        <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'boundaries['.e($idx).'][border_type]','id' => 'boundaries-'.e($idx).'-border-type','options' => $boundaryTypeOptions,'value' => $border['border_type'] ?? null,'placeholder' => 'اختر نوع الحد','required' => true,'col' => '','dataSelect2Search' => 'false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'boundaries['.e($idx).'][border_type]','id' => 'boundaries-'.e($idx).'-border-type','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($boundaryTypeOptions),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($border['border_type'] ?? null),'placeholder' => 'اختر نوع الحد','required' => true,'col' => '','data-select2-search' => 'false']); ?>
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
                    </td>
                    <td>
                        <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'boundaries['.e($idx).'][border_description]','id' => 'boundaries-'.e($idx).'-border-description','value' => $border['border_description'] ?? null,'placeholder' => 'عرض 20 متر...','required' => true,'col' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'boundaries['.e($idx).'][border_description]','id' => 'boundaries-'.e($idx).'-border-description','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($border['border_description'] ?? null),'placeholder' => 'عرض 20 متر...','required' => true,'col' => '']); ?>
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
                    </td>
                    <td>
                        <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'boundaries['.e($idx).'][length]','id' => 'boundaries-'.e($idx).'-length','type' => 'number','step' => '0.01','value' => (isset($border['length']) && $border['length'] !== '' && $border['length'] !== null) ? $border['length'] : 0,'placeholder' => '0.00','required' => true,'col' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'boundaries['.e($idx).'][length]','id' => 'boundaries-'.e($idx).'-length','type' => 'number','step' => '0.01','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((isset($border['length']) && $border['length'] !== '' && $border['length'] !== null) ? $border['length'] : 0),'placeholder' => '0.00','required' => true,'col' => '']); ?>
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
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-icon btn-sm btn-label-danger btn-remove-border" aria-label="حذف الحد">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </tbody>
    </table>
</div>

<template id="boundary-row-template">
    <tr class="boundary-row">
        <td>
            <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'boundaries[__INDEX__][direction]','id' => 'boundaries-__INDEX__-direction','options' => $boundaryDirectionOptions,'placeholder' => 'اختر الجهة','required' => true,'col' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'boundaries[__INDEX__][direction]','id' => 'boundaries-__INDEX__-direction','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($boundaryDirectionOptions),'placeholder' => 'اختر الجهة','required' => true,'col' => '']); ?>
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
        </td>
        <td>
            <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'boundaries[__INDEX__][border_type]','id' => 'boundaries-__INDEX__-border-type','options' => $boundaryTypeOptions,'placeholder' => 'اختر نوع الحد','required' => true,'col' => '','dataSelect2Search' => 'false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'boundaries[__INDEX__][border_type]','id' => 'boundaries-__INDEX__-border-type','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($boundaryTypeOptions),'placeholder' => 'اختر نوع الحد','required' => true,'col' => '','data-select2-search' => 'false']); ?>
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
        </td>
        <td>
            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'boundaries[__INDEX__][border_description]','id' => 'boundaries-__INDEX__-border-description','placeholder' => 'عرض 20 متر...','required' => true,'col' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'boundaries[__INDEX__][border_description]','id' => 'boundaries-__INDEX__-border-description','placeholder' => 'عرض 20 متر...','required' => true,'col' => '']); ?>
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
        </td>
        <td>
            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'boundaries[__INDEX__][length]','id' => 'boundaries-__INDEX__-length','type' => 'number','step' => '0.01','value' => '0','placeholder' => '0.00','required' => true,'col' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'boundaries[__INDEX__][length]','id' => 'boundaries-__INDEX__-length','type' => 'number','step' => '0.01','value' => '0','placeholder' => '0.00','required' => true,'col' => '']); ?>
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
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-icon btn-sm btn-label-danger btn-remove-border" aria-label="حذف الحد">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    </tr>
</template>
<?php /**PATH D:\Levan\old\resources\views/tenant/properties/_boundaries-table.blade.php ENDPATH**/ ?>