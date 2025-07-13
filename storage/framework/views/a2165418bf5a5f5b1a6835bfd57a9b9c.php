<!-- Карточка с основной информацией -->
<div class="card mb-4">
    <div class="card-header">Основная информация</div>
    <div class="card-body">
        <form action="<?php echo e(isset($estimate) ? route('partner.estimates.update', $estimate) : route('partner.estimates.store')); ?>" 
              method="POST" id="estimateForm">
            <?php echo csrf_field(); ?>
            <?php if(isset($estimate)): ?>
                <?php echo method_field('PUT'); ?>
                <!-- Добавляем скрытые поля для идентификации сметы -->
                <input type="hidden" name="estimate_id" value="<?php echo e($estimate->id); ?>">
                <!-- Добавляем метатег с URL для сохранения Excel -->
                <meta name="excel-save-url" content="<?php echo e(route('partner.estimates.saveExcel', $estimate)); ?>">
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="name" class="form-label">Название сметы <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="name" name="name" 
                       value="<?php echo e($estimate->name ?? old('name')); ?>" required>
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            
            <div class="mb-3">
                <label for="project_id" class="form-label">Объект</label>
                <select class="form-select <?php $__errorArgs = ['project_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="project_id" name="project_id">
                    <option value="">Выберите объект</option>
                    <?php $__currentLoopData = $projects ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($project->id); ?>" 
                                <?php echo e((isset($estimate) && $estimate->project_id == $project->id) || old('project_id') == $project->id ? 'selected' : ''); ?>>
                            <?php echo e($project->client_name); ?> (<?php echo e($project->address); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['project_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            
            <div class="mb-3">
                <label for="type" class="form-label">Тип сметы <span class="text-danger">*</span></label>
                <select class="form-select <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="type" name="type" required>
                    <option value="main" <?php echo e((isset($estimate) && $estimate->type == 'main') || old('type', 'main') == 'main' ? 'selected' : ''); ?>>
                        Основная смета (Работы)
                    </option>
                    <option value="additional" <?php echo e((isset($estimate) && $estimate->type == 'additional') || old('type') == 'additional' ? 'selected' : ''); ?>>
                        Дополнительная смета
                    </option>
                    <option value="materials" <?php echo e((isset($estimate) && $estimate->type == 'materials') || old('type') == 'materials' ? 'selected' : ''); ?>>
                        Смета по материалам
                    </option>
                </select>
                <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            
            <div class="mb-3">
                <label for="status" class="form-label">Статус</label>
                <select class="form-select <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="status" name="status">
                    <option value="draft" <?php echo e((isset($estimate) && $estimate->status == 'draft') || old('status', 'draft') == 'draft' ? 'selected' : ''); ?>>
                        Черновик
                    </option>
                    <option value="created" <?php echo e((isset($estimate) && $estimate->status == 'created') || old('status') == 'created' ? 'selected' : ''); ?>>
                        Создана
                    </option>
                    <option value="pending" <?php echo e((isset($estimate) && $estimate->status == 'pending') || old('status') == 'pending' ? 'selected' : ''); ?>>
                        На рассмотрении
                    </option>
                    <option value="approved" <?php echo e((isset($estimate) && $estimate->status == 'approved') || old('status') == 'approved' ? 'selected' : ''); ?>>
                        Утверждена
                    </option>
                </select>
                <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Примечания</label>
                <textarea class="form-control <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="notes" name="notes" rows="3"><?php echo e($estimate->description ?? old('notes')); ?></textarea>
                <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            
            <!-- Кнопки управления -->
            <div class="d-flex justify-content-between mt-4">
                <a href="<?php echo e(route('partner.estimates.index')); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>К списку смет
                </a>
                
                <div>
                    <button type="button" id="saveExcelBtn" class="btn btn-primary me-2">
                        <i class="fas fa-save me-1"></i>Сохранить
                    </button>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Обновить информацию
                    </button>
                </div>
            </div>
            
            <!-- Скрытое поле для сохранения данных Excel -->
            <input type="hidden" name="excel_data" id="excelDataInput">
        </form>
    </div>
</div>


</div>
<!-- Скрипты для работы с формой и файлом -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация выпадающего меню при загрузке страницы
    const excelDropdownBtn = document.getElementById('excelDropdownBtn');
    if (excelDropdownBtn && typeof bootstrap !== 'undefined') {
        new bootstrap.Dropdown(excelDropdownBtn);
    }
    
    // Обработка формы загрузки файла
    const uploadForm = document.getElementById('uploadExcelForm');
    if (uploadForm) {
        const fileInput = document.getElementById('excelFile');
        
        // Автоматическая отправка формы при выборе файла
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                uploadForm.submit();
            }
        });
    }
});
</script>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/partner/estimates/partials/estimate-info-form.blade.php ENDPATH**/ ?>