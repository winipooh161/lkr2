<h5 class="mb-3">Фотоотчет по объекту</h5>
    
<?php if($project->photos->isEmpty()): ?>
    <div class="alert alert-info">
        Пока не загружено ни одной фотографии.
    </div>
<?php else: ?>
    <ul class="nav nav-tabs mb-3" id="photosTab" role="tablist">
        <?php $__currentLoopData = $project->getPhotoCategories(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo e($index === 0 ? 'active' : ''); ?>" 
                        id="photo-tab-<?php echo e($index); ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#photo-content-<?php echo e($index); ?>"
                        type="button"
                        role="tab"
                        aria-controls="photo-content-<?php echo e($index); ?>"
                        aria-selected="<?php echo e($index === 0 ? 'true' : 'false'); ?>">
                    <?php echo e($category); ?>

                </button>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    
    <div class="tab-content" id="photosTabContent">
        <?php $__currentLoopData = $project->getPhotoCategories(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="tab-pane fade <?php echo e($index === 0 ? 'show active' : ''); ?>"
                 id="photo-content-<?php echo e($index); ?>"
                 role="tabpanel"
                 aria-labelledby="photo-tab-<?php echo e($index); ?>">
                
                <div class="photo-gallery">
                    <div class="row g-2">
                        <?php $__currentLoopData = $project->getPhotosByCategory($category); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-6 col-md-4 col-lg-3 mb-2">
                                <div class="card h-100 photo-card ">
                                    <a href="<?php echo e($photo->photo_url); ?>" class="photo-link" data-photo-id="<?php echo e($photo->id); ?>" data-lightbox="category-<?php echo e($index); ?>">
                                        <div class="photo-preview" style="background-image: url('<?php echo e($photo->photo_url); ?>')"></div>
                                    </a>
                                    <div class="card-body p-2">
                                        <p class="card-text small text-muted mb-1">
                                            <?php echo e($photo->created_at->format('d.m.Y')); ?>

                                        </p>
                                        <?php if($photo->comment): ?>
                                            <p class="card-text small text-truncate" title="<?php echo e($photo->comment); ?>"><?php echo e($photo->comment); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>

<style>
/* Стили для фотогалереи */
.photo-gallery {
    margin-bottom: 1.5rem;
}

.photo-card {
    transition: all 0.2s ease;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.photo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.photo-preview {
    height: 180px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    transition: all 0.3s ease;
}

.photo-link:hover .photo-preview {
    opacity: 0.9;
}

#photosTab {
    border-bottom: 1px solid #dee2e6;
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 15px;
}

#photosTab::-webkit-scrollbar {
    display: none;
}

#photosTab .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #555;
    transition: all 0.2s ease;
}

#photosTab .nav-link.active {
    color: #007bff;
    background-color: transparent;
    border-bottom: 2px solid #007bff;
}

/* Улучшения для мобильных устройств */
@media (max-width: 576px) {
    .photo-preview {
        height: 140px;
    }
    
    #photosTab {
        white-space: nowrap;
    }
    
    #photosTab .nav-link {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }
    
    .photo-gallery .row {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
    }
    
    .photo-gallery .col-6 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}
</style>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/client/projects/tabs/photos.blade.php ENDPATH**/ ?>