<div class="project-schedule mb-4">    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <h5 class="mb-2 mb-md-0">План-график</h5>
        <div>
            <a href="<?php echo e(route('client.projects.show', ['project' => $project->id, 'tab' => 'calendar'])); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-calendar-alt me-1"></i>Календарный вид графика  БЕТА-ВЕРСИЯ
            </a>
        </div>
    </div>
    
    <!-- Блок с информацией о продолжительности проекта -->
    <div class="schedule-info mb-4 bg-light p-3 rounded">
        <div class="row">
            <div class="col-md-6">
                <h6>Срок ремонта:</h6>
                <div class="d-flex flex-wrap">
                    <?php if(isset($scheduleMetadata) && isset($scheduleMetadata['total_days'])): ?>
                        <span class="me-3"><?php echo e($scheduleMetadata['total_days']); ?> <?php echo e(\App\Helpers\TextHelper::pluralize($scheduleMetadata['total_days'], ['день', 'дня', 'дней'])); ?></span>
                        <span class="me-3"><?php echo e($scheduleMetadata['total_weeks']); ?> <?php echo e(\App\Helpers\TextHelper::pluralize($scheduleMetadata['total_weeks'], ['неделя', 'недели', 'недель'])); ?></span>
                        <span><?php echo e($scheduleMetadata['total_months']); ?> <?php echo e(\App\Helpers\TextHelper::pluralize($scheduleMetadata['total_months'], ['месяц', 'месяца', 'месяцев'])); ?></span>
                    <?php else: ?>
                        <span class="text-muted">Информация отсутствует</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-md-end align-items-center mt-2 mt-md-0">
                    <?php if($project->schedule_link): ?>
                        <a href="<?php echo e($project->schedule_link); ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-link me-1"></i>Открыть линейный график
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if($project->schedule_link): ?>
        <div class="mt-2">
            <strong>Ссылка на график:</strong> 
            <a href="<?php echo e($project->schedule_link); ?>" target="_blank" class="ms-2"><?php echo e($project->schedule_link); ?></a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Таблица с данными графика -->
    <div class="schedule-table-container">
        <?php if(empty($scheduleItems) || count($scheduleItems) == 0): ?>
            <div class="schedule-placeholder">
                <div class="text-center p-5">
                    <i class="fas fa-calendar-alt fa-3x mb-3 text-muted"></i>
                    <h5 class="text-muted">План-график не доступен</h5>
                    <p>В настоящее время план-график работ не загружен</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Блок с фильтрами -->
            <div class="schedule-controls mb-3">
                <div class="row g-3">
                    <div class="col-md-5 col-12">
                        <div class="input-group flex-wrap mb-2 mb-md-0">
                            <input type="text" class="form-control" id="scheduleStartDate" placeholder="Начало" value="<?php echo e($scheduleMetadata['min_date'] ?? ''); ?>" disabled>
                            <span class="input-group-text">—</span>
                            <input type="text" class="form-control" id="scheduleEndDate" placeholder="Конец" value="<?php echo e($scheduleMetadata['max_date'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                    <div class="col-md-7 col-12">
                        <div class="d-flex flex-wrap justify-content-md-end">
                            <div class="btn-group me-2 mb-2 mb-md-0 overflow-auto hide-scroll" style="max-width: 100%;">
                                <input type="checkbox" class="btn-check" id="filterCompleted" checked autocomplete="off">
                                <label class="btn btn-sm btn-outline-success" for="filterCompleted">
                                    <i class="fas fa-check-circle"></i>
                                    <span class="d-none d-sm-inline ms-1">Завершено</span>
                                </label>
                                
                                <input type="checkbox" class="btn-check" id="filterInProgress" checked autocomplete="off">
                                <label class="btn btn-sm btn-outline-primary" for="filterInProgress">
                                    <i class="fas fa-spinner"></i>
                                    <span class="d-none d-sm-inline ms-1">В работе</span>
                                </label>
                                
                                <input type="checkbox" class="btn-check" id="filterWaiting" checked autocomplete="off">
                                <label class="btn btn-sm btn-outline-secondary" for="filterWaiting">
                                    <i class="fas fa-pause"></i>
                                    <span class="d-none d-sm-inline ms-1">Ожидание</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive-all">
                <table class="table table-bordered table-hover" id="scheduleTable">
                    <thead>
                        <tr>
                            <th style="width: 5%">Вид</th>
                            <th style="width: 45%">Наименование</th>
                            <th style="width: 15%">Статус</th>
                            <th style="width: 12%">Начало</th>
                            <th style="width: 12%">Конец</th>
                            <th style="width: 11%">Дней</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <?php $__currentLoopData = $scheduleItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="schedule-item <?php echo e($item['Статус'] == 'Готово' ? 'table-success' : ($item['Статус'] == 'В работе' ? 'table-primary' : 'table-secondary')); ?>"
                            data-status="<?php echo e($item['Статус']); ?>">
                            <td data-label="Вид">
                                <?php if(strtolower($item['Вид'] ?? '') == 'закупка'): ?>
                                    <span class="badge bg-info"><i class="fas fa-shopping-cart me-1"></i><span class="d-none d-sm-inline">Закупка</span></span>
                                <?php elseif(strtolower($item['Вид'] ?? '') == 'работа'): ?>
                                    <span class="badge bg-secondary"><i class="fas fa-tools me-1"></i><span class="d-none d-sm-inline">Работа</span></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark"><i class="fas fa-question-circle me-1"></i><span class="d-none d-sm-inline">Другое</span></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Наименование"><?php echo e($item['Наименование'] ?? ''); ?></td>
                            <td data-label="Статус">
                                <span class="badge <?php echo e($item['Статус'] == 'Готово' ? 'bg-success' : ($item['Статус'] == 'В работе' ? 'bg-primary' : 'bg-secondary')); ?>">
                                    <?php echo e($item['Статус'] ?? 'Ожидание'); ?>

                                </span>
                            </td>
                            <td data-label="Начало"><?php echo e($item['Начало'] ?? ''); ?></td>
                            <td data-label="Конец"><?php echo e($item['Конец'] ?? ''); ?></td>
                            <td data-label="Дней"><?php echo e($item['Дней'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фильтрация элементов расписания
    const filterCompletedCheck = document.getElementById('filterCompleted');
    const filterInProgressCheck = document.getElementById('filterInProgress');
    const filterWaitingCheck = document.getElementById('filterWaiting');
    
    if (filterCompletedCheck && filterInProgressCheck && filterWaitingCheck) {
        const items = document.querySelectorAll('.schedule-item');
        
        function applyFilters() {
            const showCompleted = filterCompletedCheck.checked;
            const showInProgress = filterInProgressCheck.checked;
            const showWaiting = filterWaitingCheck.checked;
            
            items.forEach(item => {
                const status = item.dataset.status;
                
                if ((status === 'Готово' && !showCompleted) || 
                    (status === 'В работе' && !showInProgress) || 
                    (status === 'Ожидание' && !showWaiting)) {
                    item.style.display = 'none';
                } else {
                    item.style.display = '';
                }
            });
        }
        
        // Назначаем обработчики
        filterCompletedCheck.addEventListener('change', applyFilters);
        filterInProgressCheck.addEventListener('change', applyFilters);
        filterWaitingCheck.addEventListener('change', applyFilters);
    }
    
    // Добавляем класс для мобильной адаптации таблицы при загрузке
    const table = document.getElementById('scheduleTable');
    if (table && window.innerWidth <= 768) {
        table.classList.add('table-card-view');
    }
    
    // Добавляем обработчик изменения размера окна
    window.addEventListener('resize', function() {
        const table = document.getElementById('scheduleTable');
        if (table) {
            if (window.innerWidth <= 768) {
                table.classList.add('table-card-view');
            } else {
                table.classList.remove('table-card-view');
            }
        }
    });
});
</script>

<style>
@media (max-width: 768px) {
    .schedule-placeholder {
        padding: 10px !important;
    }
    
    .schedule-info {
        padding: 10px !important;
    }
    
    /* Улучшенная мобильная таблица */
    .table-card-view {
        border: none;
    }
    
    .table-card-view tr {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 15px;
        border-radius: 6px;
    }
    
    .table-card-view td[data-label]:before {
        font-weight: 600;
        color: #495057;
    }
    
    .table-card-view td {
        padding: 10px 15px;
    }
}
</style>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/client/projects/tabs/schedule.blade.php ENDPATH**/ ?>