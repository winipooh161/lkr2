

<?php $__env->startSection('title', 'Ошибка сервера'); ?>
<?php $__env->startSection('code', '500'); ?>
<?php $__env->startSection('icon', '⚠️'); ?>
<?php $__env->startSection('message'); ?>
    Произошла внутренняя ошибка сервера.
    Наша команда уже получила уведомление и работает над устранением проблемы.
<?php $__env->stopSection(); ?>

<?php echo $__env->make('errors.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\remont\resources\views/errors/500.blade.php ENDPATH**/ ?>