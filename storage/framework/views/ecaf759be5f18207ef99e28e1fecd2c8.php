<!doctype html>
<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
?>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <?php if(auth()->check()): ?>
    <meta name="user-role" content="<?php echo e(auth()->user()->role); ?>" data-user-role="<?php echo e(auth()->user()->role); ?>">
    <?php endif; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title>
        <?php if(isset($pageTitle)): ?>
            <?php echo e($pageTitle); ?> - <?php echo e(config('app.name', 'Laravel')); ?>

        <?php else: ?>
            <?php echo e(config('app.name', 'Laravel')); ?> [Не установлен заголовок страницы]
        <?php endif; ?>
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon')); ?>">
    
   
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
            crossorigin="anonymous"></script>
            
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    <!-- Дополнительные стили и скрипты из дочерних представлений -->
    <?php echo $__env->yieldContent('head'); ?>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Days+One&family=Onest:wght@100..900&display=swap" rel="stylesheet">
            

    <!-- Дополнительные стили для мобильной адаптации -->
    <link href="<?php echo e(asset('css/mobile-fixes.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    
    <!-- Стили для административной панели -->
    <link href="<?php echo e(asset('css/admin-dashboard.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    
    <!-- Исправления для выпадающих меню (используем версионирование для кеширования) -->
    <link href="<?php echo e(asset('css/dropdown-fixes.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    

    <!-- Новая тема сайта -->
    <link href="<?php echo e(asset('css/theme.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    
    <!-- Анимации и эффекты -->
    <link href="<?php echo e(asset('css/animations.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    
    <!-- Дополнительные оптимизации для разных устройств -->
    <link href="<?php echo e(asset('css/optimizations.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    
    <!-- Стили для документов -->
    <link href="<?php echo e(asset('css/documents.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    
    <!-- Стили для глобального лоадера загрузки файлов -->
    <link href="<?php echo e(asset('css/global-upload-loader.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    
    <!-- Скрипт для улучшения мобильного отображения -->
    <script src="<?php echo e(asset('js/mobile-improvements.js')); ?>?v=<?php echo e(time()); ?>"></script>
    
    <!-- Скрипт для интерактивных эффектов темы -->
    <script src="<?php echo e(asset('js/theme-effects.js')); ?>?v=<?php echo e(time()); ?>"></script>

    <!-- Стили для интерактивных туров -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css">
    <link href="<?php echo e(asset('css/intro-tour.css')); ?>?v=<?php echo e(time()); ?>" rel="stylesheet">
    
    <!-- Библиотека для интерактивных туров -->
    <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/intro.min.js"></script>
    
    <!-- Данные туров и логика работы системы обучения -->
    <script src="<?php echo e(asset('js/tours-data.js')); ?>?v=<?php echo e(time()); ?>"></script>
    <script src="<?php echo e(asset('js/tours-bundle.js')); ?>?v=<?php echo e(time()); ?>"></script>
    
    <!-- Исправления для выпадающих меню в сметах -->
    <script src="<?php echo e(asset('js/estimates-dropdowns.js')); ?>?v=<?php echo e(time()); ?>"></script>

    <!-- Совместимость туров и выпадающих меню -->
    <script src="<?php echo e(asset('js/dropdown-tour-compatibility.js')); ?>?v=<?php echo e(time()); ?>"></script>
        <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
            crossorigin="anonymous"
            onload="console.log('Bootstrap JS loaded successfully')" 
            onerror="console.error('Failed to load Bootstrap JS')"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Select2 CSS и JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Scripts -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css',  'resources/sass/app.scss','resources/js/app.js']); ?>
</head>
<body data-user-role="<?php echo e(auth()->check() ? auth()->user()->role : ''); ?>">
    <?php echo $__env->make('components.help-tour-button', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="wrapper">
        <!-- Боковая навигационная панель -->
        <?php echo $__env->make('layouts.partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <!-- Основной контент -->
        <div id="content">
            <!-- Верхняя навигационная панель -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapseShow" class="btn btn-light d-inline-block d-md-none me-2" onclick="console.log('Клик по кнопке меню')">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a class="navbar-brand d-none d-md-inline" href="<?php echo e(url('/')); ?>">
                        <?php echo e(config('app.name', 'Laravel')); ?>

                    </a>
                      <div class="col-auto">
                <span class="badge" style="background: rgba(255, 255, 255, 0.3); color: rgb(255, 0, 0); font-size: 0.8rem; padding: 8px 12px; border-radius: 20px; backdrop-filter: blur(10px);">
                    v0.1 Beta
                </span>
            </div>
                    <div class="ms-2 d-none d-lg-inline">
                        <?php if(isset($pageTitle)): ?>
                            <span class="text-secondary fw-light">/ <?php echo e($pageTitle); ?></span>
                        <?php endif; ?>
                        <?php if(isset($pageSubtitle)): ?>
                            <span class="text-muted small"> - <?php echo e($pageSubtitle); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link" href="#" role="button">
                                    <i class="fas fa-bell"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="">
             
                
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>


    <!-- Исправление для выпадающих меню (добавляем версионирование для обновления кеша) -->
    <script src="<?php echo e(asset('js/dropdown-fix.js')); ?>?v=<?php echo e(time()); ?>"></script>
    
    <!-- Новый универсальный скрипт для инициализации всех выпадающих меню -->
    <script src="<?php echo e(asset('js/dropdown-init.js')); ?>?v=<?php echo e(time()); ?>"></script>
    
    <!-- Скрипт для глобального лоадера загрузки файлов -->
    <script src="<?php echo e(asset('js/global-upload-loader.js')); ?>?v=<?php echo e(time()); ?>"></script>
    
    <!-- Обязательно разместить yield для скриптов в конце body -->
    <?php echo $__env->yieldContent('scripts'); ?>
    
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/layouts/app.blade.php ENDPATH**/ ?>