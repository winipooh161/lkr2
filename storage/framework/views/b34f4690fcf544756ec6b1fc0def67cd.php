<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
        <h5>Прочие файлы</h5>
        <button type="button" class="btn btn-primary btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#uploadOtherModal">
            <i class="fas fa-upload me-1"></i>Загрузить файлы
        </button>
    </div>

    <!-- Отображение прочих файлов -->
    <?php if($project->otherFiles->isEmpty()): ?>
        <div class="alert alert-info mb-4">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будет отображаться дополнительная информация по объекту.
                    Загрузите любые файлы, которые не подходят для других разделов.
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 files-container mb-4">
            <?php $__currentLoopData = $project->otherFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col file-item">
                    <div class="card h-100 other-file-card ">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start mb-3">
                                <div class="file-icon me-3">
                                    <i class="<?php echo e($file->file_icon); ?> fa-2x text-secondary"></i>
                                </div>
                                <div class="file-info flex-grow-1">
                                    <h6 class="mb-1 text-truncate" title="<?php echo e($file->original_name); ?>">
                                        <?php echo e($file->original_name); ?>

                                    </h6>
                                    <div class="small text-muted d-flex flex-wrap">
                                        <span class="me-2"><?php echo e($file->size_formatted); ?></span>
                                        <span><?php echo e($file->created_at->format('d.m.Y')); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($file->description): ?>
                                <p class="card-text small mb-3"><?php echo e($file->description); ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-end">
                                <?php if($file->is_image): ?>
                                    <a href="<?php echo e($file->file_url); ?>" class="btn btn-sm btn-outline-info me-1" target="_blank" title="Просмотр">
                                        <i class="fas fa-eye me-1"></i><span class="d-none d-md-inline">Просмотр</span>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo e(route('partner.project-files.download', ['project' => $project->id, 'file' => $file->id])); ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-download me-1"></i><span class="d-none d-md-inline">Скачать</span>
                                </a>
                                <form action="<?php echo e(route('partner.project-files.destroy', ['project' => $project->id, 'file' => $file->id])); ?>" method="POST" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены?')">
                                        <i class="fas fa-trash me-1"></i><span class="d-none d-md-inline">Удалить</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <!-- Отображение всех файлов проекта -->
    <h5 class="mb-3">Все файлы объекта</h5>
    
    <?php
        $allFiles = collect([]);
        
        // Собираем файлы из всех разделов
        $allFiles = $allFiles->merge($project->designFiles);
        $allFiles = $allFiles->merge($project->schemeFiles);
        $allFiles = $allFiles->merge($project->documentFiles);
        $allFiles = $allFiles->merge($project->contractFiles);
        
        // Сортируем файлы по дате создания (от новых к старым)
        $allFiles = $allFiles->sortByDesc('created_at');
    ?>
    
    <?php if($allFiles->isEmpty()): ?>
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    На данный момент в проекте нет загруженных файлов.
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 files-container">
            <?php $__currentLoopData = $allFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col file-item">
                    <div class="card h-100 other-file-card ">
                        <?php if($file->is_image): ?>
                            <div class="card-img-top design-preview">
                                <a href="<?php echo e($file->file_url); ?>" target="_blank" data-lightbox="all-files" data-title="<?php echo e($file->original_name); ?>">
                                    <img src="<?php echo e($file->file_url); ?>" class="img-fluid" alt="<?php echo e($file->original_name); ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start mb-2">
                                <div class="file-icon me-3">
                                    <i class="<?php echo e($file->file_icon); ?> fa-2x <?php echo e($file->file_type == 'design' ? 'text-primary' : ($file->file_type == 'document' ? 'text-success' : ($file->file_type == 'contract' ? 'text-danger' : 'text-secondary'))); ?>"></i>
                                </div>
                                <div class="file-info flex-grow-1">
                                    <h6 class="mb-1 text-truncate" title="<?php echo e($file->original_name); ?>">
                                        <?php echo e($file->original_name); ?>

                                    </h6>
                                    <div class="small text-muted d-flex flex-wrap">
                                        <span class="me-2 badge <?php echo e($file->file_type == 'design' ? 'bg-primary' : ($file->file_type == 'document' ? 'bg-success' : ($file->file_type == 'contract' ? 'bg-danger' : ($file->file_type == 'scheme' ? 'bg-info' : 'bg-secondary')))); ?>">
                                            <?php echo e($file->file_type == 'design' ? 'Дизайн' : ($file->file_type == 'document' ? 'Документ' : ($file->file_type == 'contract' ? 'Договор' : ($file->file_type == 'scheme' ? 'Схема' : 'Прочее')))); ?>

                                        </span>
                                        <?php if($file->document_type): ?>
                                            <span class="me-2 badge bg-light text-dark"><?php echo e(ucfirst($file->document_type)); ?></span>
                                        <?php endif; ?>
                                        <span class="me-2"><?php echo e($file->size_formatted); ?></span>
                                        <span><?php echo e($file->created_at->format('d.m.Y')); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($file->description): ?>
                                <p class="card-text small mb-3"><?php echo e($file->description); ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-end">
                                <?php if($file->is_image): ?>
                                    <a href="<?php echo e($file->file_url); ?>" class="btn btn-sm btn-outline-info me-1" target="_blank" title="Просмотр">
                                        <i class="fas fa-eye me-1"></i><span class="d-none d-md-inline">Просмотр</span>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo e(route('partner.project-files.download', ['project' => $project->id, 'file' => $file->id])); ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-download me-1"></i><span class="d-none d-md-inline">Скачать</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно загрузки прочих файлов -->
<div class="modal fade" id="uploadOtherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Загрузка файлов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo e(route('partner.project-files.store', $project)); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="file_type" value="other">
                    
                    <!-- Основная форма (будет скрыта при загрузке) -->
                    <div class="mb-3">
                        <label for="otherFile" class="form-label">Выберите файл</label>
                        <input type="file" class="form-control" id="otherFile" name="file" required>
                        <div class="form-text">Поддерживаются все форматы файлов без ограничений по размеру.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="otherType" class="form-label">Категория файла</label>
                        <select class="form-select" id="otherType" name="document_type">
                            <option value="photo">Фото</option>
                            <option value="scan">Сканированный документ</option>
                            <option value="report">Отчёт</option>
                            <option value="reference">Справочный материал</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fileDescription" class="form-label">Описание (необязательно)</label>
                        <textarea class="form-control" id="fileDescription" name="description" rows="3" placeholder="Добавьте краткое описание файла"></textarea>
                    </div>
                    
                    <!-- Контейнер прогресса загрузки (по умолчанию скрыт) -->
                    <div class="upload-progress d-none">
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress-info text-center">Загрузка...</div>
                    </div>
                    
                    <div class="d-flex flex-column flex-md-row justify-content-end">
                        <button type="button" class="btn btn-secondary mb-2 mb-md-0 me-md-2 w-100 w-md-auto" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary w-100 w-md-auto upload-file-btn">Загрузить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 576px) {
    .other-file-card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .other-file-card .file-icon i {
        font-size: 1.5rem;
    }
    
    .other-file-card h6 {
        font-size: 0.9rem;
    }
    
    .other-file-card .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .alert {
        padding: 0.75rem 1rem;
    }
    
    .alert .fa-lg {
        font-size: 1.25rem;
    }
}
</style>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/partner/projects/tabs/other.blade.php ENDPATH**/ ?>