

<?php $__env->startSection('title', 'Страница не найдена'); ?>
<?php $__env->startSection('code', '404'); ?>
<?php $__env->startSection('icon', '🔍'); ?>
<?php $__env->startSection('message'); ?>
    Запрошенная вами страница не найдена. Возможно, она была перемещена или удалена.
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\remont\resources\views/errors/404.blade.php ENDPATH**/ ?>