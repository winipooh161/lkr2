<?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-12 col-md-6 col-xl-4 mb-3 project-card-container">
        <div class="card h-100 project-card ">
            <div class="card-header d-flex justify-content-between align-items-center p-2 px-3">
                <h5 class="card-title mb-0 text-truncate" style="max-width: 70%;">
                    <a href="<?php echo e(route('partner.projects.show', $project)); ?>" class="text-decoration-none text-dark stretched-link">
                        <?php echo e($project->client_name); ?>

                    </a>
                </h5>
                <span class="badge <?php echo e($project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary'))); ?>">
                    <?php echo e(ucfirst($project->status)); ?>

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
                        <div class="d-flex align-items-center">
                            <i class="fas fa-phone text-muted me-2"></i>
                            <div class="text-truncate"><?php echo e($project->phone); ?></div>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="d-flex align-items-center justify-content-end">
                            <i class="fas fa-ruler-combined text-muted me-2"></i>
                            <?php echo e($project->area ?? '-'); ?> м²
                        </div>
                    </div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tools text-muted me-2"></i>
                            <span class="text-truncate"><?php echo e($project->work_type_text); ?></span>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="d-flex align-items-center justify-content-end">
                            <i class="fas fa-home text-muted me-2"></i>
                            <span class="text-truncate"><?php echo e($project->object_type ?? 'Не указан'); ?></span>
                        </div>
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
                
                <?php if(Auth::user()->role === 'admin'): ?>
                <div class="small text-muted mb-1">
                    <i class="fas fa-user-tie me-1"></i> Партнер: 
                    <?php echo e($project->partner ? $project->partner->name : 'Не указан'); ?>

                </div>
                <?php endif; ?>
                
                <div class="small text-end">
                    <strong>
                        <i class="fas fa-money-bill-wave text-success me-1"></i>
                        <?php echo e(number_format($project->total_amount, 0, '.', ' ')); ?> ₽
                    </strong>
                </div>
            </div>
            <div class="card-footer d-flex p-2">
                <a href="<?php echo e(route('partner.projects.edit', $project)); ?>" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-edit"></i>
                    <span class="ms-1 d-none d-md-inline">Редактировать</span>
                </a>
                
                <div class="dropdown me-2">
                    <button class="btn btn-sm btn-outline-info dropdown-toggle" type="button" id="dropdownDocumentsButton<?php echo e($project->id); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-alt"></i>
                        <span class="ms-1 d-none d-md-inline">Документы</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownDocumentsButton<?php echo e($project->id); ?>">
                        <li><h6 class="dropdown-header">Акты завершения ремонта</h6></li>
                        <li><a class="dropdown-item generate-document" href="#" data-bs-toggle="modal" data-bs-target="#documentModal" data-project-id="<?php echo e($project->id); ?>" data-document-type="completion_act_ip_ip">ИП-ИП</a></li>
                        <li><a class="dropdown-item generate-document" href="#" data-bs-toggle="modal" data-bs-target="#documentModal" data-project-id="<?php echo e($project->id); ?>" data-document-type="completion_act_fl_ip">ФЛ-ИП</a></li>
                        <li><h6 class="dropdown-header">Акты</h6></li>
                        <li><a class="dropdown-item generate-document" href="#" data-bs-toggle="modal" data-bs-target="#documentModal" data-project-id="<?php echo e($project->id); ?>" data-document-type="act_ip_ip">ИП-ИП</a></li>
                        <li><a class="dropdown-item generate-document" href="#" data-bs-toggle="modal" data-bs-target="#documentModal" data-project-id="<?php echo e($project->id); ?>" data-document-type="act_fl_ip">ФЛ-ИП</a></li>
                        <li><h6 class="dropdown-header">БСО</h6></li>
                        <li><a class="dropdown-item generate-document" href="#" data-bs-toggle="modal" data-bs-target="#documentModal" data-project-id="<?php echo e($project->id); ?>" data-document-type="bso">БСО</a></li>
                        <li><h6 class="dropdown-header">Счета</h6></li>
                        <li><a class="dropdown-item generate-document" href="#" data-bs-toggle="modal" data-bs-target="#documentModal" data-project-id="<?php echo e($project->id); ?>" data-document-type="invoice_ip">На ИП</a></li>
                        <li><a class="dropdown-item generate-document" href="#" data-bs-toggle="modal" data-bs-target="#documentModal" data-project-id="<?php echo e($project->id); ?>" data-document-type="invoice_fl">На ФЛ</a></li>
                    </ul>
                </div>
                
                <a href="<?php echo e(route('partner.projects.show', $project)); ?>" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="fas fa-eye"></i>
                    <span class="ms-1 d-none d-md-inline">Просмотр</span>
                </a>
            </div>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/partner/projects/partials/projects-cards.blade.php ENDPATH**/ ?>