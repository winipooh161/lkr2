@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h1 class="h3 mb-2 mb-md-0">Создание сметы</h1>
        <div class="mt-2 mt-md-0">
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
    @endif    <div class="row justify-content-center">
        <!-- Основная колонка с формой создания -->
        <div class="col-lg-8 col-xl-6">
            <!-- Карточка с основной информацией -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Основная информация сметы
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('partner.estimates.store') }}" method="POST" id="estimateForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Название сметы <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="project_id" class="form-label">Объект</label>
                            <select class="project-search-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id" style="width: 100%;" data-placeholder="Выберите объект">
                                <option value=""></option>
                                <option value="">Выберите объект</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->client_name }} ({{ $project->full_address }})
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Тип сметы <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                @foreach($templateTypes as $typeKey => $typeName)
                                    <option value="{{ $typeKey }}" {{ old('type', 'main') == $typeKey ? 'selected' : '' }}>{{ $typeName }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Статус</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Черновик</option>
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>На рассмотрении</option>
                                <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Утверждена</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Примечания</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                          <!-- Скрытое поле для сохранения данных Excel -->
                        <input type="hidden" name="excel_data" id="excelDataInput">
                        
                        <!-- Кнопка отправки формы -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Создать смету
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Дополнительная колонка с информацией -->
        <div class="col-lg-4">
            <!-- Блок помощи -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Что происходит после создания?
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-success me-2"></i>
                        </div>
                        <div>
                            <strong>Автоматическое создание шаблона</strong><br>
                            <small class="text-muted">Система создаст Excel-файл на основе выбранного типа сметы</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-edit text-primary me-2"></i>
                        </div>
                        <div>
                            <strong>Переход к редактированию</strong><br>
                            <small class="text-muted">Вы будете перенаправлены на страницу с интерактивным редактором</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-table text-warning me-2"></i>
                        </div>
                        <div>
                            <strong>Работа с таблицей</strong><br>
                            <small class="text-muted">Добавление строк, разделов и расчет стоимости</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Блок типов смет -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>Типы смет
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong class="text-primary">Основная смета (Работы)</strong>
                        <p class="small text-muted mb-2">Смета на выполнение работ с расчетом стоимости, материалов и сроков</p>
                    </div>
                    <div class="mb-3">
                        <strong class="text-success">Дополнительная смета</strong>
                        <p class="small text-muted mb-2">Дополнительные работы к основной смете или изменения</p>
                    </div>
                    <div>
                        <strong class="text-warning">Смета по материалам</strong>
                        <p class="small text-muted mb-0">Детализированная смета только по материалам с разбивкой по категориям</p>
                    </div>
                </div>
            </div>
        </div></div>
</div>

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
                        <!-- Опции будут загружены через JS -->
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
                        <!-- Опции будут загружены через JS -->
                    </select>
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" id="workSearchInput" placeholder="Поиск работы...">
                </div>
                <div class="list-group" id="workItemsList" style="max-height: 300px; overflow-y: auto;">
                    <!-- Список работ будет загружен через JS -->
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
                        <input type="number" class="form-control" id="workQuantity" value="1">
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

<!-- Загрузка стилей и скриптов для редактора смет с формулами -->
<link href="{{ asset('css/estimates/enhanced-formula-system.css') }}?v={{ time() }}" rel="stylesheet">

<!-- 1. HTTP клиент (базовая зависимость) -->
<script src="{{ asset('js/http-client-adapter.js') }}?v={{ time() }}"></script>

<!-- 2. Унифицированная система формул -->
<script src="{{ asset('js/estimates/unified-formula-system.js') }}?v={{ time() }}"></script>

<!-- 3. Расширенный калькулятор формул -->
<script src="{{ asset('js/estimates/enhanced-formula-calculator.js') }}?v={{ time() }}"></script>

<!-- 4. Монитор состояния формул -->
<script src="{{ asset('js/estimates/formula-status-monitor.js') }}?v={{ time() }}"></script>

<!-- 5. Полнофункциональный редактор с формулами -->
<script src="{{ asset('js/estimates/json-table-editor.js') }}?v={{ time() }}"></script>

<!-- 6. Автосохранение -->
<script src="{{ asset('js/estimates/estimate-auto-saver.js') }}?v={{ time() }}"></script>

<!-- 7. Основное ядро системы смет -->
<script src="{{ asset('js/estimates/estimates-core.js') }}?v={{ time() }}"></script>

<script>
// Инициализация данных для модальных окон
let templateSections = [];
let templateWorks = [];

// Функция для заполнения списка разделов в модальном окне
function populateSectionSelects() {
    const sectionSelect = document.getElementById('sectionSelect');
    const sectionFilterSelect = document.getElementById('sectionFilterSelect');
    
    if (!sectionSelect || !sectionFilterSelect) return;
    
    // Очищаем селекты перед заполнением
    sectionSelect.innerHTML = '<option value="">Создать новый раздел...</option>';
    sectionFilterSelect.innerHTML = '<option value="">Все разделы</option>';
    
    // Заполняем данными из нашего шаблона
    fetch('/api/excel-templates/sections-data', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.sections) {
                templateSections = data.sections;
                templateWorks = data.works || [];
                
                // Заполняем селекты опциями
                templateSections.forEach((section, index) => {
                    sectionSelect.innerHTML += `<option value="${index}">${section.title}</option>`;
                    sectionFilterSelect.innerHTML += `<option value="${index}">${section.title}</option>`;
                });
            }
        })
        .catch(error => console.error('Ошибка при загрузке разделов:', error));
}

// Обработчик изменения выбранного раздела
function initSectionSelector() {
    const sectionSelect = document.getElementById('sectionSelect');
    if (sectionSelect) {
        sectionSelect.addEventListener('change', function() {
            const customSectionName = document.getElementById('customSectionName');
            if (this.value !== "") {
                // Если выбран раздел из списка, заполняем поле названия
                const selectedSection = templateSections[parseInt(this.value)];
                customSectionName.value = selectedSection.title;
            } else {
                // Если выбрано "Создать новый раздел", очищаем поле
                customSectionName.value = '';
            }
        });
    }
}

// Обработчик фильтра разделов
function initWorkFilters() {
    const sectionFilterSelect = document.getElementById('sectionFilterSelect');
    const workSearchInput = document.getElementById('workSearchInput');
    
    if (sectionFilterSelect) {
        sectionFilterSelect.addEventListener('change', function() {
            filterWorkItems();
        });
    }
    
    if (workSearchInput) {
        workSearchInput.addEventListener('input', function() {
            filterWorkItems();
        });
    }
}

// Функция фильтрации списка работ
function filterWorkItems() {
    const sectionFilter = document.getElementById('sectionFilterSelect').value;
    const searchQuery = document.getElementById('workSearchInput').value.toLowerCase();
    const workItemsList = document.getElementById('workItemsList');
    
    if (!workItemsList) return;
    
    workItemsList.innerHTML = '';
    
    let filteredWorks = [];
    
    if (sectionFilter === "") {
        // Если фильтр раздела не выбран, показываем все работы
        filteredWorks = templateWorks;
    } else {
        // Иначе фильтруем работы по выбранному разделу
        const selectedSectionIndex = parseInt(sectionFilter);
        const selectedSection = templateSections[selectedSectionIndex];
        filteredWorks = selectedSection.items || [];
    }
    
    // Применяем поисковый фильтр
    filteredWorks = filteredWorks.filter(work => 
        work.name.toLowerCase().includes(searchQuery)
    );
    
    // Отображаем отфильтрованные работы
    filteredWorks.forEach((work, index) => {
        const listItem = document.createElement('a');
        listItem.href = '#';
        listItem.className = 'list-group-item list-group-item-action';
        listItem.textContent = work.name;
        listItem.dataset.index = index;
        listItem.dataset.name = work.name;
        listItem.dataset.unit = work.unit || 'раб';
        
        listItem.addEventListener('click', function(e) {
            e.preventDefault();
            // Заполняем поля значениями выбранной работы
            document.getElementById('customWorkName').value = this.dataset.name;
            document.getElementById('workUnitSelect').value = this.dataset.unit;
            
            // Снимаем выделение со всех элементов и выделяем текущий
            document.querySelectorAll('#workItemsList a.active').forEach(el => {
                el.classList.remove('active');
            });
            this.classList.add('active');
        });
        
        workItemsList.appendChild(listItem);
    });
}

// Функция для сохранения данных Excel в скрытое поле формы
function saveExcelToForm() {
    if (typeof hot !== 'undefined' && hot && typeof saveCurrentSheetData === 'function') {
        try {
            // Сохраняем данные текущего листа
            saveCurrentSheetData();
            
            // Получаем все данные из workbook
            if (workbook && sheets && sheets.length > 0) {
                const excelData = {
                    sheets: sheets,
                    currentSheet: currentSheetIndex
                };
                
                // Сохраняем в скрытое поле формы
                const excelDataInput = document.getElementById('excelDataInput');
                if (excelDataInput) {
                    excelDataInput.value = JSON.stringify(excelData);
                }
                
                console.log('Excel data saved to form:', excelData);
                return true;
            }
        } catch (error) {
            console.error('Error saving Excel data:', error);
        }
    }
    return false;
}

// Основная инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем компоненты
    populateSectionSelects();
    initSectionSelector();
    initWorkFilters();
      // Инициализируем Excel-редактор для нового файла
    if (typeof initExcelEditor === 'function') {
        console.log('Initializing new Excel workbook for create page');
        initExcelEditor(null);
    }
    
    // Инициализируем улучшения функциональности
    if (typeof initializeEnhancements === 'function') {
        initializeEnhancements();
    }
    
    // Инициализация системы формул
    if (typeof window.enhancedFormulaCalculator !== 'undefined') {
        console.log('✅ Инициализация расширенного калькулятора формул');
        
        // Установка обработчика для пересчета формул после изменения данных
        document.addEventListener('json-table-editor-data-changed', function(event) {
            console.log('🔄 Пересчет формул после изменения данных');
            if (window.enhancedFormulaCalculator) {
                window.enhancedFormulaCalculator.recalculateAll(event.detail?.data || null);
            }
        });
        
        // Показываем статус формул при необходимости
        if (window.formulaStatusMonitor) {
            window.formulaStatusMonitor.updateInterface();
        }
    }
    
    // Переопределяем обработчик клика по кнопке "Раздел"
    const addSectionBtn = document.getElementById('addSectionBtn');
    if (addSectionBtn) {
        // Удаляем старые обработчики
        addSectionBtn.removeEventListener('click', addSectionBtn._originalHandler);
        
        // Добавляем новый обработчик
        addSectionBtn._originalHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Показываем модальное окно выбора раздела
            const modal = new bootstrap.Modal(document.getElementById('sectionSelectorModal'));
            modal.show();
        };
        
        addSectionBtn.addEventListener('click', addSectionBtn._originalHandler);
    }
    
    // Обработчик подтверждения добавления раздела
    const confirmAddSection = document.getElementById('confirmAddSection');
    if (confirmAddSection) {
        confirmAddSection.addEventListener('click', function() {
            const sectionName = document.getElementById('customSectionName').value.trim();
            if (sectionName) {
                // Закрываем модальное окно
                bootstrap.Modal.getInstance(document.getElementById('sectionSelectorModal')).hide();
                
                // Вызываем существующую функцию addNewSection с новым именем раздела
                if (typeof addNewSection === 'function') {
                    addNewSection(sectionName);
                }
            } else {
                alert('Пожалуйста, введите название раздела');
            }
        });
    }
    
    // Переопределяем обработчик клика по кнопке "Строка"
    const addRowBtn = document.getElementById('addRowBtn');
    if (addRowBtn) {
        // Удаляем старые обработчики
        addRowBtn.removeEventListener('click', addRowBtn._originalHandler);
        
        // Добавляем новый обработчик
        addRowBtn._originalHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Показываем модальное окно выбора типа работы
            const modal = new bootstrap.Modal(document.getElementById('workTypeSelectorModal'));
            filterWorkItems(); // Инициализируем список работ
            modal.show();
        };
        
        addRowBtn.addEventListener('click', addRowBtn._originalHandler);
    }
    
    // Обработчик подтверждения добавления работы
    const confirmAddWork = document.getElementById('confirmAddWork');
    if (confirmAddWork) {
        confirmAddWork.addEventListener('click', function() {
            const workName = document.getElementById('customWorkName').value.trim();
            const workUnit = document.getElementById('workUnitSelect').value;
            const workQuantity = document.getElementById('workQuantity').value;
            
            if (workName) {
                // Закрываем модальное окно
                bootstrap.Modal.getInstance(document.getElementById('workTypeSelectorModal')).hide();
                
                // Вызываем существующую функцию addNewRow с параметрами
                if (typeof addNewRow === 'function') {
                    addNewRow(workName, workUnit, workQuantity);
                }
            } else {
                alert('Пожалуйста, введите название работы');
            }
        });
    }
      // Обработчик кнопки создания сметы
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            // Проверка валидации формы
            const form = document.getElementById('estimateForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Валидация данных Excel
            if (typeof validateExcelData === 'function' && !validateExcelData()) {
                if (typeof showNotification === 'function') {
                    showNotification('Пожалуйста, добавьте данные в таблицу перед сохранением', 'warning');
                } else {
                    alert('Пожалуйста, добавьте данные в таблицу перед сохранением');
                }
                return;
            }
            
            // Обновление названия сметы на основе выбранного типа
            const typeSelector = document.getElementById('type');
            const nameInput = document.getElementById('name');
            
            // Если имя сметы не было задано вручную, устанавливаем его на основе типа
            if (!nameInput.value.trim()) {
                switch (typeSelector.value) {
                    case 'main':
                        nameInput.value = 'Работы | Смета производства работ 2025';
                        break;
                    case 'additional':
                        nameInput.value = 'Дополнительная смета';
                        break;
                    case 'materials':
                        nameInput.value = 'Материалы | Черновые материалы 2025';
                        break;
                }
            }
            
            // Показываем индикатор загрузки
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Создание...';
            
            // Сохраняем данные Excel в форму
            if (!saveExcelToForm()) {
                console.warn('Failed to save Excel data, continuing anyway...');
            }
            
            // Отправляем форму для создания сметы
            setTimeout(() => {
                form.submit();
            }, 100); // Небольшая задержка для обеспечения сохранения данных
        });
    }
    
    // Автоматическое обновление названия при изменении типа сметы
    const typeSelector = document.getElementById('type');
    if (typeSelector) {
        typeSelector.addEventListener('change', function() {
            const nameInput = document.getElementById('name');
            // Обновляем название только если пользователь еще не ввел своё
            if (!nameInput.dataset.userModified) {
                switch (this.value) {
                    case 'main':
                        nameInput.value = 'Работы | Смета производства работ 2025';
                        break;
                    case 'additional':
                        nameInput.value = 'Дополнительная смета';
                        break;
                    case 'materials':
                        nameInput.value = 'Материалы | Черновые материалы 2025';
                        break;
                }
            }
        });
    }
    
    // Отслеживаем изменения в поле названия
    const nameInput = document.getElementById('name');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.dataset.userModified = 'true';
            } else {
                delete this.dataset.userModified;
            }
        });
    }
    
    // Добавляем поддержку горячих клавиш
    document.addEventListener('keydown', function(e) {
        // Ctrl+S для сохранения
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.click();
            }
        }
    });
});
</script>

<!-- Select2 уже подключен в app.blade.php, повторное подключение не требуется -->

<style>
/* Кастомные стили для Select2 */
.select2-container--bootstrap-5 .select2-selection {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    font-size: 1rem;
    padding: 0.375rem 0.75rem;
    height: auto;
}

.select2-container--bootstrap-5 .select2-selection--single {
    height: calc(2.25rem + 2px);
}

.select2-container--bootstrap-5 .select2-selection__rendered {
    line-height: 1.5;
    padding-left: 0;
    color: #495057;
}

.select2-container--bootstrap-5 .select2-selection__arrow {
    height: calc(2.25rem);
    right: 0.25rem;
}

.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Дополнительные стили для результатов и выпадающего списка */
.select2-container--bootstrap-5 .select2-dropdown {
    border-color: #86b7fe;
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.select2-container--bootstrap-5 .select2-dropdown .select2-search .select2-search__field {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}

.select2-container--bootstrap-5 .select2-dropdown .select2-results__option--highlighted[aria-selected] {
    background-color: #0d6efd;
    color: #fff;
}

/* Сброс стилей Bootstrap для select внутри Select2 */
.project-search-select {
    width: 100%;
}
</style>

<script>
$(document).ready(function() {
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    console.log('Select2 loaded:', typeof $.fn.select2 !== 'undefined');
    
    // Проверяем наличие Select2
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 не загружен!');
        
        // Пробуем загрузить динамически
        var cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
        document.head.appendChild(cssLink);
        
        var cssThemeLink = document.createElement('link');
        cssThemeLink.rel = 'stylesheet';
        cssThemeLink.href = 'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css';
        document.head.appendChild(cssThemeLink);
        
        var scriptTag = document.createElement('script');
        scriptTag.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        scriptTag.onload = initSelect2;
        document.body.appendChild(scriptTag);
        return;
    }
    
    initSelect2();
    
    function initSelect2() {
        // Инициализация Select2 для поиска проектов с локальным поиском
        $('.project-search-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Выберите или найдите объект...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return "Ничего не найдено";
                },
                searching: function() {
                    return "Поиск...";
                }
            },
            // Добавляем локальный поиск по загруженным опциям
            matcher: function(params, data) {
                // Если нет поискового запроса, вернуть все данные
                if ($.trim(params.term) === '') {
                    return data;
                }
                
                // Если данных нет, вернуть null
                if (typeof data.text === 'undefined') {
                    return null;
                }
                
                // Поиск по тексту опции без учета регистра
                if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    return data;
                }
                
                // Ничего не нашли
                return null;
            }
        });
    }
});
</script>

@endsection
