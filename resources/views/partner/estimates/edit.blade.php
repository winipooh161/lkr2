@extends('layouts.app')

@section('head')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="{{ asset('css/estimates/estimate-editor.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('css/estimates/estimates-export.css') }}?v={{ time() }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="estimate-id" content="{{ $estimate->id }}">
    <meta name="estimate-type" content="{{ $estimate->type ?? 'main' }}">
    
    <!-- Улучшения интерфейса экспорта -->
    <script src="{{ asset('js/estimates/export-ui-enhancements.js') }}?v={{ time() }}"></script>
    
    <script>
        // Глобальные переменные для работы редактора
        window.estimateId = {{ $estimate->id }};
        window.estimateType = "{{ $estimate->type ?? 'main' }}";
    </script>
    
    <!-- Добавляем запасной просмотрщик для аварийных ситуаций -->
    <script src="{{ asset('js/estimates/fallback-viewer.js') }}?v={{ time() }}"></script>
@endsection

@section('content')
<!-- Добавляем стили для кнопок экспорта -->
<style>
/* Стили для выпадающего меню экспорта */
.dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.dropdown-menu .dropdown-header {
    background-color: #f8f9fa;
    border-radius: 0.5rem 0.5rem 0 0;
    margin: -0.5rem -1rem 0;
    padding: 0.75rem 1rem;
    font-weight: 600;
}

.dropdown-menu .dropdown-divider {
    margin: 0.5rem 0;
    opacity: 0.3;
}

/* Стили для групп экспорта */
.export-section {
    padding: 0.75rem 1rem;
    background-color: #fff;
}

.export-section:hover {
    background-color: #f8f9fa;
}

.export-section .section-title {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.export-section .section-description {
    font-size: 0.75rem;
    color: #6c757d;
    margin-left: auto;
}

.export-section .btn-group {
    gap: 0.5rem;
}

.export-section .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.15s ease-in-out;
}

.export-section .btn-outline-success:hover {
    background-color: #198754;
    border-color: #198754;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 0.125rem 0.25rem rgba(25, 135, 84, 0.25);
}

.export-section .btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 0.125rem 0.25rem rgba(220, 53, 69, 0.25);
}

/* Иконки */
.export-section i {
    font-size: 0.875rem;
}

/* Адаптивность для мобильных устройств */
@media (max-width: 768px) {
    .dropdown-menu {
        min-width: 250px !important;
        font-size: 0.875rem;
    }
    
    .export-section .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .export-section .section-description {
        font-size: 0.7rem;
    }
}

/* Улучшенный внешний вид кнопки экспорта */
.btn-group .btn.dropdown-toggle {
    font-weight: 500;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 123, 255, 0.25);
    transition: all 0.15s ease-in-out;
}

.btn-group .btn.dropdown-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 123, 255, 0.3);
}
</style>

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h1 class="h3 mb-2 mb-md-0">{{ isset($estimate) ? 'Редактирование сметы' : 'Создание сметы' }}</h1>
        <div class="mt-2 mt-md-0 d-flex gap-2 flex-wrap">
            @if(isset($estimate))
                <!-- Кнопки экспорта -->
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i>Экспорт
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 280px;">
                        <!-- Заголовок -->
                        <li>
                            <h6 class="dropdown-header text-center mb-2">
                                <i class="fas fa-download me-2"></i>Экспорт сметы
                            </h6>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- Полная версия -->
                        <li>
                            <div class="px-3 py-2 export-section">
                                <div class="d-flex align-items-center mb-2 section-title">
                                    <i class="fas fa-table me-2 text-primary"></i>
                                    <strong class="text-primary">Полная версия</strong>
                                    <small class="text-muted ms-auto">(все данные)</small>
                                </div>
                                <div class="d-flex gap-2 btn-group">
                                    <a class="btn btn-outline-success btn-sm flex-fill" href="{{ route('partner.estimates.export', $estimate->id) }}">
                                        <i class="fas fa-file-excel me-1"></i>Excel
                                    </a>
                                    <a class="btn btn-outline-danger btn-sm flex-fill" href="{{ route('partner.estimates.exportPdf', $estimate->id) }}">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- Для заказчика -->
                        <li>
                            <div class="px-3 py-2 export-section">
                                <div class="d-flex align-items-center mb-2 section-title">
                                    <i class="fas fa-user me-2 text-info"></i>
                                    <strong class="text-info">Для заказчика</strong>
                                    <small class="text-muted ms-auto">(клиентские цены)</small>
                                </div>
                                <div class="d-flex gap-2 btn-group">
                                    <a class="btn btn-outline-success btn-sm flex-fill" href="{{ route('partner.estimates.exportClient', $estimate->id) }}">
                                        <i class="fas fa-file-excel me-1"></i>Excel
                                    </a>
                                    <a class="btn btn-outline-danger btn-sm flex-fill" href="{{ route('partner.estimates.exportPdfClient', $estimate->id) }}">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" id="create-client-pdf">
                                        <i class="fas fa-file-pdf me-1"></i>Создать PDF для клиента
                                    </button>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <!-- Для мастера -->
                        <li>
                            <div class="px-3 py-2 export-section">
                                <div class="d-flex align-items-center mb-2 section-title">
                                    <i class="fas fa-hard-hat me-2 text-warning"></i>
                                    <strong class="text-warning">Для мастера</strong>
                                    <small class="text-muted ms-auto">(базовые цены)</small>
                                </div>
                                <div class="d-flex gap-2 btn-group">
                                    <a class="btn btn-outline-success btn-sm flex-fill" href="{{ route('partner.estimates.exportContractor', $estimate->id) }}">
                                        <i class="fas fa-file-excel me-1"></i>Excel
                                    </a>
                                    <a class="btn btn-outline-danger btn-sm flex-fill" href="{{ route('partner.estimates.exportPdfContractor', $estimate->id) }}">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            @endif
            
            <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад к списку
            </a>
        </div>
    </div>
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ $estimate->name ?? 'Новая смета' }}</h5>
                        @if(isset($estimate) && $estimate->project)
                            <span class="text-muted">{{ $estimate->project->full_address }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <!-- Контейнер для редактора смет -->
                    <div id="estimate-editor-container" class="estimate-editor-container"
                         data-estimate-id="{{ $estimate->id ?? '' }}"
                         data-template-type="{{ $estimate->type ?? 'main' }}"
                         data-mode="edit">
                        <!-- Редактор будет инициализирован здесь через JavaScript -->
                        <div class="text-center p-5 loading-indicator">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                            <p class="mt-2 text-muted">Инициализация редактора смет...</p>
                        </div>
                        
                        <!-- Контейнер для редактора -->
                        <div class="estimate-editor" style="display: none;">
                            <!-- Панель инструментов -->
                            <div class="editor-toolbar d-flex justify-content-between align-items-center p-2 bg-light border-bottom">
                                <div class="toolbar-left d-flex align-items-center">
                                </div>
                                <div class="toolbar-right d-flex align-items-center">
                                    <button type="button" class="btn btn-success btn-sm me-2" id="manual-save-btn">
                                        <i class="fas fa-save"></i> Сохранить
                                    </button>
                                    <span id="save-status" class="text-muted small"></span>
                                </div>
                            </div>
                            
                            <!-- Таблица сметы -->
                            <div class="table-wrapper">
                                <table class="estimate-table table table-bordered table-hover mb-0">
                                    <thead>
                                        <tr></tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Итоги -->
                            <div class="editor-totals p-3 bg-light">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Показать сообщение об ошибке в контейнере
     * @param {string} message - Текст сообщения об ошибке
     * @param {string} containerId - ID контейнера для отображения ошибки
     */
    function showErrorMessage(message, containerId = 'estimate-editor-container') {
        console.error('❌ Ошибка редактора смет:', message);
        
        const container = document.getElementById(containerId);
        if (container) {
            const loadingIndicator = container.querySelector('.loading-indicator');
            if (loadingIndicator) {
                loadingIndicator.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">Ошибка при работе с редактором</h4>
                        <p>${message}</p>
                        <hr>
                        <p class="mb-0">Активирован запасной режим просмотра данных. Вы можете перезагрузить страницу, чтобы попробовать снова.</p>
                        <div class="d-flex mt-3">
                            <button class="btn btn-primary me-2" onclick="location.reload()">Перезагрузить страницу</button>
                            <button class="btn btn-success me-2" id="fallback-mode-btn">Открыть в запасном режиме</button>
                            <button class="btn btn-outline-secondary" onclick="window.history.back()">Вернуться назад</button>
                        </div>
                    </div>
                `;
                
                // Добавляем обработчик для запасного режима
                setTimeout(() => {
                    const fallbackBtn = document.getElementById('fallback-mode-btn');
                    if (fallbackBtn) {
                        fallbackBtn.addEventListener('click', function() {
                            if (typeof window.showFallbackEstimateView === 'function') {
                                window.showFallbackEstimateView();
                            } else {
                                alert('Запасной режим просмотра недоступен. Перезагрузите страницу и попробуйте снова.');
                            }
                        });
                    }
                }, 100);
            }
        }
        
        // Автоматически активируем запасной режим просмотра через 5 секунд
        setTimeout(() => {
            if (typeof window.showFallbackEstimateView === 'function') {
                console.log('🔄 Автоматическая активация запасного режима просмотра...');
                window.showFallbackEstimateView();
            }
        }, 5000);
    }
    
    // Инициализация редактора после загрузки всех скриптов
    document.addEventListener('DOMContentLoaded', function() {
        // Небольшая задержка для полной загрузки Bootstrap и всех скриптов
        setTimeout(() => {
            console.log('Инициализация редактора смет...');
            let initializationSuccessful = false;
            
            try {
                // Находим контейнер редактора
                const container = document.querySelector('#estimate-editor-container');
                
                if (!container) {
                    throw new Error('Контейнер редактора не найден');
                }
                
                if (typeof EstimateEditor !== 'function') {
                    throw new Error('Класс EstimateEditor не загружен');
                }
                
                // Получаем данные из атрибутов контейнера
                const estimateId = container.dataset.estimateId;
                const templateType = container.dataset.templateType || 'main';
                
                // Проверяем наличие ID сметы
                if (!estimateId) {
                    throw new Error('ID сметы не указан');
                }
                
                // Инициализируем редактор с обработчиком ошибок
                try {
                    // Создаем экземпляр редактора
                    window.estimateEditor = new EstimateEditor('estimate-editor-container', {
                        estimateId: estimateId,
                        templateType: templateType,
                        apiEndpoint: '/partner/estimates',
                        dataUrl: `/partner/estimates/${estimateId}/json-data`,
                        saveUrl: `/partner/estimates/${estimateId}/save-json-data`
                    });
                    
                    // Добавляем обработчик ошибок для редактора
                    if (window.estimateEditor) {
                        window.estimateEditor.onError = function(error) {
                            showErrorMessage(error.message || 'Неизвестная ошибка при работе редактора');
                        };
                        
                        // Добавляем обработчик для запасного режима
                        window.estimateEditor.fallbackMode = function() {
                            if (typeof window.showFallbackEstimateView === 'function') {
                                window.showFallbackEstimateView();
                            }
                        };
                    }
                    
                    initializationSuccessful = true;
                    console.log('✅ Редактор смет успешно инициализирован');
                } catch (initError) {
                    showErrorMessage(`Ошибка при создании редактора: ${initError.message}`);
                    initializationSuccessful = false;
                }
            } catch (error) {
                showErrorMessage(error.message);
                initializationSuccessful = false;
            }
            
            // Если инициализация не удалась через 10 секунд, активируем запасной режим
            if (!initializationSuccessful) {
                setTimeout(() => {
                    if (typeof window.showFallbackEstimateView === 'function' && 
                        (!window.estimateEditor || !window.estimateEditor.initialized)) {
                        console.log('⚠️ Автоматический переход в запасной режим из-за ошибки инициализации');
                        window.showFallbackEstimateView();
                    }
                }, 10000);
            }
        }, 1000); // Увеличиваем задержку для гарантированной загрузки всех скриптов
    });
    
    // Обработчик кнопки ручного сохранения
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'manual-save-btn') {
            const saveBtn = e.target;
            const statusSpan = document.getElementById('save-status');
            
            if (window.estimateEditor && typeof window.estimateEditor.saveData === 'function') {
                // Показываем индикатор загрузки
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
                statusSpan.textContent = 'Сохранение...';
                statusSpan.className = 'text-primary small';
                
                window.estimateEditor.saveData()
                    .then(() => {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                        statusSpan.textContent = 'Сохранено ' + new Date().toLocaleTimeString();
                        statusSpan.className = 'text-success small';
                        
                        // Убираем статус через 3 секунды
                        setTimeout(() => {
                            statusSpan.textContent = '';
                        }, 3000);
                    })
                    .catch((error) => {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
                        statusSpan.textContent = 'Ошибка сохранения: ' + error.message;
                        statusSpan.className = 'text-danger small';
                    });
            } else {
                statusSpan.textContent = 'Редактор не инициализирован';
                statusSpan.className = 'text-warning small';
            }
        }
    });
    
    // Обработчик для кнопки создания PDF для клиента
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'create-client-pdf') {
            e.preventDefault();
            
            const estimateId = {{ $estimate->id ?? 0 }};
            
            if (!estimateId) {
                alert('Ошибка: ID сметы не найден');
                return;
            }
            
            // Показываем индикатор загрузки
            const btn = e.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Создание PDF...';
            
            // Отправляем запрос на создание PDF
            fetch(`/partner/estimates/${estimateId}/create-client-pdf`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Показываем уведомление об успехе
                    if (typeof window.estimateExportManager !== 'undefined' && window.estimateExportManager.showNotification) {
                        window.estimateExportManager.showNotification(
                            'PDF смета для клиента успешно создана и добавлена в рабочую документацию проекта',
                            'success',
                            7000
                        );
                    } else {
                        alert('PDF смета для клиента успешно создана!');
                    }
                } else {
                    throw new Error(data.message || 'Неизвестная ошибка');
                }
            })
            .catch(error => {
                console.error('Ошибка создания PDF:', error);
                
                if (typeof window.estimateExportManager !== 'undefined' && window.estimateExportManager.showNotification) {
                    window.estimateExportManager.showNotification(
                        'Ошибка при создании PDF: ' + error.message,
                        'error',
                        7000
                    );
                } else {
                    alert('Ошибка при создании PDF: ' + error.message);
                }
            })
            .finally(() => {
                // Восстанавливаем кнопку
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
    });
</script>
@endsection

<!-- Модальное окно выбора раздела -->
<div class="modal fade" id="sectionSelectorModal" tabindex="-1" aria-labelledby="sectionSelectorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionSelectorModalLabel">Выбор раздела</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="sectionSelect" class="form-label">Выберите раздел из шаблона</label>
                    <select class="form-select" id="sectionSelect">
                        <option value="">Создать новый раздел...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="customSectionName" class="form-label">Название раздела</label>
                    <input type="text" class="form-control" id="customSectionName" placeholder="Введите название раздела">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="confirmAddSection">Добавить раздел</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно выбора типа работы -->
<div class="modal fade" id="workTypeSelectorModal" tabindex="-1" aria-labelledby="workTypeSelectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="workTypeSelectorModalLabel">Выбор типа работы</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="sectionFilterSelect" class="form-label">Фильтровать по разделу</label>
                    <select class="form-select" id="sectionFilterSelect">
                        <option value="">Все разделы</option>
                    </select>
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" id="workSearchInput" placeholder="Поиск работы...">
                </div>
                <div class="list-group" id="workItemsList" style="max-height: 300px; overflow-y: auto;">
                </div>
                <div class="mt-3">
                    <label for="customWorkName" class="form-label">Или введите свой вариант</label>
                    <input type="text" class="form-control" id="customWorkName" placeholder="Введите название работы">
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="workUnitSelect" class="form-label">Единица измерения</label>
                        <select class="form-select" id="workUnitSelect">
                            <option value="раб">раб</option>
                            <option value="шт">шт</option>
                            <option value="м2">м²</option>
                            <option value="м.п.">м.п.</option>
                            <option value="компл">компл</option>
                            <option value="точка">точка</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="workQuantity" class="form-label">Количество</label>
                        <input type="number" class="form-control" id="workQuantity" value="1" min="0.1" step="0.1">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="confirmAddWork">Добавить работу</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно настройки столбцов -->
<div class="modal fade" id="columnSettingsModal" tabindex="-1" aria-labelledby="columnSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="columnSettingsModalLabel">Настройка столбцов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group" id="columnsList">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-primary" id="saveColumnSettings">Применить</button>
            </div>
        </div>
    </div>
</div>

<!-- Загрузка стилей и скриптов для редактора смет с формулами -->

<!-- Стили для системы формул -->
<link href="{{ asset('css/estimates/estimate-editor.css') }}?v={{ time() }}" rel="stylesheet">
<link href="{{ asset('css/estimates/materials-amount-updater.css') }}?v={{ time() }}" rel="stylesheet">

<!-- HTTP клиент (базовая зависимость) -->
<script src="{{ asset('js/http-client-adapter.js') }}?v={{ time() }}"></script>

<!-- Основные скрипты редактора смет -->
<script src="{{ asset('js/estimates/estimate-editor-core.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/estimates/estimate-editor-ui.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/estimates/estimate-editor-formulas.js') }}?v={{ time() }}"></script>

<script src="{{ asset('js/estimates/editor-action-patches.js') }}?v={{ time() }}"></script>

<!-- Дополнительные расширения -->
<script src="{{ asset('js/estimates/estimate-editor-extended.js') }}?v={{ time() }}"></script>

<!-- Автоматическое обновление materials_amount -->
<script src="{{ asset('js/estimates/materials-amount-updater.js') }}?v={{ time() }}"></script>

<!-- Диагностический скрипт -->
<script src="{{ asset('js/estimates/estimate-diagnostic.js') }}?v={{ time() }}"></script>

<script>
    // Устанавливаем ID сметы для использования в JavaScript
    window.estimateId = {{ $estimate->id }};
    
    // Инициализация с полной системой формул
    document.addEventListener('DOMContentLoaded', function() {
        console.log('� Расширенный редактор смет загружен (с системой формул)');
        
        // Патч для проблем с контекстным меню
        if (window.EstimateEditorUI && EstimateEditorUI.prototype) {
            const originalCreateContextMenu = EstimateEditorUI.prototype.createContextMenu;
            
            EstimateEditorUI.prototype.createContextMenu = function() {
                try {
                    return originalCreateContextMenu.call(this);
                } catch (error) {
                    console.warn('Ошибка при создании контекстного меню:', error.message);
                    
                    // Создаем контекстное меню вручную
                    this.contextMenu = document.createElement('div');
                    this.contextMenu.className = 'context-menu dropdown-menu';
                    this.contextMenu.style.display = 'none';
                    document.body.appendChild(this.contextMenu);
                    
                    return this.contextMenu;
                }
            };
            
            console.log('✅ Патч для создания контекстного меню применен');
        }
        
        // Расширенные отладочные функции
        window.debugEstimate = {
            getTableData: () => {
                const table = document.querySelector('#json-table-container-table');
                return table ? window.jsonTableEditor?.extractDataFromTable(table) : null;
            },
            recalculateFormulas: () => {
                console.log('🔄 Запуск пересчета всех формул...');
                if (window.enhancedFormulaCalculator) {
                    return window.enhancedFormulaCalculator.recalculateAll();
                } else if (window.unifiedFormulaSystem) {
                    return window.unifiedFormulaSystem.recalculateAllWithTotals();
                }
                return null;
            },
            checkSystemStatus: () => {
                console.log('🔍 Проверка состояния системы:');
                console.log('- JsonTableEditor:', typeof window.JsonTableEditor);
                console.log('- UnifiedFormulaSystem:', typeof window.UnifiedFormulaSystem);
                console.log('- EnhancedFormulaCalculator:', typeof window.EnhancedFormulaCalculator);
                console.log('- FormulaStatusMonitor:', typeof window.FormulaStatusMonitor);
                console.log('- EstimateAutoSaver:', typeof window.EstimateAutoSaver);
            },
            diagnoseRows: () => {
                // Проверка целостности данных в строках
                const data = window.jsonTableEditor?.getData();
                if (!data || !data.sheets) {
                    console.error('❌ Данные не найдены');
                    return;
                }
                
                let totalRows = 0;
                let problematicRows = 0;
                let formulaCount = 0;
                
                data.sheets.forEach((sheet, sheetIdx) => {
                    if (sheet.data && Array.isArray(sheet.data)) {
                        totalRows += sheet.data.length;
                        
                        sheet.data.forEach((row, rowIdx) => {
                            if (!row || !Array.isArray(row)) {
                                console.error(`❌ Некорректная строка [${sheetIdx}][${rowIdx}]:`, row);
                                problematicRows++;
                                return;
                            }
                            
                            // Проверка формул
                            row.forEach((cell, cellIdx) => {
                                if (cell && cell.formula) {
                                    formulaCount++;
                                }
                            });
                        });
                    }
                });
                
                console.log(`📊 Диагностика завершена: ${totalRows} строк, ${problematicRows} проблемных, ${formulaCount} формул`);
            },
            showStatus: () => {
                if (window.formulaStatusMonitor) {
                    window.formulaStatusMonitor.show();
                }
            },
            // Отладочные функции
            editor: () => window.estimateEditor,
            data: () => window.estimateEditor?.data,
            save: () => window.estimateEditor?.saveData(),
            reload: () => window.estimateEditor?.loadEstimateData(),
            logs: () => console.log('Editor:', window.estimateEditor),
            testSave: (testData) => {
                const estimateId = document.getElementById('estimate-editor-container')?.dataset?.estimateId;
                if (!estimateId) {
                    console.error('Estimate ID not found');
                    return;
                }
                
                const data = testData || {
                    type: "main",
                    version: "1.0",
                    meta: {
                        template_name: "Тестовая смета",
                        estimate_id: parseInt(estimateId),
                        updated_at: new Date().toISOString(),
                        updated_by: "Тест"
                    },
                    sheets: [{
                        data: [{
                            number: 1,
                            name: "Тестовая работа",
                            unit: "шт",
                            quantity: 1,
                            price: 100,
                            cost: 100,
                            markup: 20,
                            discount: 0,
                            client_price: 120,
                            client_cost: 120
                        }]
                    }],
                    currentSheet: 0,
                    sections: [],
                    totals: {
                        work_total: 100,
                        materials_total: 0,
                        grand_total: 100,
                        client_work_total: 120,
                        client_materials_total: 0,
                        client_grand_total: 120
                    }
                };
                
                return fetch(`/partner/estimates/${estimateId}/save-json-data`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    console.log('Test save result:', result);
                    return result;
                })
                .catch(error => {
                    console.error('Test save error:', error);
                    throw error;
                });
            }
        };
        
        console.log('✅ Отладочные функции доступны через window.debugEstimate');
    });
</script>

<!-- Скрипт для работы с экспортом смет на странице редактирования -->
<script src="{{ asset('js/estimates/estimates-export.js') }}?v={{ time() }}"></script>

<script>
// Инициализация экспорта смет на странице редактирования
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Инициализация системы экспорта для страницы редактирования...');
    
    // Ждем загрузки основного класса EstimateExportManager
    function waitForExportManager() {
        if (typeof EstimateExportManager !== 'undefined') {
            // Инициализируем менеджер экспорта
            window.estimateExportManager = new EstimateExportManager();
            console.log('✅ Менеджер экспорта инициализирован для страницы редактирования');
        } else {
            setTimeout(waitForExportManager, 100);
        }
    }
    
    waitForExportManager();
});
</script>


