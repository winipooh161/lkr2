<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
        <h5>Схемы и чертежи</h5>
        <button type="button" class="btn btn-primary btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#uploadSchemeModal">
            <i class="fas fa-upload me-1"></i>Загрузить схемы
        </button>
    </div>

    <?php if($project->schemeFiles->isEmpty()): ?>
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будут отображаться схемы и чертежи по объекту. 
                    Нажмите на кнопку "Загрузить схемы", чтобы добавить схемы и чертежи.
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Категории схем на вкладках -->
        <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto hide-scroll" id="schemeTabs" role="tablist">
            <?php
                $schemeCategories = $project->schemeFiles->pluck('document_type')->unique();
                $firstCategory = true;
            ?>
            
            <?php $__currentLoopData = $schemeCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo e($firstCategory ? 'active' : ''); ?>" 
                            id="scheme-tab-<?php echo e($loop->index); ?>" 
                            data-bs-toggle="tab" 
                            data-bs-target="#scheme-<?php echo e($loop->index); ?>" 
                            type="button" 
                            role="tab">
                        <?php echo e(ucfirst($category)); ?>

                    </button>
                </li>
                <?php $firstCategory = false; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        
        <!-- Содержимое вкладок со схемами -->
        <div class="tab-content" id="schemeTabContent">
            <?php $firstCategory = true; ?>
            
            <?php $__currentLoopData = $schemeCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="tab-pane fade <?php echo e($firstCategory ? 'show active' : ''); ?>" 
                     id="scheme-<?php echo e($loop->index); ?>" 
                     role="tabpanel" 
                     aria-labelledby="scheme-tab-<?php echo e($loop->index); ?>">
                    
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                        <?php $__currentLoopData = $project->schemeFiles->where('document_type', $category); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col file-item" data-file-id="<?php echo e($file->id); ?>">
                                <div class="card h-100 scheme-file-card">
                                    <?php if($file->is_image): ?>
                                        <div class="card-img-top scheme-preview">
                                            <a href="<?php echo e($file->file_url); ?>" target="_blank" data-lightbox="scheme-<?php echo e($category); ?>" data-title="<?php echo e($file->original_name); ?>">
                                                <img src="<?php echo e($file->file_url); ?>" class="img-fluid" alt="<?php echo e($file->original_name); ?>">
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="card-img-top scheme-preview d-flex align-items-center justify-content-center bg-light">
                                            <i class="<?php echo e($file->file_icon); ?> fa-3x text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h6 class="card-title text-truncate" title="<?php echo e($file->original_name); ?>"><?php echo e($file->original_name); ?></h6>
                                        <p class="card-text text-muted small mb-2">
                                            <span><?php echo e($file->size_formatted); ?></span>
                                            <span class="mx-1">•</span>
                                            <span><?php echo e($file->created_at->format('d.m.Y')); ?></span>
                                        </p>
                                        <?php if($file->description): ?>
                                            <p class="card-text small mb-3"><?php echo e($file->description); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between">
                                        <a href="<?php echo e(route('partner.project-files.download', ['project' => $project->id, 'file' => $file->id])); ?>" class="btn btn-sm btn-outline-primary">
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
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php $firstCategory = false; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно загрузки схем -->
<div class="modal fade" id="uploadSchemeModal" tabindex="-1" aria-labelledby="uploadSchemeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadSchemeModalLabel">Загрузить схемы и чертежи</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="uploadSchemeForm" method="POST" enctype="multipart/form-data" action="<?php echo e(route('partner.project-files.store', $project)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="file_type" value="scheme">
                    
                    <div class="mb-3">
                        <label for="schemeFile" class="form-label">Выберите файлы для загрузки</label>
                        <input class="form-control" type="file" id="schemeFile" name="file" required>
                        <div class="form-text">Поддерживаются все форматы файлов: JPG, PNG, PDF, DWG, DXF, SVG, CAD и др. без ограничений по размеру.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="schemeType" class="form-label">Тип схемы/чертежа</label>
                        <select class="form-select" id="schemeType" name="document_type">
                            <option value="floor">Планы этажей</option>
                            <option value="electrical">Электрические схемы</option>
                            <option value="plumbing">Водоснабжение/Канализация</option>
                            <option value="ventilation">Вентиляция/Кондиционирование</option>
                            <option value="construction">Строительные чертежи</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="schemeDescription" class="form-label">Описание файла (необязательно)</label>
                        <textarea class="form-control" id="schemeDescription" name="description" rows="2" placeholder="Добавьте краткое описание файла"></textarea>
                    </div>
                    
                    <!-- Контейнер прогресса загрузки (по умолчанию скрыт) -->
                    <div class="upload-progress d-none">
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress-info text-center">Загрузка...</div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary upload-file-btn">Загрузить</button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<style>
.scheme-preview {
    height: 160px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
}

.scheme-preview img {
    max-height: 100%;
    object-fit: cover;
    width: 100%;
}

.scheme-file-card {
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.125);
}

.scheme-file-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.hide-scroll {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE/Edge */
}

.hide-scroll::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}
</style>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/partner/projects/tabs/schemes.blade.php ENDPATH**/ ?>