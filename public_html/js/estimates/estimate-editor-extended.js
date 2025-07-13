/**
 * Расширенная функциональность редактора смет
 * Версия: 3.0
 */

// Расширение класса EstimateEditor
if (typeof EstimateEditor !== 'undefined') {
    
    /**
     * Добавление нового раздела
     */
    EstimateEditor.prototype.addSection = function() {
        const modal = this.createSectionModal();
        document.body.appendChild(modal);
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    };
    
    /**
     * Создание модального окна для добавления раздела
     */
    EstimateEditor.prototype.createSectionModal = function() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Добавить раздел</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Выберите из шаблона:</label>
                            <select class="form-select" id="templateSectionSelect">
                                <option value="">Создать новый раздел</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Название раздела:</label>
                            <input type="text" class="form-control" id="sectionTitleInput" 
                                   placeholder="Введите название раздела">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Позиция:</label>
                            <select class="form-select" id="sectionPositionSelect">
                                <option value="end">В конец</option>
                                <option value="start">В начало</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" id="addSectionConfirmBtn">Добавить</button>
                    </div>
                </div>
            </div>
        `;
        
        // Заполняем список шаблонов
        this.populateTemplateSections(modal.querySelector('#templateSectionSelect'));
        
        // Обработчик подтверждения
        modal.querySelector('#addSectionConfirmBtn').addEventListener('click', () => {
            this.confirmAddSection(modal);
        });
        
        // Обработчик выбора шаблона
        modal.querySelector('#templateSectionSelect').addEventListener('change', (e) => {
            const titleInput = modal.querySelector('#sectionTitleInput');
            if (e.target.value) {
                const template = this.findTemplateSection(e.target.value);
                if (template) {
                    titleInput.value = template.title;
                }
            }
        });
        
        return modal;
    };
    
    /**
     * Заполнение списка разделов из шаблонов
     */
    EstimateEditor.prototype.populateTemplateSections = function(select) {
        if (!select || !this.templates) return;
        
        try {
            // Очищаем существующие опции, кроме первой (Создать новый раздел)
            while (select.options.length > 1) {
                select.remove(1);
            }
            
            // Создаем группы для каждого типа шаблонов
            Object.entries(this.templates).forEach(([type, template]) => {
                if (!template || !template.sections || !Array.isArray(template.sections)) return;
                
                // Создаем группу для данного типа шаблона, если есть разделы
                if (template.sections.length > 0) {
                    const group = document.createElement('optgroup');
                    group.label = template.title || type.charAt(0).toUpperCase() + type.slice(1);
                    
                    // Добавляем разделы в группу
                    template.sections.forEach(section => {
                        if (!section || !section.id || !section.title) return;
                        
                        const option = document.createElement('option');
                        option.value = `${template.type || type}_${section.id}`;
                        option.textContent = section.title;
                        group.appendChild(option);
                    });
                    
                    // Добавляем группу только если в ней есть опции
                    if (group.childNodes.length > 0) {
                        select.appendChild(group);
                    }
                }
            });
        } catch (error) {
            console.error('❌ Ошибка при заполнении списка разделов:', error);
        }
    };
    
    /**
     * Поиск раздела в шаблонах
     */
    EstimateEditor.prototype.findTemplateSection = function(templateId) {
        try {
            if (!templateId) return null;
            
            const parts = templateId.split('_');
            if (parts.length < 2) return null;
            
            const templateType = parts[0];
            const sectionId = parts[1];
            
            if (!this.templates || !this.templates[templateType]) {
                console.warn(`❗ Шаблон типа "${templateType}" не найден`);
                return null;
            }
            
            const template = this.templates[templateType];
            if (!template.sections || !Array.isArray(template.sections)) {
                console.warn(`❗ Разделы не найдены в шаблоне "${templateType}"`);
                return null;
            }
            
            const section = template.sections.find(s => s && s.id === sectionId);
            if (!section) {
                console.warn(`❗ Раздел с ID "${sectionId}" не найден в шаблоне "${templateType}"`);
            }
            
            return section || null;
        } catch (error) {
            console.error('❌ Ошибка при поиске раздела в шаблонах:', error);
            return null;
        }
    };
    
    /**
     * Подтверждение добавления раздела
     */
    EstimateEditor.prototype.confirmAddSection = function(modal) {
        const templateSelect = modal.querySelector('#templateSectionSelect');
        const titleInput = modal.querySelector('#sectionTitleInput');
        const positionSelect = modal.querySelector('#sectionPositionSelect');
        
        const title = titleInput.value.trim();
        if (!title) {
            this.showNotification('Введите название раздела', 'warning');
            return;
        }
        
        let newSection = {
            id: 'section_' + Date.now(),
            title: title,
            type: 'section',
            items: []
        };
        
        // Если выбран шаблон, копируем элементы
        try {
            if (templateSelect.value) {
                const templateSection = this.findTemplateSection(templateSelect.value);
                if (templateSection && templateSection.items && Array.isArray(templateSection.items)) {
                    // Делаем глубокую копию, обрабатываем ошибки
                    try {
                        newSection.items = JSON.parse(JSON.stringify(templateSection.items));
                    } catch (jsonError) {
                        console.error('❌ Ошибка копирования элементов шаблона:', jsonError);
                        newSection.items = [];
                        
                        // Попробуем скопировать элементы вручную
                        templateSection.items.forEach(item => {
                            if (item) {
                                const newItem = {};
                                for (const key in item) {
                                    if (Object.prototype.hasOwnProperty.call(item, key)) {
                                        newItem[key] = item[key];
                                    }
                                }
                                newSection.items.push(newItem);
                            }
                        });
                    }
                } else {
                    console.warn('⚠️ Элементы шаблона не найдены или неверный формат');
                    newSection.items = [];
                }
            }
        } catch (error) {
            console.error('❌ Ошибка при копировании элементов шаблона:', error);
            newSection.items = [];
        }
        
        // Добавляем раздел в данные
        if (!this.data.sections) {
            this.data.sections = [];
        }
        
        if (positionSelect.value === 'start') {
            this.data.sections.unshift(newSection);
        } else {
            this.data.sections.push(newSection);
        }
        
        // Перестраиваем таблицу
        this.buildTableBody();
        this.updateTotals();
        
        this.hasChanges = true;
        this.updateStatusIndicator('Изменено');
        
        // Закрываем модальное окно
        bootstrap.Modal.getInstance(modal).hide();
        
        this.showNotification('Раздел добавлен', 'success');
    };
    
    /**
     * Добавление новой строки
     */
    EstimateEditor.prototype.addRow = function() {
        if (!this.data.sections || this.data.sections.length === 0) {
            this.showNotification('Сначала добавьте раздел', 'warning');
            return;
        }
        
        const modal = this.createRowModal();
        document.body.appendChild(modal);
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    };
    
    /**
     * Создание модального окна для добавления строки
     */
    EstimateEditor.prototype.createRowModal = function() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Добавить строку</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Раздел:</label>
                            <select class="form-select" id="sectionSelect">
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Выберите из шаблона:</label>
                            <select class="form-select" id="templateItemSelect">
                                <option value="">Создать новую строку</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Наименование:</label>
                                    <input type="text" class="form-control" id="itemNameInput" 
                                           placeholder="Введите наименование работы/материала">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Единица измерения:</label>
                                    <input type="text" class="form-control" id="itemUnitInput" 
                                           value="шт" placeholder="ед.изм.">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Количество:</label>
                                    <input type="number" class="form-control" id="itemQuantityInput" 
                                           value="0" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Цена:</label>
                                    <input type="number" class="form-control" id="itemPriceInput" 
                                           value="0" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Наценка %:</label>
                                    <input type="number" class="form-control" id="itemMarkupInput" 
                                           value="20" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Скидка %:</label>
                                    <input type="number" class="form-control" id="itemDiscountInput" 
                                           value="0" min="0" max="100">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isHeaderCheck">
                                <label class="form-check-label" for="isHeaderCheck">
                                    Заголовок группы (не участвует в расчетах)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" id="addRowConfirmBtn">Добавить</button>
                    </div>
                </div>
            </div>
        `;
        
        // Заполняем список разделов
        this.populateSectionSelect(modal.querySelector('#sectionSelect'));
        
        // Заполняем список шаблонных элементов
        this.populateTemplateItems(modal.querySelector('#templateItemSelect'));
        
        // Обработчик подтверждения
        modal.querySelector('#addRowConfirmBtn').addEventListener('click', () => {
            this.confirmAddRow(modal);
        });
        
        // Обработчик выбора шаблона
        modal.querySelector('#templateItemSelect').addEventListener('change', (e) => {
            this.fillRowFromTemplate(modal, e.target.value);
        });
        
        return modal;
    };
    
    /**
     * Заполнение списка разделов
     */
    EstimateEditor.prototype.populateSectionSelect = function(select) {
        this.data.sections.forEach((section, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = section.title;
            select.appendChild(option);
        });
    };
    
    /**
     * Заполнение списка элементов из шаблонов
     */
    EstimateEditor.prototype.populateTemplateItems = function(select) {
        Object.values(this.templates).forEach(template => {
            if (template && template.sections) {
                template.sections.forEach(section => {
                    if (section.items) {
                        section.items.forEach((item, index) => {
                            const option = document.createElement('option');
                            option.value = `${template.type}_${section.id}_${index}`;
                            option.textContent = `${section.title}: ${item.name}`;
                            select.appendChild(option);
                        });
                    }
                });
            }
        });
    };
    
    /**
     * Заполнение формы данными из шаблона
     */
    EstimateEditor.prototype.fillRowFromTemplate = function(modal, templateId) {
        if (!templateId) return;
        
        const [templateType, sectionId, itemIndex] = templateId.split('_');
        const template = this.templates[templateType];
        const section = template?.sections?.find(s => s.id === sectionId);
        const item = section?.items?.[parseInt(itemIndex)];
        
        if (item) {
            modal.querySelector('#itemNameInput').value = item.name || '';
            modal.querySelector('#itemUnitInput').value = item.unit || 'шт';
            modal.querySelector('#itemQuantityInput').value = item.quantity || 0;
            modal.querySelector('#itemPriceInput').value = item.price || 0;
            modal.querySelector('#itemMarkupInput').value = item.markup || 20;
            modal.querySelector('#itemDiscountInput').value = item.discount || 0;
            modal.querySelector('#isHeaderCheck').checked = item.is_header || false;
        }
    };
    
    /**
     * Подтверждение добавления строки
     */
    EstimateEditor.prototype.confirmAddRow = function(modal) {
        const sectionIndex = parseInt(modal.querySelector('#sectionSelect').value);
        const name = modal.querySelector('#itemNameInput').value.trim();
        
        if (!name) {
            this.showNotification('Введите наименование', 'warning');
            return;
        }
        
        const newItem = {
            name: name,
            unit: modal.querySelector('#itemUnitInput').value.trim() || 'шт',
            quantity: parseFloat(modal.querySelector('#itemQuantityInput').value) || 0,
            price: parseFloat(modal.querySelector('#itemPriceInput').value) || 0,
            markup: parseFloat(modal.querySelector('#itemMarkupInput').value) || 20,
            discount: parseFloat(modal.querySelector('#itemDiscountInput').value) || 0,
            is_header: modal.querySelector('#isHeaderCheck').checked
        };
        
        // Добавляем элемент в раздел
        if (this.data.sections[sectionIndex]) {
            this.data.sections[sectionIndex].items.push(newItem);
        }
        
        // Перестраиваем таблицу
        this.buildTableBody();
        this.updateTotals();
        
        this.hasChanges = true;
        this.updateStatusIndicator('Изменено');
        
        // Закрываем модальное окно
        bootstrap.Modal.getInstance(modal).hide();
        
        this.showNotification('Строка добавлена', 'success');
    };
    
    /**
     * Показ контекстного меню для раздела
     */
    EstimateEditor.prototype.showSectionMenu = function(button, sectionIndex) {
        const menu = this.createContextMenu([
            { text: 'Редактировать', action: () => this.editSection(sectionIndex) },
            { text: 'Добавить строку', action: () => this.addRowToSection(sectionIndex) },
            { text: 'Переместить вверх', action: () => this.moveSectionUp(sectionIndex) },
            { text: 'Переместить вниз', action: () => this.moveSectionDown(sectionIndex) },
            { divider: true },
            { text: 'Удалить', action: () => this.deleteSection(sectionIndex), danger: true }
        ]);
        
        this.showContextMenu(menu, button);
    };
    
    /**
     * Показ контекстного меню для элемента
     */
    EstimateEditor.prototype.showItemMenu = function(button, sectionIndex, itemIndex) {
        const menu = this.createContextMenu([
            { text: 'Редактировать', action: () => this.editItem(sectionIndex, itemIndex) },
            { text: 'Дублировать', action: () => this.duplicateItem(sectionIndex, itemIndex) },
            { text: 'Переместить вверх', action: () => this.moveItemUp(sectionIndex, itemIndex) },
            { text: 'Переместить вниз', action: () => this.moveItemDown(sectionIndex, itemIndex) },
            { divider: true },
            { text: 'Удалить', action: () => this.deleteItem(sectionIndex, itemIndex), danger: true }
        ]);
        
        this.showContextMenu(menu, button);
    };
    
    /**
     * Создание контекстного меню
     */
    EstimateEditor.prototype.createContextMenu = function(items) {
        const menu = document.createElement('div');
        menu.className = 'context-menu';
        
        items.forEach(item => {
            if (item.divider) {
                const divider = document.createElement('div');
                divider.className = 'context-menu-divider';
                menu.appendChild(divider);
            } else {
                const menuItem = document.createElement('button');
                menuItem.className = 'context-menu-item' + (item.danger ? ' danger' : '');
                menuItem.textContent = item.text;
                menuItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    item.action();
                    this.hideContextMenu();
                });
                menu.appendChild(menuItem);
            }
        });
        
        return menu;
    };
    
    /**
     * Показ контекстного меню
     */
    EstimateEditor.prototype.showContextMenu = function(menu, button) {
        // Удаляем предыдущее меню
        this.hideContextMenu();
        
        document.body.appendChild(menu);
        
        // Позиционируем меню
        const rect = button.getBoundingClientRect();
        menu.style.left = rect.right + 'px';
        menu.style.top = rect.top + 'px';
        
        // Проверяем, не выходит ли меню за границы экрана
        setTimeout(() => {
            const menuRect = menu.getBoundingClientRect();
            if (menuRect.right > window.innerWidth) {
                menu.style.left = (rect.left - menuRect.width) + 'px';
            }
            if (menuRect.bottom > window.innerHeight) {
                menu.style.top = (window.innerHeight - menuRect.height - 10) + 'px';
            }
        }, 0);
        
        // Закрытие по клику вне меню
        setTimeout(() => {
            document.addEventListener('click', this.hideContextMenu.bind(this), { once: true });
        }, 100);
    };
    
    /**
     * Скрытие контекстного меню
     */
    EstimateEditor.prototype.hideContextMenu = function() {
        const menu = document.querySelector('.context-menu');
        if (menu) {
            menu.remove();
        }
    };
    
    /**
     * Удаление раздела
     */
    EstimateEditor.prototype.deleteSection = function(sectionIndex) {
        if (confirm('Удалить раздел и все его элементы?')) {
            this.data.sections.splice(sectionIndex, 1);
            this.buildTableBody();
            this.updateTotals();
            this.hasChanges = true;
            this.updateStatusIndicator('Изменено');
            this.showNotification('Раздел удален', 'success');
        }
    };
    
    /**
     * Удаление элемента
     */
    EstimateEditor.prototype.deleteItem = function(sectionIndex, itemIndex) {
        if (confirm('Удалить элемент?')) {
            this.data.sections[sectionIndex].items.splice(itemIndex, 1);
            this.buildTableBody();
            this.updateTotals();
            this.hasChanges = true;
            this.updateStatusIndicator('Изменено');
            this.showNotification('Элемент удален', 'success');
        }
    };
    
    /**
     * Экспорт в Excel
     */
    EstimateEditor.prototype.exportExcel = function() {
        window.open(`${this.apiUrl}/${this.estimateId}/export-excel`, '_blank');
    };
    
    /**
     * Экспорт в PDF
     */
    EstimateEditor.prototype.exportPdf = function() {
        window.open(`${this.apiUrl}/${this.estimateId}/export-pdf`, '_blank');
    };
    
    /**
     * Показ настроек
     */
    EstimateEditor.prototype.showSettings = function() {
        const modal = this.createSettingsModal();
        document.body.appendChild(modal);
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    };
    
    /**
     * Создание модального окна настроек
     */
    EstimateEditor.prototype.createSettingsModal = function() {
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
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enableAutoSave" 
                                       ${this.settings.enableAutoSave ? 'checked' : ''}>
                                <label class="form-check-label" for="enableAutoSave">
                                    Автосохранение
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enableFormulas" 
                                       ${this.settings.enableFormulas ? 'checked' : ''}>
                                <label class="form-check-label" for="enableFormulas">
                                    Автоматический расчет формул
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showLineNumbers" 
                                       ${this.settings.showLineNumbers ? 'checked' : ''}>
                                <label class="form-check-label" for="showLineNumbers">
                                    Показывать номера строк
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Интервал автосохранения (секунды):</label>
                            <input type="number" class="form-control" id="autoSaveInterval" 
                                   value="${this.autoSaveInterval / 1000}" min="10" max="300">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Знаков после запятой:</label>
                            <input type="number" class="form-control" id="decimalPlaces" 
                                   value="${this.settings.decimalPlaces}" min="0" max="4">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" id="saveSettingsBtn">Сохранить</button>
                    </div>
                </div>
            </div>
        `;
        
        // Обработчик сохранения настроек
        modal.querySelector('#saveSettingsBtn').addEventListener('click', () => {
            this.saveSettings(modal);
        });
        
        return modal;
    };
    
    /**
     * Сохранение настроек
     */
    EstimateEditor.prototype.saveSettings = function(modal) {
        this.settings.enableAutoSave = modal.querySelector('#enableAutoSave').checked;
        this.settings.enableFormulas = modal.querySelector('#enableFormulas').checked;
        this.settings.showLineNumbers = modal.querySelector('#showLineNumbers').checked;
        this.settings.decimalPlaces = parseInt(modal.querySelector('#decimalPlaces').value) || 2;
        
        const newInterval = parseInt(modal.querySelector('#autoSaveInterval').value) * 1000;
        if (newInterval !== this.autoSaveInterval) {
            this.autoSaveInterval = newInterval;
            this.setupAutoSave();
        }
        
        // Сохраняем настройки в localStorage
        localStorage.setItem('estimateEditorSettings', JSON.stringify(this.settings));
        
        bootstrap.Modal.getInstance(modal).hide();
        this.showNotification('Настройки сохранены', 'success');
        
        // Перестраиваем интерфейс при необходимости
        this.buildTable();
    };
    
    /**
     * Загрузка настроек из localStorage
     */
    EstimateEditor.prototype.loadSettings = function() {
        const saved = localStorage.getItem('estimateEditorSettings');
        if (saved) {
            try {
                const settings = JSON.parse(saved);
                this.settings = { ...this.settings, ...settings };
            } catch (error) {
                this.log('Ошибка загрузки настроек:', error);
            }
        }
    };
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Загружаем настройки
    if (window.estimateEditor) {
        window.estimateEditor.loadSettings();
    }
});
