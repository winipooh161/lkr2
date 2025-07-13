<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
        <h5>Дизайн-проект</h5>
        <button type="button" class="btn btn-primary btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#uploadDesignModal">
            <i class="fas fa-upload me-1"></i>Загрузить файлы дизайна
        </button>
    </div>

    <?php if($project->designFiles->isEmpty()): ?>
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будут отображаться файлы дизайн-проекта. 
                    Нажмите на кнопку "Загрузить файлы дизайна", чтобы добавить файлы визуализации и дизайна.
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Категории дизайн-файлов на вкладках -->
        <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto hide-scroll" id="designTabs" role="tablist">
            <?php
                $designCategories = $project->designFiles->pluck('document_type')->unique();
                $firstCategory = true;
            ?>
            
            <?php $__currentLoopData = $designCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo e($firstCategory ? 'active' : ''); ?>" 
                            id="design-tab-<?php echo e($loop->index); ?>" 
                            data-bs-toggle="tab" 
                            data-bs-target="#design-<?php echo e($loop->index); ?>" 
                            type="button" 
                            role="tab">
                        <?php echo e(ucfirst($category)); ?>

                    </button>
                </li>
                <?php $firstCategory = false; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        
        <!-- Содержимое вкладок с дизайн-файлами -->
        <div class="tab-content" id="designTabContent">
            <?php $firstCategory = true; ?>
            
            <?php $__currentLoopData = $designCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="tab-pane fade <?php echo e($firstCategory ? 'show active' : ''); ?>" 
                     id="design-<?php echo e($loop->index); ?>" 
                     role="tabpanel" 
                     aria-labelledby="design-tab-<?php echo e($loop->index); ?>">
                    
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                        <?php $__currentLoopData = $project->designFiles->where('document_type', $category); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col">
                                <div class="card h-100 design-file-card ">
                                    <?php if($file->is_image): ?>
                                        <div class="card-img-top design-preview">
                                            <a href="<?php echo e($file->file_url); ?>" target="_blank" data-lightbox="design-<?php echo e($category); ?>" data-title="<?php echo e($file->original_name); ?>">
                                                <img src="<?php echo e($file->file_url); ?>" class="img-fluid" alt="<?php echo e($file->original_name); ?>">
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="card-img-top design-preview d-flex align-items-center justify-content-center bg-light">
                                            <i class="<?php echo e($file->file_icon); ?> fa-3x text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body p-3">
                                        <h6 class="card-title text-truncate" title="<?php echo e($file->original_name); ?>">
                                            <?php echo e($file->original_name); ?>

                                        </h6>
                                        
                                        <p class="card-text small text-muted mb-2">
                                            <span><?php echo e($file->size_formatted); ?></span>
                                            <span class="mx-1">•</span>
                                            <span><?php echo e($file->created_at->format('d.m.Y')); ?></span>
                                        </p>
                                        
                                        <?php if($file->description): ?>
                                            <p class="card-text small mb-3"><?php echo e($file->description); ?></p>
                                        <?php endif; ?>
                                          <div class="d-flex">
                                            <a href="<?php echo e(route('partner.project-files.download', ['project' => $project->id, 'file' => $file->id])); ?>" class="btn btn-sm btn-outline-primary me-2">
                                                <i class="fas fa-download me-1"></i>Скачать
                                            </a>
                                            <form action="<?php echo e(route('partner.project-files.destroy', ['project' => $project->id, 'file' => $file->id])); ?>" method="POST">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены?')">
                                                    <i class="fas fa-trash me-1"></i>Удалить
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php $firstCategory = false; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно загрузки файлов дизайна -->
<div class="modal fade" id="uploadDesignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Загрузка файлов дизайна</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo e(route('partner.project-files.store', $project)); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="file_type" value="design">
                    
                    <!-- Основная форма (будет скрыта при загрузке) -->
                    <div class="mb-3">
                        <label for="designFile" class="form-label">Выберите файл</label>
                        <input type="file" class="form-control" id="designFile" name="file" required>
                        <div class="form-text">Поддерживаются все форматы файлов: JPG, PNG, PDF, TIFF, SVG, PSD, AI, WEBP, DWG, RAW и др. без ограничений по размеру.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="designType" class="form-label">Категория файла</label>
                        <select class="form-select" id="designType" name="document_type">
                            <option value="visualization">Визуализация</option>
                            <option value="drawings">Чертежи</option>
                            <option value="3d">3D модели</option>
                            <option value="materials">Материалы</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="designDescription" class="form-label">Описание (необязательно)</label>
                        <textarea class="form-control" id="designDescription" name="description" rows="3" placeholder="Добавьте краткое описание файла"></textarea>
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
/* Стили для адаптивного отображения на мобильных */
.design-preview {
    height: 160px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.design-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@media (max-width: 576px) {
    .design-file-card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .design-preview {
        height: 140px; /* Уменьшаем высоту для мобильных */
    }
    
    .design-file-card .card-body {
        padding: 0.75rem;
    }
    
    .design-file-card h6 {
        font-size: 0.9rem;
    }
    
    .design-file-card .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.4rem;
    }
    
    /* Улучшенные вкладки для мобильных устройств */
    #designTabs {
        padding-bottom: 2px;
    }
    
    #designTabs .nav-link {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/partner/projects/tabs/design.blade.php ENDPATH**/ ?>