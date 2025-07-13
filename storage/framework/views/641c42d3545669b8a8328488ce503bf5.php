<div class="mb-4">
    <h5 class="mb-3">Дизайн-проект</h5>

    <?php if($project->designFiles->isEmpty()): ?>
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    Файлы дизайн-проекта еще не загружены.
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
                                        
                                        <div class="d-flex">                                            <a href="<?php echo e($file->client_download_url); ?>" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="fas fa-download me-1"></i>Скачать
                                            </a>
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
<?php /**PATH C:\OSPanel\domains\remont\resources\views/client/projects/tabs/design.blade.php ENDPATH**/ ?>