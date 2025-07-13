<div class="row">
    <div class="col-12 col-md-6 mb-4 mb-md-0">
        <h5 class="mb-3">Основная информация</h5>
        <div class="table-responsive-mobile">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th width="30%">Клиент:</th>
                        <td><?php echo e($project->client_name); ?></td>
                    </tr>
                    <tr>
                        <th>Адрес:</th>
                        <td><?php echo e($project->full_address); ?></td>
                    </tr>                    <tr>
                        <th>Площадь объекта:</th>
                        <td><?php echo e($project->area ?? '-'); ?> м²</td>
                    </tr>
                    <tr>
                        <th>Телефон клиента:</th>
                        <td><?php echo e($project->phone); ?></td>
                    </tr>
                    <tr>
                        <th>Тип объекта:</th>
                        <td><?php echo e($project->object_type ?? 'Не указан'); ?></td>
                    </tr>
                    <tr>
                        <th>Тип работ:</th>
                        <td><?php echo e($project->work_type_text); ?></td>
                    </tr>
                    <tr>
                        <th>Филиал:</th>
                        <td><?php echo e($project->branch ?? 'Не указан'); ?></td>
                    </tr>
                    <tr>
                        <th>Статус проекта:</th>
                        <td>
                            <span class="badge <?php echo e($project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary'))); ?>">
                                <?php echo e($project->status == 'active' ? 'Активен' : ($project->status == 'paused' ? 'Приостановлен' : ($project->status == 'completed' ? 'Завершен' : 'Отменен'))); ?>

                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
      <div class="col-12 col-md-6">
        <h5 class="mb-3">Дополнительная информация</h5>
        <div class="mb-4">
            <h6>Телефоны для связи:</h6>
            <?php if($project->contact_phones): ?>
                <?php $__currentLoopData = explode("\n", $project->contact_phones); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $phone): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($phone); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <div class="text-muted">Дополнительные телефоны не указаны</div>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <h6>Ссылки:</h6>
            <div class="mb-2">
                <strong>IP камера:</strong> 
                <?php if($project->camera_link): ?>
                    <a href="<?php echo e($project->camera_link); ?>" target="_blank" class="d-inline-block text-truncate" style="max-width: 100%;"><?php echo e($project->camera_link); ?></a>
                <?php else: ?>
                    <span class="text-muted">Не указана</span>
                <?php endif; ?>
            </div>
            <div class="mb-2">
                <strong>Линейный график:</strong> 
                <?php if($project->schedule_link): ?>
                    <a href="<?php echo e($project->schedule_link); ?>" target="_blank" class="d-inline-block text-truncate" style="max-width: 100%;"><?php echo e($project->schedule_link); ?></a>
                <?php else: ?>
                    <span class="text-muted">Не указан</span>
                <?php endif; ?>
            </div>
            <div class="mb-2">
                <strong>Код вставлен:</strong> 
                <?php if($project->code_inserted): ?>
                    <span class="text-success">Да</span>
                <?php else: ?>
                    <span class="text-danger">Нет</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div>
            <h6>Информация о договоре:</h6>
            <div class="table-responsive-mobile">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th width="40%">Дата договора:</th>
                            <td><?php echo e($project->contract_date ? $project->contract_date->format('d.m.Y') : 'Не указана'); ?></td>
                        </tr>
                        <tr>
                            <th>Номер договора:</th>
                            <td><?php echo e($project->contract_number ?? 'Не указан'); ?></td>
                        </tr>
                        <tr>
                            <th>Дата начала работ:</th>
                            <td><?php echo e($project->work_start_date ? $project->work_start_date->format('d.m.Y') : 'Не указана'); ?></td>
                        </tr>                    </tbody>
                </table>
            </div>
        </div>
        
        <div>
            <h6>Информация о создании:</h6>
            <div class="d-flex flex-wrap">
                <div class="me-4 mb-2">
                    <strong>Создано:</strong> <?php echo e($project->created_at->format('d.m.Y H:i')); ?>

                </div>
                <div>
                    <strong>Обновлено:</strong> <?php echo e($project->updated_at->format('d.m.Y H:i')); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/client/projects/tabs/main.blade.php ENDPATH**/ ?>