<div class="d-flex justify-content-center gap-2">
   <a href="<?php echo e(route('admin.users.profile' , $row->id)); ?>" class="btn btn-icon btn-label-primary bg-label-primary">
        <i class="ti ti-eye"></i>
    </a>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.edit')): ?>
    <a href="<?php echo e(route($route . '.edit', $row->id)); ?>" class="btn btn-icon btn-label-primary bg-label-primary">
        <i class="ti ti-edit"></i>
    </a>
    <?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.delete')): ?>
    <button type="button" class="btn btn-icon btn-label-primary bg-label-primary js-delete-btn" data-id="<?php echo e($row->id); ?>">
        <i class="ti ti-trash"></i>
    </button>

    <form id="delete-form-<?php echo e($row->id); ?>" action="<?php echo e(route($route . '.destroy', $row->id)); ?>" method="POST" style="display: none;">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
    </form>
    <?php endif; ?>

    
</div>
<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-delete-btn');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        confirmDelete(id);
    });
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH D:\Levan\old\resources\views/admin/users/action.blade.php ENDPATH**/ ?>