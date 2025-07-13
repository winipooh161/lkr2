/**
 * EstimateEditorUI - Модуль пользовательского интерфейса для редактора смет
 * 
 * Отвечает за построение и обновление UI элементов редактора смет.
 * Работает вместе с основным классом EstimateEditor.
 * 
 * @version 1.0
 */

class EstimateEditorUI {
    /**
     * @param {EstimateEditor} editor - Экземпляр основного редактора
     * @param {Object} options - Опции интерфейса
     */
    constructor(editor, options = {}) {
        this.editor = editor;
        
        // Настройки UI
        this.options = Object.assign({
            theme: 'light',
            showLineNumbers: true,
            showToolbar: true,
            compactMode: false,
            animationsEnabled: true
        }, options);
        
        // Ссылки на DOM элементы
        this.elements = {};
        
        // Привязка методов
        this.bindMethods();
    }
    
    /**
     * Привязка методов к контексту
     */
    bindMethods() {
        this.handleThemeToggle = this.handleThemeToggle.bind(this);
        this.handleColumnVisibilityToggle = this.handleColumnVisibilityToggle.bind(this);
        this.handleCompactModeToggle = this.handleCompactModeToggle.bind(this);
        this.handleSettingsOpen = this.handleSettingsOpen.bind(this);
    }
    
    /**
     * Инициализация пользовательского интерфейса
     */
    initialize() {
        this.createSettingsPanel();
        this.createContextMenu();
        this.setupEventListeners();
        
        // Применяем текущие настройки
        this.applySettings();
        
        return this;
    }
    
    /**
     * Создание панели настроек
     */
    createSettingsPanel() {
        // Создаем модальное окно для настроек
        const settingsModal = document.createElement('div');
        settingsModal.className = 'modal fade';
        settingsModal.id = 'estimate-settings-modal';
        settingsModal.setAttribute('tabindex', '-1');
        settingsModal.setAttribute('aria-hidden', 'true');
        
        settingsModal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Настройки редактора</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="themeToggle" ${this.options.theme === 'dark' ? 'checked' : ''}>
                            <label class="form-check-label" for="themeToggle">Темная тема</label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="lineNumbersToggle" ${this.options.showLineNumbers ? 'checked' : ''}>
                            <label class="form-check-label" for="lineNumbersToggle">Показывать номера строк</label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="compactModeToggle" ${this.options.compactMode ? 'checked' : ''}>
                            <label class="form-check-label" for="compactModeToggle">Компактный режим</label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="animationsToggle" ${this.options.animationsEnabled ? 'checked' : ''}>
                            <label class="form-check-label" for="animationsToggle">Анимации</label>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Видимость колонок</h6>
                        <div id="columnVisibilityControls" class="mb-3">
                            <!-- Динамически заполняется -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="button" class="btn btn-primary" id="saveSettingsBtn">Сохранить</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(settingsModal);
        this.elements.settingsModal = settingsModal;
        
        // Заполняем элементы управления видимостью колонок
        this.updateColumnVisibilityControls();
    }
    
    /**
     * Обновление элементов управления видимостью колонок
     */
    updateColumnVisibilityControls() {
        const container = document.getElementById('columnVisibilityControls');
        if (!container) return;
        
        container.innerHTML = '';
        
        const columns = this.editor.data?.structure?.columns || [];
        
        columns.forEach((column, index) => {
            const isVisible = column.visible !== false;
            const checkboxId = `column-visibility-${index}`;
            
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'form-check form-switch mb-2';
            checkboxDiv.innerHTML = `
                <input class="form-check-input" type="checkbox" id="${checkboxId}" 
                       data-column-index="${index}" ${isVisible ? 'checked' : ''}>
                <label class="form-check-label" for="${checkboxId}">${column.title || `Колонка ${index + 1}`}</label>
            `;
            
            container.appendChild(checkboxDiv);
            
            // Добавляем обработчик
            checkboxDiv.querySelector('input').addEventListener('change', this.handleColumnVisibilityToggle);
        });
    }
    
    /**
     * Создание контекстного меню
     */
    createContextMenu() {
        const contextMenu = document.createElement('div');
        contextMenu.className = 'estimate-context-menu';
        contextMenu.style.display = 'none';
        contextMenu.innerHTML = `
            <div class="context-menu-item" data-action="addRow">
                <i class="fas fa-plus"></i> Добавить строку
            </div>
            <div class="context-menu-item" data-action="addSection">
                <i class="fas fa-folder-plus"></i> Добавить раздел
            </div>
            <div class="context-menu-item" data-action="deleteRow">
                <i class="fas fa-trash"></i> Удалить строку
            </div>
            <div class="context-menu-item" data-action="copyRow">
                <i class="fas fa-copy"></i> Копировать строку
            </div>
            <div class="context-menu-item" data-action="pasteRow">
                <i class="fas fa-paste"></i> Вставить строку
            </div>
            <div class="context-menu-item" data-action="moveUp">
                <i class="fas fa-arrow-up"></i> Переместить вверх
            </div>
            <div class="context-menu-item" data-action="moveDown">
                <i class="fas fa-arrow-down"></i> Переместить вниз
            </div>
        `;
        
        document.body.appendChild(contextMenu);
        this.elements.contextMenu = contextMenu;
        
        // Обработчики для контекстного меню
        this.setupContextMenuHandlers();
    }
    
    /**
     * Настройка обработчиков для контекстного меню
     */
    setupContextMenuHandlers() {
        const contextMenu = this.elements.contextMenu;
        if (!contextMenu) return;
        
        // Скрытие контекстного меню при клике вне его
        document.addEventListener('click', (e) => {
            if (!contextMenu.contains(e.target)) {
                contextMenu.style.display = 'none';
            }
        });
        
        // Обработчики для пунктов меню
        contextMenu.querySelectorAll('.context-menu-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const action = e.currentTarget.dataset.action;
                const targetRow = contextMenu.dataset.targetRow;
                
                this.handleContextMenuAction(action, targetRow);
                contextMenu.style.display = 'none';
            });
        });
        
        // Отключение стандартного контекстного меню браузера
        this.editor.table.addEventListener('contextmenu', (e) => {
            const row = e.target.closest('tr');
            if (row) {
                e.preventDefault();
                
                // Сохраняем идентификатор строки
                contextMenu.dataset.targetRow = row.dataset.id;
                
                // Позиционируем и показываем меню
                contextMenu.style.left = `${e.pageX}px`;
                contextMenu.style.top = `${e.pageY}px`;
                contextMenu.style.display = 'block';
            }
        });
    }
    
    /**
     * Обработка действий контекстного меню
     */
    handleContextMenuAction(action, rowId) {
        if (!rowId) return;
        
        switch (action) {
            case 'addRow':
                this.editor.addRow(rowId);
                break;
                
            case 'addSection':
                this.editor.addSection();
                break;
                
            case 'deleteRow':
                this.editor.deleteRow(rowId);
                break;
                
            case 'copyRow':
                this.copyRow(rowId);
                break;
                
            case 'pasteRow':
                this.pasteRow(rowId);
                break;
                
            case 'moveUp':
                this.moveRow(rowId, 'up');
                break;
                
            case 'moveDown':
                this.moveRow(rowId, 'down');
                break;
        }
    }
    
    /**
     * Копирование строки
     */
    copyRow(rowId) {
        const rowIndex = this.editor.findRowIndexById(rowId);
        if (rowIndex === -1) return;
        
        const currentSheet = this.editor.data.currentSheet || 0;
        const row = this.editor.data.sheets[currentSheet].data[rowIndex];
        
        // Сохраняем копию строки в локальном хранилище
        try {
            localStorage.setItem('estimate_clipboard', JSON.stringify(row));
            this.editor.showNotification('Строка скопирована', 'info');
        } catch (error) {
            this.editor.showError('Ошибка при копировании строки', error.message);
        }
    }
    
    /**
     * Вставка строки
     */
    pasteRow(rowId) {
        try {
            const clipboardData = localStorage.getItem('estimate_clipboard');
            if (!clipboardData) {
                this.editor.showNotification('Буфер обмена пуст', 'warning');
                return;
            }
            
            const rowData = JSON.parse(clipboardData);
            
            // Создаем новую строку с новым ID на основе данных из буфера
            const newRow = {
                ...rowData,
                _id: this.editor.generateId()
            };
            
            // Находим индекс для вставки
            const rowIndex = this.editor.findRowIndexById(rowId);
            if (rowIndex === -1) return;
            
            // Вставляем строку после указанной
            const currentSheet = this.editor.data.currentSheet || 0;
            this.editor.data.sheets[currentSheet].data.splice(rowIndex + 1, 0, newRow);
            
            // Перестраиваем таблицу
            this.editor.buildTable();
            this.editor.isDirty = true;
            
            this.editor.showNotification('Строка вставлена', 'success');
        } catch (error) {
            this.editor.showError('Ошибка при вставке строки', error.message);
        }
    }
    
    /**
     * Перемещение строки вверх/вниз
     */
    moveRow(rowId, direction) {
        const rowIndex = this.editor.findRowIndexById(rowId);
        if (rowIndex === -1) return;
        
        const currentSheet = this.editor.data.currentSheet || 0;
        const data = this.editor.data.sheets[currentSheet].data;
        
        let targetIndex;
        
        if (direction === 'up') {
            if (rowIndex <= 0) return;
            targetIndex = rowIndex - 1;
            
            // Если перемещаем строку вверх, проверяем, что она не попадет внутрь другого раздела
            if (data[rowIndex]._type !== 'header' && data[targetIndex]._type === 'header') {
                targetIndex--;
            }
        } else { // down
            if (rowIndex >= data.length - 1) return;
            targetIndex = rowIndex + 1;
            
            // Если перемещаем заголовок раздела вниз, перемещаем его за все строки раздела
            if (data[rowIndex]._type === 'header') {
                while (targetIndex < data.length && data[targetIndex]._type !== 'header') {
                    targetIndex++;
                }
            }
        }
        
        if (targetIndex < 0 || targetIndex >= data.length) return;
        
        // Перемещаем строку
        const row = data.splice(rowIndex, 1)[0];
        data.splice(targetIndex, 0, row);
        
        // Перестраиваем таблицу
        this.editor.buildTable();
        this.editor.isDirty = true;
    }
    
    /**
     * Настройка обработчиков событий
     */
    setupEventListeners() {
        // Обработчик для кнопки настроек
        const settingsBtn = document.querySelector('#settingsBtn');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', this.handleSettingsOpen);
        }
        
        // Обработчики для элементов настроек
        const themeToggle = document.getElementById('themeToggle');
        const lineNumbersToggle = document.getElementById('lineNumbersToggle');
        const compactModeToggle = document.getElementById('compactModeToggle');
        const animationsToggle = document.getElementById('animationsToggle');
        const saveSettingsBtn = document.getElementById('saveSettingsBtn');
        
        if (themeToggle) {
            themeToggle.addEventListener('change', this.handleThemeToggle);
        }
        
        if (lineNumbersToggle) {
            lineNumbersToggle.addEventListener('change', (e) => {
                this.options.showLineNumbers = e.target.checked;
                this.applySettings();
            });
        }
        
        if (compactModeToggle) {
            compactModeToggle.addEventListener('change', this.handleCompactModeToggle);
        }
        
        if (animationsToggle) {
            animationsToggle.addEventListener('change', (e) => {
                this.options.animationsEnabled = e.target.checked;
                this.applySettings();
            });
        }
        
        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener('click', () => {
                this.saveSettings();
                
                // Закрываем модальное окно
                const modal = bootstrap.Modal.getInstance(this.elements.settingsModal);
                if (modal) {
                    modal.hide();
                }
            });
        }
    }
    
    /**
     * Обработчик открытия настроек
     */
    handleSettingsOpen() {
        // Обновляем элементы управления видимостью колонок
        this.updateColumnVisibilityControls();
        
        // Показываем модальное окно
        const modal = new bootstrap.Modal(this.elements.settingsModal);
        modal.show();
    }
    
    /**
     * Обработчик переключения темы
     */
    handleThemeToggle(e) {
        this.options.theme = e.target.checked ? 'dark' : 'light';
        this.applyTheme();
    }
    
    /**
     * Обработчик переключения видимости колонок
     */
    handleColumnVisibilityToggle(e) {
        const columnIndex = parseInt(e.target.dataset.columnIndex);
        const isVisible = e.target.checked;
        
        if (isNaN(columnIndex)) return;
        
        // Обновляем настройку видимости в структуре данных
        const columns = this.editor.data.structure.columns;
        if (columns && columns[columnIndex]) {
            columns[columnIndex].visible = isVisible;
            
            // Перестраиваем таблицу
            this.editor.buildTable();
        }
    }
    
    /**
     * Обработчик переключения компактного режима
     */
    handleCompactModeToggle(e) {
        this.options.compactMode = e.target.checked;
        this.applyCompactMode();
    }
    
    /**
     * Применение текущих настроек
     */
    applySettings() {
        this.applyTheme();
        this.applyCompactMode();
        this.applyLineNumbers();
        this.applyAnimations();
    }
    
    /**
     * Применение настроек темы
     */
    applyTheme() {
        const editor = this.editor.container.querySelector('.estimate-editor');
        if (!editor) return;
        
        if (this.options.theme === 'dark') {
            editor.classList.add('dark-theme');
            document.body.classList.add('dark-theme-body');
        } else {
            editor.classList.remove('dark-theme');
            document.body.classList.remove('dark-theme-body');
        }
    }
    
    /**
     * Применение настроек компактного режима
     */
    applyCompactMode() {
        const editor = this.editor.container.querySelector('.estimate-editor');
        if (!editor) return;
        
        if (this.options.compactMode) {
            editor.classList.add('compact-mode');
        } else {
            editor.classList.remove('compact-mode');
        }
    }
    
    /**
     * Применение настроек отображения номеров строк
     */
    applyLineNumbers() {
        const table = this.editor.table;
        if (!table) return;
        
        if (this.options.showLineNumbers) {
            table.classList.add('show-line-numbers');
        } else {
            table.classList.remove('show-line-numbers');
        }
    }
    
    /**
     * Применение настроек анимаций
     */
    applyAnimations() {
        const editor = this.editor.container.querySelector('.estimate-editor');
        if (!editor) return;
        
        if (this.options.animationsEnabled) {
            editor.classList.add('animations-enabled');
        } else {
            editor.classList.remove('animations-enabled');
        }
    }
    
    /**
     * Сохранение настроек
     */
    saveSettings() {
        try {
            localStorage.setItem('estimate_editor_settings', JSON.stringify(this.options));
            this.editor.showNotification('Настройки сохранены', 'success');
        } catch (error) {
            this.editor.showError('Ошибка сохранения настроек', error.message);
        }
    }
    
    /**
     * Загрузка сохраненных настроек
     */
    loadSettings() {
        try {
            const savedSettings = localStorage.getItem('estimate_editor_settings');
            if (savedSettings) {
                this.options = { ...this.options, ...JSON.parse(savedSettings) };
                this.applySettings();
            }
        } catch (error) {
            console.error('Ошибка загрузки настроек:', error);
        }
    }
}
