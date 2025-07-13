<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактор сметы {{ $estimateId ?? 'Новая смета' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome иконки -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Toastify для уведомлений -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <!-- Стили приложения -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/estimates/estimate-editor.css">
    
    <!-- Запасной просмотрщик данных для аварийных ситуаций -->
    <script src="{{ asset('js/estimates/fallback-viewer.js') }}?v={{ time() }}"></script>
    
    <script>
        // Глобальные переменные для доступа в JS
        window.estimateId = {{ $estimateId ?? 'null' }};
        window.estimateType = "{{ $estimateType ?? 'main' }}";
    </script>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>{{ isset($estimate) ? "Редактирование сметы #{$estimate->id}" : 'Новая смета' }}</h1>
                    <div>
                        <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> К списку смет
                        </a>
                        <button type="button" class="btn btn-primary save-btn">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <button type="button" class="btn btn-success ms-2 print-btn">
                            <i class="fas fa-print"></i> Печать
                        </button>
                    </div>
                </div>
                <hr>
            </div>
        </div>
        
        <!-- Основной контейнер редактора -->
        <div class="row">
            <div class="col-12">
                <div id="estimate-editor-container" 
                     data-estimate-id="{{ $estimateId ?? '' }}" 
                     data-template-type="{{ $estimateType ?? 'main' }}" 
                     data-mode="{{ isset($estimate) && $estimate->status == 'approved' ? 'view' : 'edit' }}">
                    <!-- Здесь будет инициализирован редактор -->
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <p class="mt-3">Загрузка редактора смет...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Индикатор статуса -->
        <div class="position-fixed bottom-0 end-0 p-3" id="statusIndicator" style="display: none;">
            <div class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        <span id="statusMessage">Загрузка...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle с Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Toastify JS для уведомлений -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <!-- Интеграция редактора смет -->
    <script src="{{ asset('js/estimates/estimate-editor-integration.js') }}?v={{ time() }}"></script>
</body>
</html>
