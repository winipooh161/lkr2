/**
 * Основной редактор смет - система управления JSON данными
 * Версия: 3.0
 * Дата: 2025-07-12
 */

class EstimateEditor {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.estimateId = options.estimateId || window.estimateId;
        this.estimateType = options.estimateType || window.estimateType || 'main';
        this.apiUrl = options.apiUrl || '/partner/estimates';
        
        // Состояние данных
        this.data = null;
        this.templates = {
            main: null,
            materials: null,
            additional: null
        };
        
        // Флаги состояния
        this.isLoading = false;
        this.hasChanges = false;
        this.autoSaveEnabled = true;
        this.autoSaveInterval = 30000; // 30 секунд
        this.autoSaveTimer = null;
        
        // Настройки
        this.settings = {
            enableFormulas: true,
            enableAutoSave: true,
            showLineNumbers: true,
            currencySymbol: '₽',
            decimalPlaces: 2,
            ...options.settings
        };
        
        this.init();
    }
    
    /**
     * Инициализация редактора
     */
    async init() {
        try {
            this.showLoading('Инициализация редактора...');
            
            // Загружаем шаблоны
            await this.loadTemplates();
            
            // Загружаем данные сметы
            await this.loadEstimateData();
            
            // Создаем интерфейс
            this.createInterface();
            
            // Настраиваем автосохранение
            this.setupAutoSave();
            
            // Настраиваем обработчики событий
            this.setupEventHandlers();
            
            this.hideLoading();
            this.log('Редактор смет инициализирован');
            
        } catch (error) {
            this.handleError('Ошибка инициализации редактора', error);
            // При ошибке все равно пытаемся создать интерфейс с пустыми данными
            if (!this.data) {
                this.data = this.createDefaultData();
            }
            this.createInterface();
            this.setupEventHandlers();
        }
    }
    
    /**
     * Загрузка шаблонов
     */
    async loadTemplates() {
        const templateTypes = ['main', 'materials', 'additional'];
        
        for (const type of templateTypes) {
            try {
                const response = await fetch(`${this.apiUrl}/templates/${type}`);
                if (response.ok) {
                    this.templates[type] = await response.json();
                }
            } catch (error) {
                this.log(`Ошибка загрузки шаблона ${type}:`, error);
            }
        }
    }
    
    /**
     * Загрузка данных сметы
     */
    async loadEstimateData() {
        if (!this.estimateId) {
            this.data = this.createDefaultData();
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}/${this.estimateId}/json-data`);
            if (response.ok) {
                const result = await response.json();
                
                // Контроллер возвращает данные напрямую, без обертки success
                if (result && typeof result === 'object') {
                    this.data = result;
                    // Убеждаемся, что есть базовая структура
                    if (!this.data.sections) this.data.sections = [];
                    if (!this.data.totals) {
                        this.data.totals = {
                            work_total: 0,
                            materials_total: 0,
                            grand_total: 0,
                            markup_percent: 20,
                            discount_percent: 0
                        };
                    }
                } else {
                    throw new Error('Некорректный формат данных сметы');
                }
            } else {
                throw new Error(`Ошибка HTTP: ${response.status}`);
            }
        } catch (error) {
            this.log('Ошибка загрузки данных:', error);
            this.data = this.createDefaultData();
            // НЕ выбрасываем ошибку дальше, а продолжаем с пустыми данными
        }
    }
    
    /**
     * Создание интерфейса редактора
     */
    createInterface() {
        if (!this.container) {
            throw new Error('Контейнер редактора не найден');
        }
        
        this.container.innerHTML = `
            <div class="estimate-editor">
                <!-- Панель инструментов -->
                <div class="editor-toolbar">
                    <div class="toolbar-left">
                        <button id="saveBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <button id="addSectionBtn" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Раздел
                        </button>
                        <button id="addRowBtn" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus"></i> Строка
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                    data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i> Экспорт
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" id="exportExcelBtn">Excel</a></li>
                                <li><a class="dropdown-item" href="#" id="exportPdfBtn">PDF</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="toolbar-right">
                        <span id="statusIndicator" class="badge bg-secondary">Сохранено</span>
                        <button id="settingsBtn" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Основная область редактирования -->
                <div class="editor-content">
                    <div class="table-wrapper">
                        <table id="estimateTable" class="table table-sm table-bordered estimate-table">
                            <thead id="tableHead"></thead>
                            <tbody id="tableBody"></tbody>
                            <tfoot id="tableFoot"></tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Итоги -->
                <div class="editor-totals">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="totals-section">
                                <h6>Итоги по работам:</h6>
                                <div class="d-flex justify-content-between">
                                    <span>Сумма работ:</span>
                                    <span id="workTotal" class="fw-bold">0 ₽</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Сумма для клиента:</span>
                                    <span id="clientTotal" class="fw-bold text-primary">0 ₽</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="totals-section">
                                <h6>Общие итоги:</h6>
                                <div class="d-flex justify-content-between">
                                    <span>Общая сумма:</span>
                                    <span id="grandTotal" class="fw-bold text-success fs-5">0 ₽</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.buildTable();
        this.updateTotals();
    }
    
    /**
     * Построение таблицы
     */
    buildTable() {
        const template = this.templates[this.estimateType];
        if (!template) {
            this.log('Шаблон не найден для типа:', this.estimateType);
            return;
        }
        
        this.buildTableHeader(template.structure.columns);
        this.buildTableBody();
        this.buildTableFooter();
    }
    
    /**
     * Построение заголовка таблицы
     */
    buildTableHeader(columns) {
        const thead = document.getElementById('tableHead');
        const headerRow = document.createElement('tr');
        
        // Добавляем столбец для действий
        const actionsHeader = document.createElement('th');
        actionsHeader.innerHTML = '<i class="fas fa-cog"></i>';
        actionsHeader.style.width = '40px';
        headerRow.appendChild(actionsHeader);
        
        columns.forEach((column, index) => {
            const th = document.createElement('th');
            th.textContent = column.title;
            th.style.width = column.width + 'px';
            th.dataset.columnIndex = index;
            th.dataset.columnType = column.type;
            headerRow.appendChild(th);
        });
        
        thead.appendChild(headerRow);
    }
    
    /**
     * Построение тела таблицы
     */
    buildTableBody() {
        const tbody = document.getElementById('tableBody');
        if (!tbody || !this.data?.sections) return;
        
        tbody.innerHTML = '';
        let rowIndex = 0;
        
        this.data.sections.forEach((section, sectionIndex) => {
            // Создаем строку раздела
            const sectionRow = this.createSectionRow(section, sectionIndex);
            tbody.appendChild(sectionRow);
            
            // Создаем строки элементов
            if (section.items && Array.isArray(section.items)) {
                section.items.forEach((item, itemIndex) => {
                    const itemRow = this.createItemRow(item, sectionIndex, itemIndex, rowIndex);
                    tbody.appendChild(itemRow);
                    rowIndex++;
                });
            }
        });
    }
    
    /**
     * Построение подвала таблицы с итогами
     */
    buildTableFooter() {
        const tableWrapper = this.container.querySelector('.table-wrapper');
        if (!tableWrapper) {
            this.log('Контейнер таблицы не найден для footer');
            return;
        }
        
        // Удаляем существующий footer если есть
        const existingFooter = tableWrapper.querySelector('.table-footer');
        if (existingFooter) {
            existingFooter.remove();
        }
        
        // Создаем новый footer
        const footer = document.createElement('div');
        footer.className = 'table-footer mt-3 p-3 bg-light border rounded';
        footer.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Итоги по смете:</h6>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Работы:</small>
                            <div class="fw-bold" id="workTotalFooter">0 ₽</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Материалы:</small>
                            <div class="fw-bold" id="materialsTotalFooter">0 ₽</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-end">
                        <h6>Общая сумма:</h6>
                        <div class="h4 text-primary fw-bold" id="grandTotalFooter">0 ₽</div>
                        <small class="text-muted">Включая наценку и скидки</small>
                    </div>
                </div>
            </div>
        `;
        
        tableWrapper.appendChild(footer);
    }
    
    /**
     * Создание строки раздела
     */
    createSectionRow(section, sectionIndex) {
        const row = document.createElement('tr');
        row.className = 'section-row table-info';
        row.dataset.sectionIndex = sectionIndex;
        
        const template = this.templates[this.estimateType];
        const columnCount = template.structure.columns.length + 1; // +1 для столбца действий
        
        // Ячейка действий
        const actionsCell = document.createElement('td');
        actionsCell.innerHTML = `
            <div class="btn-group-vertical btn-group-sm">
                <button class="btn btn-outline-secondary btn-xs section-menu-btn" 
                        data-section-index="${sectionIndex}">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
            </div>
        `;
        row.appendChild(actionsCell);
        
        // Ячейка с названием раздела
        const titleCell = document.createElement('td');
        titleCell.colSpan = columnCount - 1;
        titleCell.innerHTML = `
            <strong class="section-title" data-section-index="${sectionIndex}">
                ${section.title || 'Без названия'}
            </strong>
        `;
        row.appendChild(titleCell);
        
        return row;
    }
    
    /**
     * Создание строки элемента
     */
    createItemRow(item, sectionIndex, itemIndex, rowIndex) {
        const row = document.createElement('tr');
        row.className = item.is_header ? 'item-row table-warning' : 'item-row';
        row.dataset.sectionIndex = sectionIndex;
        row.dataset.itemIndex = itemIndex;
        row.dataset.rowIndex = rowIndex;
        
        const template = this.templates[this.estimateType];
        
        // Ячейка действий
        const actionsCell = document.createElement('td');
        actionsCell.innerHTML = `
            <div class="btn-group-vertical btn-group-sm">
                <button class="btn btn-outline-secondary btn-xs item-menu-btn" 
                        data-section-index="${sectionIndex}" 
                        data-item-index="${itemIndex}">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
            </div>
        `;
        row.appendChild(actionsCell);
        
        // Ячейки данных
        template.structure.columns.forEach((column, columnIndex) => {
            const cell = this.createTableCell(item, column, columnIndex, rowIndex);
            row.appendChild(cell);
        });
        
        return row;
    }
    
    /**
     * Создание ячейки таблицы
     */
    createTableCell(item, column, columnIndex, rowIndex) {
        const cell = document.createElement('td');
        cell.dataset.columnIndex = columnIndex;
        cell.dataset.columnType = column.type;
        
        const fieldName = this.getFieldNameByColumn(columnIndex);
        let value = item[fieldName] || (column.default !== undefined ? column.default : '');
        
        if (column.readonly) {
            // Только для чтения - показываем вычисленное значение
            if (column.formula) {
                value = this.calculateFormula(column.formula, item, rowIndex);
            }
            cell.innerHTML = this.formatCellValue(value, column.type);
            cell.className = 'readonly-cell';
        } else {
            // Редактируемая ячейка
            const input = this.createCellInput(value, column);
            input.dataset.fieldName = fieldName;
            input.addEventListener('input', (e) => this.onCellChange(e, rowIndex));
            input.addEventListener('blur', (e) => this.onCellBlur(e, rowIndex));
            cell.appendChild(input);
        }
        
        return cell;
    }
    
    /**
     * Создание поля ввода для ячейки
     */
    createCellInput(value, column) {
        const input = document.createElement('input');
        input.type = column.type === 'numeric' || column.type === 'currency' ? 'number' : 'text';
        input.className = 'form-control form-control-sm cell-input';
        input.value = value;
        
        if (column.type === 'currency' || column.type === 'numeric') {
            input.step = column.type === 'currency' ? '0.01' : '1';
            input.min = '0';
        }
        
        return input;
    }
    
    /**
     * Обработчик изменения ячейки
     */
    onCellChange(event, rowIndex) {
        this.hasChanges = true;
        this.updateStatusIndicator('Изменено');
        
        // Обновляем данные
        this.updateItemData(event.target);
        
        // Пересчитываем формулы
        this.recalculateRow(rowIndex);
        this.updateTotals();
        
        // Планируем автосохранение
        this.scheduleAutoSave();
    }
    
    /**
     * Обработчик потери фокуса ячейки
     */
    onCellBlur(event, rowIndex) {
        // Дополнительная валидация при потере фокуса
        this.validateCell(event.target);
    }
    
    /**
     * Валидация ячейки
     */
    validateCell(input) {
        const columnType = input.closest('td').dataset.columnType;
        const value = input.value;
        
        if (columnType === 'numeric' || columnType === 'currency') {
            const numValue = parseFloat(value);
            if (isNaN(numValue) || numValue < 0) {
                input.value = 0;
                input.classList.add('is-invalid');
                setTimeout(() => {
                    input.classList.remove('is-invalid');
                }, 2000);
            }
        }
        
        return true;
    }
    
    /**
     * Добавление нового раздела
     */
    addSection() {
        if (!this.data.sections) {
            this.data.sections = [];
        }
        
        const newSection = {
            id: `section_${Date.now()}`,
            title: 'Новый раздел',
            type: 'section',
            items: []
        };
        
        this.data.sections.push(newSection);
        this.buildTableBody();
        this.hasChanges = true;
        this.updateStatusIndicator('Изменено');
        this.scheduleAutoSave();
        
        this.showNotification('Раздел добавлен', 'success');
    }
    
    /**
     * Добавление новой строки
     */
    addRow() {
        if (!this.data.sections || this.data.sections.length === 0) {
            this.addSection();
        }
        
        const lastSection = this.data.sections[this.data.sections.length - 1];
        if (!lastSection.items) {
            lastSection.items = [];
        }
        
        const newItem = {
            number: lastSection.items.length + 1,
            name: '',
            unit: 'шт',
            quantity: 1,
            price: 0,
            cost: 0,
            markup: 20,
            discount: 0,
            client_price: 0,
            client_cost: 0
        };
        
        lastSection.items.push(newItem);
        this.buildTableBody();
        this.hasChanges = true;
        this.updateStatusIndicator('Изменено');
        this.scheduleAutoSave();
        
        this.showNotification('Строка добавлена', 'success');
    }
    
    /**
     * Экспорт в Excel
     */
    async exportExcel() {
        try {
            const response = await fetch(`${this.apiUrl}/${this.estimateId}/export`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                }
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `estimate_${this.estimateId}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showNotification('Файл Excel загружен', 'success');
            } else {
                throw new Error('Ошибка экспорта');
            }
        } catch (error) {
            this.handleError('Ошибка экспорта в Excel', error);
        }
    }
    
    /**
     * Экспорт в PDF
     */
    async exportPdf() {
        try {
            const response = await fetch(`${this.apiUrl}/${this.estimateId}/export-pdf`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf'
                }
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `estimate_${this.estimateId}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showNotification('Файл PDF загружен', 'success');
            } else {
                throw new Error('Ошибка экспорта');
            }
        } catch (error) {
            this.handleError('Ошибка экспорта в PDF', error);
        }
    }
    
    /**
     * Показать настройки
     */
    showSettings() {
        // Создаем модальное окно настроек
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Настройки редактора</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enableFormulas" 
                                   ${this.settings.enableFormulas ? 'checked' : ''}>
                            <label class="form-check-label" for="enableFormulas">
                                Включить формулы
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enableAutoSave" 
                                   ${this.settings.enableAutoSave ? 'checked' : ''}>
                            <label class="form-check-label" for="enableAutoSave">
                                Автосохранение
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showLineNumbers" 
                                   ${this.settings.showLineNumbers ? 'checked' : ''}>
                            <label class="form-check-label" for="showLineNumbers">
                                Показывать номера строк
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" id="saveSettings">Сохранить</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        // Обработчик сохранения настроек
        modal.querySelector('#saveSettings').addEventListener('click', () => {
            this.settings.enableFormulas = modal.querySelector('#enableFormulas').checked;
            this.settings.enableAutoSave = modal.querySelector('#enableAutoSave').checked;
            this.settings.showLineNumbers = modal.querySelector('#showLineNumbers').checked;
            
            bootstrapModal.hide();
            this.showNotification('Настройки сохранены', 'success');
        });
        
        // Удаляем модальное окно после закрытия
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }
    
    /**
     * Показать контекстное меню для раздела
     */
    showSectionMenu(button, sectionIndex) {
        // Простая реализация контекстного меню
        console.log('Контекстное меню для раздела:', sectionIndex);
        // TODO: Реализовать полноценное контекстное меню
    }
    
    /**
     * Показать контекстное меню для элемента
     */
    showItemMenu(button, sectionIndex, itemIndex) {
        // Простая реализация контекстного меню
        console.log('Контекстное меню для элемента:', sectionIndex, itemIndex);
        // TODO: Реализовать полноценное контекстное меню
    }
    
    /**
     * Сохранение данных
     */
    async save(isAutoSave = false) {
        if (this.isLoading) return;
        
        try {
            this.isLoading = true;
            this.updateStatusIndicator(isAutoSave ? 'Автосохранение...' : 'Сохранение...');
            
            const response = await fetch(`${this.apiUrl}/${this.estimateId}/save-json-data`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(this.data)
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                this.hasChanges = false;
                this.updateStatusIndicator('Сохранено');
                if (!isAutoSave) {
                    this.showNotification('Смета успешно сохранена', 'success');
                }
                
                // Генерируем событие о сохранении данных
                document.dispatchEvent(new CustomEvent('estimate-data-saved', {
                    detail: {
                        estimate: result.estimate || {
                            id: this.estimateId,
                            type: this.estimateType,
                            total_amount: this.data.totals?.grand_total || 0
                        },
                        isAutoSave: isAutoSave
                    }
                }));
                
            } else {
                throw new Error(result.message || 'Ошибка сохранения');
            }
            
        } catch (error) {
            this.handleError('Ошибка сохранения', error);
        } finally {
            this.isLoading = false;
        }
    }
    
    /**
     * Создание данных по умолчанию
     */
    createDefaultData() {
        const template = this.templates[this.estimateType];
        
        if (template) {
            // Если есть шаблон, используем его структуру
            return {
                type: this.estimateType,
                version: template.version || '1.0',
                meta: {
                    ...template.meta,
                    estimate_id: this.estimateId,
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString()
                },
                structure: template.structure || {},
                sections: template.sections ? JSON.parse(JSON.stringify(template.sections)) : [],
                totals: {
                    work_total: 0,
                    materials_total: 0,
                    grand_total: 0,
                    markup_percent: 20,
                    discount_percent: 0
                },
                footer: {
                    items: []
                }
            };
        } else {
            // Базовая структура без шаблона
            return {
                type: this.estimateType,
                version: '1.0',
                meta: {
                    template_name: 'Смета',
                    is_template: false,
                    estimate_id: this.estimateId,
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString()
                },
                structure: {
                    columns: [
                        { title: "№", width: 50, type: "numeric", readonly: true },
                        { title: "Наименование работ", width: 300, type: "text" },
                        { title: "Ед.изм.", width: 80, type: "text" },
                        { title: "Кол-во", width: 80, type: "numeric" },
                        { title: "Цена", width: 100, type: "currency" },
                        { title: "Стоимость", width: 120, type: "currency", formula: "quantity*price", readonly: true },
                        { title: "Наценка %", width: 80, type: "numeric", default: 20 },
                        { title: "Скидка %", width: 80, type: "numeric", default: 0 },
                        { title: "Цена клиента", width: 120, type: "currency", formula: "price*(1+markup/100)*(1-discount/100)", readonly: true },
                        { title: "Сумма клиента", width: 120, type: "currency", formula: "quantity*client_price", readonly: true }
                    ],
                    settings: {
                        readonly_columns: [0,5,8,9],
                        formula_columns: [5,8,9],
                        numeric_columns: [0,3,4,5,6,7,8,9],
                        currency_columns: [4,5,8,9]
                    }
                },
                sections: [],
                totals: {
                    work_total: 0,
                    materials_total: 0,
                    grand_total: 0,
                    markup_percent: 20,
                    discount_percent: 0
                },
                footer: {
                    items: []
                }
            };
        }
    }
    
    /**
     * Обновление индикатора статуса
     */
    updateStatusIndicator(status) {
        const indicator = document.getElementById('statusIndicator');
        if (indicator) {
            indicator.textContent = status;
            indicator.className = 'badge ' + this.getStatusBadgeClass(status);
        }
    }
    
    /**
     * Получение класса для индикатора статуса
     */
    getStatusBadgeClass(status) {
        const classes = {
            'Сохранено': 'bg-success',
            'Изменено': 'bg-warning',
            'Сохранение...': 'bg-info',
            'Автосохранение...': 'bg-info',
            'Ошибка': 'bg-danger'
        };
        return classes[status] || 'bg-secondary';
    }
    
    /**
     * Показать уведомление
     */
    showNotification(message, type = 'info') {
        // Простая реализация уведомлений
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    /**
     * Показать загрузку
     */
    showLoading(message = 'Загрузка...') {
        if (this.container) {
            this.container.innerHTML = `
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2 text-muted">${message}</p>
                </div>
            `;
        }
    }
    
    /**
     * Скрыть загрузку
     */
    hideLoading() {
        // Загрузка скрывается при создании интерфейса
    }
    
    /**
     * Обработка ошибок
     */
    handleError(message, error) {
        this.log(message, error);
        this.updateStatusIndicator('Ошибка');
        this.showNotification(message, 'danger');
    }
    
    /**
     * Логирование
     */
    log(message, data = null) {
        console.log(`[EstimateEditor] ${message}`, data);
    }
    
    /**
     * Очистка ресурсов
     */
    destroy() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
        }
        
        // Сохраняем перед уничтожением, если есть изменения
        if (this.hasChanges) {
            this.save();
        }
    }
    
    /**
     * Обновление итогов
     */
    updateTotals() {
        let workTotal = 0;
        let clientTotal = 0;
        
        if (this.data && this.data.sections) {
            this.data.sections.forEach(section => {
                if (section.items) {
                    section.items.forEach(item => {
                        if (!item.is_header) {
                            workTotal += (item.quantity || 0) * (item.price || 0);
                            clientTotal += (item.quantity || 0) * (item.client_price || 0);
                        }
                    });
                }
            });
        }
        
        // Обновляем отображение в основной панели итогов
        const workTotalEl = document.getElementById('workTotal');
        const clientTotalEl = document.getElementById('clientTotal');
        const grandTotalEl = document.getElementById('grandTotal');
        
        if (workTotalEl) workTotalEl.textContent = this.formatCellValue(workTotal, 'currency');
        if (clientTotalEl) clientTotalEl.textContent = this.formatCellValue(clientTotal, 'currency');
        if (grandTotalEl) grandTotalEl.textContent = this.formatCellValue(clientTotal, 'currency');
        
        // Обновляем отображение в footer таблицы
        const workTotalFooterEl = document.getElementById('workTotalFooter');
        const materialsTotalFooterEl = document.getElementById('materialsTotalFooter');
        const grandTotalFooterEl = document.getElementById('grandTotalFooter');
        
        if (workTotalFooterEl) workTotalFooterEl.textContent = this.formatCellValue(workTotal, 'currency');
        if (materialsTotalFooterEl) materialsTotalFooterEl.textContent = this.formatCellValue(0, 'currency');
        if (grandTotalFooterEl) grandTotalFooterEl.textContent = this.formatCellValue(clientTotal, 'currency');
        
        // Обновляем данные
        if (this.data && this.data.totals) {
            this.data.totals.work_total = workTotal;
            this.data.totals.materials_total = 0;
            this.data.totals.grand_total = clientTotal;
            this.data.totals.client_work_total = clientTotal;
            this.data.totals.client_grand_total = clientTotal;
        }
    }
    
    /**
     * Обновление данных элемента
     */
    updateItemData(input) {
        const row = input.closest('tr');
        const sectionIndex = parseInt(row.dataset.sectionIndex);
        const itemIndex = parseInt(row.dataset.itemIndex);
        const fieldName = input.dataset.fieldName;
        
        if (this.data.sections[sectionIndex] && 
            this.data.sections[sectionIndex].items[itemIndex]) {
            this.data.sections[sectionIndex].items[itemIndex][fieldName] = 
                this.parseInputValue(input.value, input.closest('td').dataset.columnType);
        }
    }
    
    /**
     * Парсинг значения ввода
     */
    parseInputValue(value, type) {
        if (type === 'numeric' || type === 'currency') {
            const numValue = parseFloat(value);
            return isNaN(numValue) ? 0 : numValue;
        }
        return value;
    }
    
    /**
     * Пересчет формул для строки
     */
    recalculateRow(rowIndex) {
        const row = document.querySelector(`tr[data-row-index="${rowIndex}"]`);
        if (!row) return;
        
        const sectionIndex = parseInt(row.dataset.sectionIndex);
        const itemIndex = parseInt(row.dataset.itemIndex);
        const item = this.data.sections[sectionIndex]?.items[itemIndex];
        
        if (!item) return;
        
        const template = this.templates[this.estimateType];
        
        template.structure.columns.forEach((column, columnIndex) => {
            if (column.formula && column.readonly) {
                const value = this.calculateFormula(column.formula, item, rowIndex);
                const cell = row.children[columnIndex + 1]; // +1 для столбца действий
                
                if (cell && cell.classList.contains('readonly-cell')) {
                    cell.innerHTML = this.formatCellValue(value, column.type);
                }
                
                // Обновляем значение в данных
                const fieldName = this.getFieldNameByColumn(columnIndex);
                item[fieldName] = value;
            }
        });
    }
    
    /**
     * Вычисление формулы
     */
    calculateFormula(formula, item, rowIndex) {
        try {
            // Заменяем переменные в формуле на значения
            let processedFormula = formula
                .replace(/\bquantity\b/g, item.quantity || 0)
                .replace(/\bprice\b/g, item.price || 0)
                .replace(/\bmarkup\b/g, item.markup || 0)
                .replace(/\bdiscount\b/g, item.discount || 0)
                .replace(/\bclient_price\b/g, item.client_price || 0);
            
            // Дополнительная очистка от некорректных переменных
            processedFormula = processedFormula
                .replace(/\bclient_\d+\b/g, '0') // Заменяем client_0, client_1 и т.д. на 0
                .replace(/\bundefined\b/g, '0')
                .replace(/\bNaN\b/g, '0');
            
            // Проверяем что формула содержит только числа и операторы
            if (!/^[\d\s+\-*/().,]+$/.test(processedFormula)) {
                this.log('Некорректная формула после обработки:', processedFormula);
                return 0;
            }
            
            // Безопасное вычисление
            return Function('"use strict"; return (' + processedFormula + ')')();
        } catch (error) {
            this.log('Ошибка вычисления формулы:', error);
            return 0;
        }
    }
    
    /**
     * Получение имени поля по индексу столбца
     */
    getFieldNameByColumn(columnIndex) {
        const fieldMap = {
            0: 'number',
            1: 'name',
            2: 'unit',
            3: 'quantity',
            4: 'price',
            5: 'cost',
            6: 'markup',
            7: 'discount',
            8: 'client_price',
            9: 'client_cost'
        };
        return fieldMap[columnIndex] || 'unknown';
    }
    
    /**
     * Форматирование значения ячейки
     */
    formatCellValue(value, type) {
        if (type === 'currency') {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: this.settings.decimalPlaces
            }).format(value || 0);
        }
        
        if (type === 'numeric') {
            return new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(value || 0);
        }
        
        return value || '';
    }
    
    /**
     * Настройка автосохранения
     */
    setupAutoSave() {
        if (this.settings.enableAutoSave) {
            this.autoSaveTimer = setInterval(() => {
                if (this.hasChanges && !this.isLoading) {
                    this.save(true); // автосохранение
                }
            }, this.autoSaveInterval);
        }
    }
    
    /**
     * Планирование автосохранения
     */
    scheduleAutoSave() {
        if (this.autoSaveTimer) {
            clearTimeout(this.autoSaveTimer);
        }
        
        this.autoSaveTimer = setTimeout(() => {
            if (this.hasChanges && !this.isLoading) {
                this.save(true);
            }
        }, 3000); // Сохранение через 3 секунды после последнего изменения
    }
    
    /**
     * Настройка обработчиков событий
     */
    setupEventHandlers() {
        // Кнопка сохранения
        document.getElementById('saveBtn')?.addEventListener('click', () => this.save());
        
        // Кнопка добавления раздела
        document.getElementById('addSectionBtn')?.addEventListener('click', () => this.addSection());
        
        // Кнопка добавления строки
        document.getElementById('addRowBtn')?.addEventListener('click', () => this.addRow());
        
        // Экспорт
        document.getElementById('exportExcelBtn')?.addEventListener('click', () => this.exportExcel());
        document.getElementById('exportPdfBtn')?.addEventListener('click', () => this.exportPdf());
        
        // Настройки
        document.getElementById('settingsBtn')?.addEventListener('click', () => this.showSettings());
        
        // Обработчики контекстного меню
        this.setupContextMenus();
    }
    
    /**
     * Настройка контекстных меню
     */
    setupContextMenus() {
        // Меню для разделов
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('section-menu-btn')) {
                e.preventDefault();
                this.showSectionMenu(e.target, parseInt(e.target.dataset.sectionIndex));
            }
        });
        
        // Меню для элементов
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('item-menu-btn')) {
                e.preventDefault();
                this.showItemMenu(e.target, 
                    parseInt(e.target.dataset.sectionIndex),
                    parseInt(e.target.dataset.itemIndex)
                );
            }
        });
    }
}

// Инициализация редактора при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('estimate-editor-container');
    if (container && window.estimateId) {
        window.estimateEditor = new EstimateEditor('estimate-editor-container', {
            estimateId: window.estimateId,
            estimateType: window.estimateType
        });
    }
});

// Экспорт для глобального использования
window.EstimateEditor = EstimateEditor;
