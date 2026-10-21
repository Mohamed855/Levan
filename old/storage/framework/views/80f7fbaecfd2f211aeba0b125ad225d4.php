<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id',
    'label',
    'options' => [],
    'enum' => null,
    'placeholder' => 'الكل',
    'value' => null,
    'name' => null,
    'filterKey' => null,
    'table' => null,
    'target' => null,
    'col' => 'col-md-6 col-lg-3',
    'wrapperClass' => '',
    'selectClass' => '',
    'multiple' => false,
    'disabled' => false,
    'required' => false,
    'allowClear' => true,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'id',
    'label',
    'options' => [],
    'enum' => null,
    'placeholder' => 'الكل',
    'value' => null,
    'name' => null,
    'filterKey' => null,
    'table' => null,
    'target' => null,
    'col' => 'col-md-6 col-lg-3',
    'wrapperClass' => '',
    'selectClass' => '',
    'multiple' => false,
    'disabled' => false,
    'required' => false,
    'allowClear' => true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    if ($enum) {
        $options = \App\Support\SelectOptions::fromEnum($enum);
    }

    $customClass = $attributes->get('class', '');
    $hasColInClass = $customClass && preg_match('/(^|\s)col(-[a-z0-9]+)?(-\d+)?(\s|$)/', $customClass);
    $columnClass = trim(($hasColInClass ? $customClass : "$col $wrapperClass"));
    $remainingAttributes = $hasColInClass ? $attributes->except('class') : $attributes;

    $selectName = $name;
    if ($selectName && $multiple && !str_ends_with($selectName, '[]')) {
        $selectName .= '[]';
    }

    $targetTable = $table ?? $target;
    if ($targetTable && !str_starts_with($targetTable, '#') && !str_starts_with($targetTable, '.')) {
        $targetTable = '#' . $targetTable;
    }

    $filterDataAttributes = [
        'class' => trim('form-control select2 ' . $selectClass),
        'data-placeholder' => $placeholder,
        'data-allow-clear' => $allowClear ? 'true' : 'false',
        'data-datatable-filter' => 'true',
    ];

    if ($filterKey) {
        $filterDataAttributes['data-filter-key'] = $filterKey;
    }
    if ($targetTable) {
        $filterDataAttributes['data-datatable-target'] = $targetTable;
    }
?>

<div class="<?php echo e($columnClass); ?>">
    <label class="form-label" for="<?php echo e($id); ?>"><?php echo e($label); ?></label>
    <select id="<?php echo e($id); ?>"
        <?php if($selectName): ?> name="<?php echo e($selectName); ?>" <?php endif; ?>
        <?php if($multiple): ?> multiple <?php endif; ?>
        <?php if($disabled): ?> disabled <?php endif; ?>
        <?php if($required): ?> required <?php endif; ?>
        <?php echo e($remainingAttributes->merge($filterDataAttributes)); ?>>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$multiple): ?>
            <option value=""></option>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php
                if ($enum) {
                    $optionValue = $key;
                    $optionLabel = $option;
                    $optionDisabled = false;
                } elseif (is_array($option)) {
                    $optionValue = $option['value'] ?? $option['id'] ?? $key;
                    $optionLabel = $option['label'] ?? $option['name'] ?? $option['title'] ?? (string) $optionValue;
                    $optionDisabled = !empty($option['disabled']);
                } elseif (is_object($option)) {
                    $optionValue = $option->value ?? $option->id ?? $key;
                    $optionLabel = $option->label ?? $option->name ?? $option->title ?? (string) $optionValue;
                    $optionDisabled = !empty($option->disabled);
                } elseif (is_array($options) && array_is_list($options)) {
                    $optionValue = $option;
                    $optionLabel = $option;
                    $optionDisabled = false;
                } else {
                    $optionValue = $key;
                    $optionLabel = $option;
                    $optionDisabled = false;
                }

                $isSelected = is_array($value)
                    ? in_array((string) $optionValue, array_map('strval', $value), true)
                    : ($value !== null && $value !== '' && (string) $value === (string) $optionValue);
            ?>
            <option value="<?php echo e($optionValue); ?>" <?php if($isSelected): echo 'selected'; endif; ?> <?php if($optionDisabled): echo 'disabled'; endif; ?>>
                <?php echo e($optionLabel); ?>

            </option>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </select>
</div>
<?php /**PATH D:\Levan\old\resources\views/components/shared/filter/select.blade.php ENDPATH**/ ?>