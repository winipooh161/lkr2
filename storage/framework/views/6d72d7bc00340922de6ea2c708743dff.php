<div class="mb-4">
    <h5 class="mb-3">Прочие файлы</h5>

    <?php if($project->otherFiles->isEmpty()): ?>
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе отображаются дополнительные материалы по объекту.
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 files-container">
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
                                <?php endif; ?>                                <a href="<?php echo e($file->client_download_url); ?>" class="btn btn-sm btn-outline-primary">
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
<?php /**PATH C:\OSPanel\domains\remont\resources\views/client/projects/tabs/other.blade.php ENDPATH**/ ?>