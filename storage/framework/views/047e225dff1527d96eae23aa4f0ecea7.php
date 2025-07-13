

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fs-2"><?php echo e(__('Аналитическая панель')); ?></h1>
            <p class="text-muted">Обзор вашей деятельности и управление объектами</p>
        </div>
    </div>

    <!-- Карточки с основными показателями -->
    <div class="row mb-4">
        <!-- Активные объекты -->
        <div class="col-12 col-md-6 col-xl-3 mb-4 mb-xl-0">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-muted mb-0">Активные объекты</h6>
                        <div class="feature-icon bg-primary bg-gradient text-white rounded-circle">
                            <i class="fas fa-hammer"></i>
                        </div>
                    </div>
                    <h2 class="fs-1 fw-bold mb-0"><?php echo e($activeProjects); ?></h2>
                    <p class="text-muted mb-0">В работе</p>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo e(($activeProjects / max(1, $totalProjects)) * 100); ?>%" aria-valuenow="<?php echo e($activeProjects); ?>" aria-valuemin="0" aria-valuemax="<?php echo e($totalProjects); ?>"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Завершенные объекты -->
        <div class="col-12 col-md-6 col-xl-3 mb-4 mb-xl-0">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-muted mb-0">Завершенные объекты</h6>
                        <div class="feature-icon bg-success bg-gradient text-white rounded-circle">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <h2 class="fs-1 fw-bold mb-0"><?php echo e($completedProjects); ?></h2>
                    <p class="text-muted mb-0">Завершены</p>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo e(($completedProjects / max(1, $totalProjects)) * 100); ?>%" aria-valuenow="<?php echo e($completedProjects); ?>" aria-valuemin="0" aria-valuemax="<?php echo e($totalProjects); ?>"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Финансовые показатели -->
        <div class="col-12 col-md-6 col-xl-3 mb-4 mb-xl-0">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-muted mb-0">Общая сумма</h6>
                        <div class="feature-icon bg-info bg-gradient text-white rounded-circle">
                            <i class="fas fa-ruble-sign"></i>
                        </div>
                    </div>
                    <h2 class="fs-1 fw-bold mb-0"><?php echo e(number_format($totalAmount, 0, '', ' ')); ?></h2>
                    <p class="text-muted mb-0">Работы и материалы</p>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Документы и файлы -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-muted mb-0">Всего файлов</h6>
                        <div class="feature-icon bg-warning bg-gradient text-white rounded-circle">
                            <i class="fas fa-file"></i>
                        </div>
                    </div>
                    <h2 class="fs-1 fw-bold mb-0"><?php echo e($totalFiles); ?></h2>
                    <p class="text-muted mb-0">Документы и фото</p>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- График динамики проектов -->
        <div class="col-12 col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">Динамика проектов</h5>
                    <p class="text-muted small mb-0">Количество активных и завершенных проектов за последние 6 месяцев</p>
                </div>
                <div class="card-body">
                    <canvas id="projectsChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Распределение проектов по статусам -->
        <div class="col-12 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">Статусы проектов</h5>
                    <p class="text-muted small mb-0">Распределение по текущим статусам</p>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Финансовая динамика -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Финансовая динамика</h5>
                            <p class="text-muted small mb-0">Суммы работ и материалов по месяцам</p>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary active" data-period="monthly">Месяц</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-period="quarterly">Квартал</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-period="yearly">Год</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="financialChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Последние объекты -->
        <div class="col-12 col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Последние объекты</h5>
                    <a href="<?php echo e(route('partner.projects.index')); ?>" class="btn btn-sm btn-primary">Все объекты</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Клиент</th>
                                    <th>Адрес</th>
                                    <th>Статус</th>
                                    <th>Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $recentProjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle text-center me-2 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user text-muted"></i>
                                            </div>
                                            <div><?php echo e($project->client_name); ?></div>
                                        </div>
                                    </td>
                                    <td class="text-truncate" style="max-width: 200px;"><?php echo e($project->full_address); ?></td>
                                    <td><?php echo $project->statusBadge(); ?></td>
                                    <td><?php echo e(number_format($project->total_amount, 0, '', ' ')); ?> ₽</td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">Нет данных о проектах</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Типы объектов и работ -->
        <div class="col-12 col-lg-6 mb-4">
            <div class="row h-100">
                <!-- Распределение типов объектов -->
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">Типы объектов</h5>
                            <p class="text-muted small mb-0">Распределение по категориям</p>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <canvas id="objectTypeChart"></canvas>
                                </div>
                                <div class="col-md-6">
                                    <div class="chart-legend mt-3 mt-md-0">
                                        <?php $__currentLoopData = $objectTypeStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="legend-item d-flex align-items-center mb-2">
                                            <div class="legend-color me-2" style="background-color: <?php echo e($objectTypeColors[$loop->index % count($objectTypeColors)]); ?>;"></div>
                                            <div class="d-flex justify-content-between w-100">
                                                <span><?php echo e($type); ?></span>
                                                <span class="fw-bold"><?php echo e($count); ?></span>
                                            </div>
                                        </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Календарь активности -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">Активность</h5>
                            <p class="text-muted small mb-0">Количество событий по дням месяца</p>
                        </div>
                        <div class="card-body">
                            <div id="activity-heatmap" class="activity-calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Нижний ряд с карточками быстрого доступа -->
    <div class="row">
        <div class="col-12 col-md-6 col-xl-3 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="feature-icon-large bg-primary-subtle text-primary rounded mx-auto mb-3">
                        <i class="fas fa-plus-circle fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-2">Новый объект</h5>
                    <p class="card-text text-muted mb-3">Создать новый объект и начать работу с клиентом</p>
                    <a href="<?php echo e(route('partner.projects.create')); ?>" class="btn btn-primary">Создать</a>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="feature-icon-large bg-success-subtle text-success rounded mx-auto mb-3">
                        <i class="fas fa-file-invoice fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-2">Сметы</h5>
                    <p class="card-text text-muted mb-3">Управление сметами для проектов</p>
                    <a href="<?php echo e(route('partner.estimates.index')); ?>" class="btn btn-success">Перейти</a>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="feature-icon-large bg-warning-subtle text-warning rounded mx-auto mb-3">
                        <i class="fas fa-calendar-alt fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-2">Графики работ</h5>
                    <p class="card-text text-muted mb-3">Просмотр и планирование работ</p>
                    <a href="<?php echo e(route('partner.projects.index')); ?>?view=calendar" class="btn btn-warning">Календарь</a>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="feature-icon-large bg-info-subtle text-info rounded mx-auto mb-3">
                        <i class="fas fa-user-cog fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-2">Профиль</h5>
                    <p class="card-text text-muted mb-3">Управление данными вашего профиля</p>
                    <a href="<?php echo e(route('partner.profile')); ?>" class="btn btn-info">Настройки</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- CalHeatmap для календаря активности -->
<script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
<script src="https://cdn.jsdelivr.net/npm/cal-heatmap@4"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cal-heatmap@4/cal-heatmap.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация графика динамики проектов
    const projectsCtx = document.getElementById('projectsChart').getContext('2d');
    const projectsChart = new Chart(projectsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($projectChartLabels); ?>,
            datasets: [
                {
                    label: 'Активные проекты',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    tension: 0.3,
                    fill: true,
                    data: <?php echo json_encode($projectChartActive); ?>

                },
                {
                    label: 'Завершенные проекты',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(25, 135, 84, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    tension: 0.3,
                    fill: true,
                    data: <?php echo json_encode($projectChartCompleted); ?>

                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 6
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    boxWidth: 10,
                    usePointStyle: true,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + ' проектов';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    },
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Инициализация пончикового графика статусов
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($statusStats)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($statusStats)); ?>,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.8)',
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(108, 117, 125, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 6,
                        padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + ' проектов';
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // Инициализация пончикового графика типов объектов
    const objectTypeCtx = document.getElementById('objectTypeChart').getContext('2d');
    const objectTypeChart = new Chart(objectTypeCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($objectTypeStats)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($objectTypeStats)); ?>,
                backgroundColor: <?php echo json_encode($objectTypeColors); ?>,
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + ' объектов';
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // Инициализация графика финансовой динамики
    const financialCtx = document.getElementById('financialChart').getContext('2d');
    const financialChart = new Chart(financialCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($financeChartLabels); ?>,
            datasets: [
                {
                    label: 'Работы',
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1,
                    data: <?php echo json_encode($financeChartWork); ?>

                },
                {
                    label: 'Материалы',
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 1,
                    data: <?php echo json_encode($financeChartMaterials); ?>

                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 6
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat('ru-RU').format(context.raw) + ' ₽';
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: {
                        display: false
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + ' млн ₽';
                            } else if (value >= 1000) {
                                return (value / 1000).toFixed(0) + ' тыс ₽';
                            }
                            return value + ' ₽';
                        }
                    }
                }
            }
        }
    });

    // Переключатели периода для финансового графика
    const periodButtons = document.querySelectorAll('[data-period]');
    periodButtons.forEach(button => {
        button.addEventListener('click', function() {
            periodButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const period = this.getAttribute('data-period');
            let labels, workData, materialsData;
            
            // Здесь должен быть AJAX-запрос для получения данных за выбранный период
            // Для демонстрации используем заглушку
            if (period === 'quarterly') {
                labels = ['Q1 2025', 'Q2 2025', 'Q3 2025', 'Q4 2025'];
                workData = [3200000, 4100000, 3700000, 4500000];
                materialsData = [1800000, 2300000, 2100000, 2500000];
            } else if (period === 'yearly') {
                labels = ['2023', '2024', '2025'];
                workData = [10500000, 12300000, 15500000];
                materialsData = [6200000, 7100000, 8700000];
            } else {
                // Возвращаемся к месячным данным
                labels = <?php echo json_encode($financeChartLabels); ?>;
                workData = <?php echo json_encode($financeChartWork); ?>;
                materialsData = <?php echo json_encode($financeChartMaterials); ?>;
            }
            
            financialChart.data.labels = labels;
            financialChart.data.datasets[0].data = workData;
            financialChart.data.datasets[1].data = materialsData;
            financialChart.update();
        });
    });

    // Инициализация тепловой карты активности
    const activityData = <?php echo json_encode($activityData); ?>;
    const cal = new CalHeatmap();
    cal.paint({
        itemSelector: '#activity-heatmap',
        range: 3,
        domain: {
            type: 'month',
            gutter: 10,
            label: { text: 'MMM', textAlign: 'start' }
        },
        subDomain: {
            type: 'day',
            width: 12,
            height: 12,
            gutter: 3,
            radius: 2
        },
        data: {
            source: activityData,
            type: 'json',
            x: 'date',
            y: 'count'
        },
        scale: {
            color: {
                range: ['#e8f0fe', '#0d6efd'],
                interpolate: 'hsl',
                type: 'linear',
                domain: [0, 10]
            }
        }
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
<style>
/* Стили для панели управления партнера */
.feature-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-icon-large {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-sm {
    width: 30px;
    height: 30px;
}

.chart-legend .legend-color {
    width: 14px;
    height: 14px;
    border-radius: 4px;
}

.activity-calendar {
    width: 100%;
    overflow-x: auto;
    margin-bottom: 10px;
}

/* Адаптивные стили для мобильных устройств */
@media (max-width: 768px) {
    .card {
        margin-bottom: 1rem;
    }
    
    h1 {
        font-size: 1.5rem !important;
    }
    
    .table-responsive {
        border: none;
    }
    
    .card-header {
        padding: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .feature-icon {
        width: 32px;
        height: 32px;
    }
    
    .feature-icon-large {
        width: 50px;
        height: 50px;
    }
    
    .chart-legend {
        font-size: 0.8rem;
    }
    
    .chart-legend .legend-item {
        margin-bottom: 0.5rem;
    }
    
    /* Улучшенная прокрутка для календаря активности */
    .activity-calendar {
        padding-bottom: 10px;
    }
    
    /* Улучшение читаемости таблиц на мобильных */
    .table th, .table td {
        font-size: 0.85rem;
        padding: 0.5rem;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
}
</style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\OSPanel\domains\remont\resources\views/partner/dashboard.blade.php ENDPATH**/ ?>