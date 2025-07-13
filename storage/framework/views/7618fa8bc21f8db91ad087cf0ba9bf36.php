<h5 class="mb-3">Проверка объекта</h5>

<div class="alert alert-info mb-4">
    <div class="d-flex align-items-center">
        <i class="fas fa-info-circle me-2"></i>
        <div>
            В данном разделе отображается информация о проверках, выполненных на объекте.
            Проверки проводятся специалистами на различных этапах выполнения работ.
        </div>
    </div>
</div>

<div class="check-list-container mb-4">
    <?php if($project->checks && $project->checks->count() > 0): ?>
        <?php $__currentLoopData = $project->checks->groupBy('category'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $categoryChecks): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                // Проверяем, есть ли хотя бы один выполненный чекпоинт в этой категории
                $hasCompletedCheck = $categoryChecks->contains('status', true);
            ?>
            
            <?php if($hasCompletedCheck): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><?php echo e(ucfirst($category)); ?></h6>
                    </div>
                    <div class="card-body">
                        <?php $__currentLoopData = $categoryChecks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $check): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="row mb-2">
                                <div class="col-8">
                                    <span>Проверка #<?php echo e($check->check_id); ?></span>
                                </div>
                                <div class="col-4 text-end">
                                    <?php if($check->status): ?>
                                        <span class="badge bg-success">Выполнено</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Ожидается</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if($check->comment): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <small class="text-muted"><?php echo e($check->comment); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if(!$loop->last): ?>
                                <hr class="my-2">
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php else: ?>
        <div class="alert alert-light">
            <div class="text-center py-3">
                <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Проверки еще не проводились</h6>
                <p class="text-muted mb-0">Информация о проверках будет доступна после их выполнения специалистами.</p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php
        // Проверим, есть ли категории с выполненными проверками
        $hasAnyCategoriesWithCompletedChecks = false;
        if($project->checks && $project->checks->count() > 0) {
            foreach($project->checks->groupBy('category') as $categoryChecks) {
                if($categoryChecks->contains('status', true)) {
                    $hasAnyCategoriesWithCompletedChecks = true;
                    break;
                }
            }
        }
    ?>
    
    <?php if($project->checks && $project->checks->count() > 0 && !$hasAnyCategoriesWithCompletedChecks): ?>
        <div class="alert alert-info">
            <div class="text-center py-3">
                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Пока нет выполненных проверок</h6>
                <p class="text-muted mb-0">В данном разделе будут отображаться проверки после их проведения специалистами.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Адаптивные стили для мобильных устройств */
@media (max-width: 576px) {
    .card-body {
        padding: 0.75rem;
    }
    
    .row {
        margin: 0;
    }
    
    .col-8, .col-4 {
        padding: 0 0.25rem;
    }
    
    .badge {
        font-size: 0.7rem;
    }
}

/* Анимация для плавного отображения содержимого проверки */
.check-content {
    transition: all 0.3s ease-in-out;
}
</style>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/client/projects/tabs/check.blade.php ENDPATH**/ ?>