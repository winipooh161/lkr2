

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <h1 class="h3 mb-3 mb-md-0">Мои объекты</h1>
    </div>
    
    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Фильтры и поиск - улучшенная версия для мобильных -->
    <div class="card mb-4">
        <div class="card-header p-2 d-flex justify-content-between align-items-center">
            <h5 class="m-0">Фильтры</h5>
            <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="fas fa-sliders-h"></i>
            </button>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body p-2 p-md-3">
                <form action="<?php echo e(route('client.projects.index')); ?>" method="GET" id="filterForm">
                    <input type="hidden" name="filter" value="true">
                    
                    <div class="row g-2">
                        <div class="col-12 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Поиск по имени, адресу..." value="<?php echo e($filters['search'] ?? ''); ?>">
                                <button type="submit" class="btn btn-primary d-md-none">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-6 col-md-4 mb-2">
                            <label class="d-block d-md-none small mb-1">Статус</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">Все статусы</option>
                                <option value="active" <?php echo e(isset($filters['status']) && $filters['status'] == 'active' ? 'selected' : ''); ?>>Активные</option>
                                <option value="completed" <?php echo e(isset($filters['status']) && $filters['status'] == 'completed' ? 'selected' : ''); ?>>Завершенные</option>
                                <option value="paused" <?php echo e(isset($filters['status']) && $filters['status'] == 'paused' ? 'selected' : ''); ?>>Приостановленные</option>
                                <option value="cancelled" <?php echo e(isset($filters['status']) && $filters['status'] == 'cancelled' ? 'selected' : ''); ?>>Отмененные</option>
                            </select>
                        </div>
                        
                        <div class="col-6 col-md-4 mb-2">
                            <label class="d-block d-md-none small mb-1">Тип работ</label>
                            <select class="form-select" name="work_type" id="work_type">
                                <option value="">Все типы работ</option>
                                <option value="repair" <?php echo e(isset($filters['work_type']) && $filters['work_type'] == 'repair' ? 'selected' : ''); ?>>Ремонт</option>
                                <option value="design" <?php echo e(isset($filters['work_type']) && $filters['work_type'] == 'design' ? 'selected' : ''); ?>>Дизайн</option>
                                <option value="construction" <?php echo e(isset($filters['work_type']) && $filters['work_type'] == 'construction' ? 'selected' : ''); ?>>Строительство</option>
                            </select>
                        </div>
                        
                        <div class="col-6 d-md-none">
                            <a href="<?php echo e(route('client.projects.index', ['clear' => true])); ?>" class="btn btn-outline-secondary w-100">
                                Сбросить
                            </a>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-12 text-end d-none d-md-block">
                            <a href="<?php echo e(route('client.projects.index', ['clear' => true])); ?>" class="btn btn-outline-secondary">
                                Сбросить
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Информация о примененных фильтрах -->
    <?php if(!empty(array_filter($filters ?? []))): ?>
        <div class="mb-3 overflow-auto hide-scroll">
            <div class="d-flex align-items-center flex-nowrap">
                <span class="me-2 text-nowrap">Фильтры:</span>
                <?php if(!empty($filters['search'])): ?>
                    <span class="badge bg-light text-dark me-2">Поиск: <?php echo e(Str::limit($filters['search'], 15)); ?></span>
                <?php endif; ?>
                <?php if(!empty($filters['status'])): ?>
                    <span class="badge bg-light text-dark me-2">Статус: 
                        <?php echo e($filters['status'] == 'active' ? 'Активные' : 
                          ($filters['status'] == 'completed' ? 'Завершенные' : 
                          ($filters['status'] == 'paused' ? 'Приостановленные' : 'Отмененные'))); ?>

                    </span>
                <?php endif; ?>
                <?php if(!empty($filters['work_type'])): ?>
                    <span class="badge bg-light text-dark me-2">Тип: 
                        <?php echo e($filters['work_type'] == 'repair' ? 'Ремонт' : 
                          ($filters['work_type'] == 'design' ? 'Дизайн' : 'Строительство')); ?>

                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if($projects->isEmpty()): ?>
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Объекты не найдены</h5>
                <?php if(!empty(array_filter($filters ?? []))): ?>
                    <p>Попробуйте изменить параметры фильтрации или <a href="<?php echo e(route('client.projects.index', ['clear' => true])); ?>">сбросить все фильтры</a>.</p>
                <?php else: ?>
                    <p>У вас пока нет объектов. Они появятся здесь, когда наш партнер создаст их с вашим номером телефона.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-12 col-md-6 col-xl-4 mb-3">
                    <div class="card h-100 project-card ">
                        <div class="card-header d-flex justify-content-between align-items-center p-2 px-3">
                            <h5 class="card-title mb-0 text-truncate" style="max-width: 70%;">
                                <a href="<?php echo e(route('client.projects.show', $project)); ?>" class="text-decoration-none text-dark stretched-link">
                                    <?php echo e($project->client_name); ?>

                                </a>
                            </h5>
                            <span class="badge <?php echo e($project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary'))); ?>">
                                <?php echo e($project->status == 'active' ? 'Активен' : 
                                   ($project->status == 'paused' ? 'Приостановлен' : 
                                   ($project->status == 'completed' ? 'Завершен' : 'Отменен'))); ?>

                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-map-marker-alt text-muted mt-1 me-2"></i>
                                    <div class="text-truncate" style="max-width: 100%;">
                                        <?php echo e($project->full_address); ?>

                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Тип работ:</small>
                                    <span><?php echo e($project->work_type_text); ?></span>
                                </div>
                                <div class="col-6 text-end">
                                    <small class="text-muted d-block">Площадь:</small>
                                    <span><?php echo e($project->area ?? '-'); ?> м²</span>
                                </div>
                            </div>
                            
                            <hr class="my-2">
                            
                            <?php if($project->contract_date): ?>
                            <div class="small text-muted mb-1">
                                <i class="fas fa-file-signature me-1"></i> Договор: 
                                <?php echo e($project->contract_date->format('d.m.Y')); ?>, 
                                №<?php echo e($project->contract_number ?? '-'); ?>

                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer d-flex p-2">
                            <a href="<?php echo e(route('client.projects.show', $project)); ?>" class="btn btn-sm btn-primary flex-grow-1">
                                <i class="fas fa-eye"></i>
                                <span class="ms-1">Просмотр</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        
        <div class="d-flex justify-content-center mt-4">
            <div class="pagination-container overflow-auto px-2 py-1 hide-scroll">
                <?php echo e($projects->links()); ?>

            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Получаем элементы формы
        const filterForm = document.getElementById('filterForm');
        const filterSelects = filterForm.querySelectorAll('select');
        const searchInput = filterForm.querySelector('input[name="search"]');
        
        // Авто-отправка формы при изменении селектов
        filterSelects.forEach(function(select) {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
        
        // Отправка формы поиска после паузы в наборе текста на десктопах
        let typingTimer;
        const doneTypingInterval = 800; // время в мс
        
        searchInput.addEventListener('keyup', function() {
            // Для мобильных устройств не используем автоматическую отправку
            if (window.innerWidth > 768) {
                clearTimeout(typingTimer);
                if (searchInput.value) {
                    typingTimer = setTimeout(function() {
                        filterForm.submit();
                    }, doneTypingInterval);
                }
            }
        });
        
        // Сбросить таймер, если пользователь продолжил печатать
        searchInput.addEventListener('keydown', function() {
            clearTimeout(typingTimer);
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\remont\resources\views/client/projects/index.blade.php ENDPATH**/ ?>