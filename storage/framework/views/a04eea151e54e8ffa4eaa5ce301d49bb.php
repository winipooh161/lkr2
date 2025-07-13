<h5 class="mb-3">Документация по объекту</h5>

<!-- Вкладки для разделов документации -->
<ul class="nav nav-tabs documentation-tabs mb-3" id="documentationTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="contract-docs-tab" data-bs-toggle="tab" 
                data-bs-target="#contract-docs" type="button" role="tab" 
                aria-controls="contract-docs" aria-selected="true">
            <i class="fas fa-file-signature me-1"></i>Договор
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="schemes-docs-tab" data-bs-toggle="tab" 
                data-bs-target="#schemes-docs" type="button" role="tab" 
                aria-controls="schemes-docs" aria-selected="false">
            <i class="fas fa-project-diagram me-1"></i>Схемы
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="work-docs-tab" data-bs-toggle="tab" 
                data-bs-target="#work-docs" type="button" role="tab" 
                aria-controls="work-docs" aria-selected="false">
            <i class="fas fa-file-alt me-1"></i>Рабочая документация
        </button>
    </li>
</ul>

<!-- Содержимое вкладок документации -->
<div class="tab-content" id="documentationTabsContent">
    <!-- Вкладка договора -->
    <div class="tab-pane fade show active" id="contract-docs" role="tabpanel" aria-labelledby="contract-docs-tab">
        <div class="contract-container mb-4">
            <?php if($project->contractFiles->isEmpty()): ?>
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2 fa-lg"></i>
                        <div>
                            В этом разделе будут отображаться документы договора и приложения к нему.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Контейнер для файлов договора -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                    <?php $__currentLoopData = $project->contractFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col">
                            <div class="card h-100 contract-file-card ">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="file-icon me-3">
                                            <i class="far <?php echo e($file->is_image ? 'fa-file-image' : 'fa-file-pdf'); ?> fa-2x text-primary"></i>
                                        </div>
                                        <div class="file-info">
                                            <h6 class="mb-0 text-truncate" title="<?php echo e($file->original_name); ?>">
                                                <?php echo e($file->original_name); ?>

                                            </h6>
                                            <small class="text-muted"><?php echo e($file->created_at->format('d.m.Y H:i')); ?></small>
                                        </div>
                                    </div>
                                    
                                    <?php if($file->description): ?>
                                        <p class="card-text small mb-3"><?php echo e($file->description); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-secondary"><?php echo e($file->size_formatted); ?></span>
                                        <div>
                                            <?php if($file->is_image): ?>
                                                <a href="<?php echo e($file->file_url); ?>" class="btn btn-sm btn-outline-info me-1" target="_blank">
                                                    <i class="fas fa-eye me-1"></i>Просмотр
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo e($file->client_download_url); ?>" class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download me-1"></i>Скачать
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Вкладка схем -->
    <div class="tab-pane fade" id="schemes-docs" role="tabpanel" aria-labelledby="schemes-docs-tab">
        <div class="mb-4">
            <?php if($project->schemeFiles->isEmpty()): ?>
                <div class="alert alert-info">
                    В этом разделе будут отображаться схемы и чертежи по объекту.
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 files-container">
                    <?php $__currentLoopData = $project->schemeFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col file-item" data-file-id="<?php echo e($file->id); ?>">
                            <div class="card h-100">
                                <?php if($file->is_image): ?>
                                    <div class="card-img-top file-preview" style="height: 140px; background-image: url('<?php echo e(asset('storage/project_files/' . $project->id . '/' . $file->filename)); ?>'); background-size: cover; background-position: center;"></div>
                                <?php else: ?>
                                    <div class="card-img-top text-center bg-light d-flex align-items-center justify-content-center" style="height: 140px;">
                                        <i class="<?php echo e($file->file_icon); ?> fa-3x text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h6 class="card-title text-truncate" title="<?php echo e($file->original_name); ?>"><?php echo e($file->original_name); ?></h6>
                                    <p class="card-text text-muted small mb-2">
                                        <span><?php echo e(number_format($file->size / 1024, 2)); ?> KB</span>
                                        <span class="ms-2"><?php echo e($file->created_at->format('d.m.Y H:i')); ?></span>
                                    </p>
                                    <p class="card-text small text-muted">
                                        <?php echo e($file->description); ?>

                                    </p>
                                </div>
                                <div class="card-footer">
                                    <a href="<?php echo e($file->client_download_url); ?>" class="btn btn-sm btn-outline-primary w-100" download>
                                        <i class="fas fa-download me-1"></i>Скачать
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Вкладка рабочей документации -->
    <div class="tab-pane fade" id="work-docs" role="tabpanel" aria-labelledby="work-docs-tab">
        <div class="mb-4">
            <!-- Документы -->
            <?php if($project->documentFiles->isEmpty()): ?>
                <div class="alert alert-info">
                    <div class="d-flex">
                        <i class="fas fa-info-circle me-2 fa-lg"></i>
                        <div>
                            В этом разделе будут отображаться документы по объекту.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Фильтры по типам документов -->
                <div class="mb-3">
                    <div class="document-filter btn-group flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary active" data-filter="all">
                            Все
                        </button>
                        <?php
                            $docTypes = $project->documentFiles->pluck('document_type')->unique();
                        ?>
                        
                        <?php $__currentLoopData = $docTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="<?php echo e($type); ?>">
                                <?php echo e(ucfirst($type)); ?>

                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <!-- Список документов -->
                <div class="documents-container">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 ">
                        <?php $__currentLoopData = $project->documentFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col document-item" data-type="<?php echo e($file->document_type); ?>">
                                <div class="card h-100 document-card ">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="document-icon me-3">
                                                <i class="far <?php echo e($file->file_icon); ?> fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0 text-truncate" title="<?php echo e($file->original_name); ?>">
                                                    <?php echo e($file->original_name); ?>

                                                </h6>
                                                <div class="document-meta small text-muted">
                                                    <span class="document-type"><?php echo e(ucfirst($file->document_type)); ?></span>
                                                    <span class="mx-1">•</span>
                                                    <span class="document-date"><?php echo e($file->created_at->format('d.m.Y')); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if($file->description): ?>
                                            <p class="card-text small mb-3"><?php echo e($file->description); ?></p>
                                        <?php endif; ?>
                                                          <div class="d-flex justify-content-between mt-2">
                            <span class="badge bg-secondary"><?php echo e($file->size_formatted); ?></span>
                            <?php if($file->document_type === 'estimate'): ?>
                                <?php
                                    // Найти estimate_id из описания файла
                                    $estimateId = null;
                                    if (preg_match('/estimate_id:(\d+)/', $file->description, $matches)) {
                                        $estimateId = $matches[1];
                                    } else {
                                        // Попытаться найти estimate по project_id
                                        $estimate = $project->estimates()->latest()->first();
                                        $estimateId = $estimate ? $estimate->id : null;
                                    }
                                ?>
                                <?php if($estimateId): ?>
                                    <a href="<?php echo e(route('client.estimates.download-pdf', $estimateId)); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download me-1"></i>Скачать
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-outline-secondary disabled">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Недоступно
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?php echo e($file->client_download_url); ?>" class="btn btn-sm btn-outline-primary" download>
                                    <i class="fas fa-download me-1"></i>Скачать
                                </a>
                            <?php endif; ?>
                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Переключение фильтров документов
    const filterButtons = document.querySelectorAll('.document-filter .btn');
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Сбрасываем активный класс со всех кнопок
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Добавляем активный класс нажатой кнопке
                this.classList.add('active');
                
                // Фильтрация документов
                const filterValue = this.getAttribute('data-filter');
                const documentItems = document.querySelectorAll('.document-item');
                
                documentItems.forEach(item => {
                    if (filterValue === 'all' || item.getAttribute('data-type') === filterValue) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    }

    // Сохранение активной подвкладки в localStorage
    const tabs = document.querySelectorAll('#documentationTabs .nav-link');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            localStorage.setItem('activeDocTab', this.getAttribute('id'));
        });
    });

    // Восстановление активной подвкладки из localStorage
    const activeTabId = localStorage.getItem('activeDocTab');
    if (activeTabId) {
        const activeTab = document.getElementById(activeTabId);
        if (activeTab) {
            const tabTrigger = new bootstrap.Tab(activeTab);
            tabTrigger.show();
        }
    }
});
</script>

<style>
/* Улучшение стилей для подвкладок документации */
.documentation-tabs {
    border-bottom: 1px solid rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.documentation-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    background: none;
    color: #6c757d;
    padding: 0.5rem 1rem;
    margin-right: 0.5rem;
}

.documentation-tabs .nav-link.active {
    border-color: #0d6efd;
    color: #0d6efd;
    background: transparent;
    font-weight: 500;
}

.documentation-tabs .nav-link:hover:not(.active) {
    border-color: rgba(13, 110, 253, 0.5);
    color: #0d6efd;
}

/* Адаптивные стили для мобильных устройств */
@media (max-width: 768px) {
    .documentation-tabs .nav-link {
        padding: 0.4rem 0.75rem;
        font-size: 0.9rem;
    }
    
    .documentation-tabs .nav-link i {
        margin-right: 0;
    }
    
    .document-filter {
        overflow-x: auto;
        white-space: nowrap;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
}

/* Стили для карточек файлов */
.file-preview {
    transition: all 0.2s ease;
}

.file-item:hover .file-preview {
    transform: scale(1.03);
}
</style>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/client/projects/tabs/documentation.blade.php ENDPATH**/ ?>