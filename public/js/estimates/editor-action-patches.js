/**
 * Патчи для недостающих методов редактирования в EstimateEditor
 * 
 * Этот файл добавляет методы для редактирования, перемещения и дублирования элементов,
 * которые отсутствуют в основной реализации EstimateEditor
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Инициализация патчей для методов редактирования EstimateEditor...');
    
    if (typeof EstimateEditor === 'undefined') {
        console.error('❌ Класс EstimateEditor не найден');
        return;
    }
    
    // Патчи для работы с разделами
    if (!EstimateEditor.prototype.moveSectionUp) {
        EstimateEditor.prototype.moveSectionUp = function(sectionIndex) {
            console.log('🔄 Перемещение раздела вверх:', sectionIndex);
            
            try {
                if (!this.data || !this.data.sections || !Array.isArray(this.data.sections)) {
                    console.warn('⚠️ Разделы не найдены');
                    return;
                }
                
                if (sectionIndex <= 0 || sectionIndex >= this.data.sections.length) {
                    console.warn('⚠️ Некорректный индекс раздела:', sectionIndex);
                    return;
                }
                
                // Меняем местами с предыдущим разделом
                const temp = this.data.sections[sectionIndex];
                this.data.sections[sectionIndex] = this.data.sections[sectionIndex - 1];
                this.data.sections[sectionIndex - 1] = temp;
                
                // Перестраиваем таблицу
                if (typeof this.buildTableBody === 'function') {
                    this.buildTableBody();
                } else if (typeof this.buildTable === 'function') {
                    this.buildTable();
                }
                
                // Обновляем итоги
                if (typeof this.updateTotals === 'function') {
                    this.updateTotals();
                }
                
                // Обновляем статус
                this.hasChanges = true;
                if (typeof this.updateStatusIndicator === 'function') {
                    this.updateStatusIndicator('Изменено');
                }
                
                // Показываем уведомление
                if (typeof this.showNotification === 'function') {
                    this.showNotification('Раздел перемещен вверх', 'success');
                }
            } catch (error) {
                console.error('❌ Ошибка при перемещении раздела вверх:', error);
            }
        };
        
        console.log('✅ Добавлен метод moveSectionUp');
    }
    
    if (!EstimateEditor.prototype.moveSectionDown) {
        EstimateEditor.prototype.moveSectionDown = function(sectionIndex) {
            console.log('🔄 Перемещение раздела вниз:', sectionIndex);
            
            try {
                if (!this.data || !this.data.sections || !Array.isArray(this.data.sections)) {
                    console.warn('⚠️ Разделы не найдены');
                    return;
                }
                
                if (sectionIndex < 0 || sectionIndex >= this.data.sections.length - 1) {
                    console.warn('⚠️ Некорректный индекс раздела:', sectionIndex);
                    return;
                }
                
                // Меняем местами со следующим разделом
                const temp = this.data.sections[sectionIndex];
                this.data.sections[sectionIndex] = this.data.sections[sectionIndex + 1];
                this.data.sections[sectionIndex + 1] = temp;
                
                // Перестраиваем таблицу
                if (typeof this.buildTableBody === 'function') {
                    this.buildTableBody();
                } else if (typeof this.buildTable === 'function') {
                    this.buildTable();
                }
                
                // Обновляем итоги
                if (typeof this.updateTotals === 'function') {
                    this.updateTotals();
                }
                
                // Обновляем статус
                this.hasChanges = true;
                if (typeof this.updateStatusIndicator === 'function') {
                    this.updateStatusIndicator('Изменено');
                }
                
                // Показываем уведомление
                if (typeof this.showNotification === 'function') {
                    this.showNotification('Раздел перемещен вниз', 'success');
                }
            } catch (error) {
                console.error('❌ Ошибка при перемещении раздела вниз:', error);
            }
        };
        
        console.log('✅ Добавлен метод moveSectionDown');
    }
    
    if (!EstimateEditor.prototype.editSection) {
        EstimateEditor.prototype.editSection = function(sectionIndex) {
            console.log('✏️ Редактирование раздела:', sectionIndex);
            
            try {
                if (!this.data || !this.data.sections || !Array.isArray(this.data.sections)) {
                    console.warn('⚠️ Разделы не найдены');
                    return;
                }
                
                if (sectionIndex < 0 || sectionIndex >= this.data.sections.length) {
                    console.warn('⚠️ Некорректный индекс раздела:', sectionIndex);
                    return;
                }
                
                const section = this.data.sections[sectionIndex];
                const title = prompt('Введите новое название раздела', section.title);
                
                if (title !== null && title.trim() !== '') {
                    section.title = title.trim();
                    
                    // Перестраиваем таблицу
                    if (typeof this.buildTableBody === 'function') {
                        this.buildTableBody();
                    } else if (typeof this.buildTable === 'function') {
                        this.buildTable();
                    }
                    
                    // Обновляем статус
                    this.hasChanges = true;
                    if (typeof this.updateStatusIndicator === 'function') {
                        this.updateStatusIndicator('Изменено');
                    }
                    
                    // Показываем уведомление
                    if (typeof this.showNotification === 'function') {
                        this.showNotification('Раздел обновлен', 'success');
                    }
                }
            } catch (error) {
                console.error('❌ Ошибка при редактировании раздела:', error);
            }
        };
        
        console.log('✅ Добавлен метод editSection');
    }
    
    // Патчи для работы с элементами
    if (!EstimateEditor.prototype.editItem) {
        EstimateEditor.prototype.editItem = function(sectionIndex, itemIndex) {
            console.log('✏️ Редактирование элемента:', sectionIndex, itemIndex);
            
            try {
                if (!this.data || !this.data.sections || !Array.isArray(this.data.sections)) {
                    console.warn('⚠️ Разделы не найдены');
                    return;
                }
                
                if (sectionIndex < 0 || sectionIndex >= this.data.sections.length) {
                    console.warn('⚠️ Некорректный индекс раздела:', sectionIndex);
                    return;
                }
                
                const section = this.data.sections[sectionIndex];
                
                if (!section.items || !Array.isArray(section.items)) {
                    console.warn('⚠️ Элементы не найдены в разделе:', sectionIndex);
                    return;
                }
                
                if (itemIndex < 0 || itemIndex >= section.items.length) {
                    console.warn('⚠️ Некорректный индекс элемента:', itemIndex);
                    return;
                }
                
                const item = section.items[itemIndex];
                
                // Создаем модальное окно для редактирования
                const modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Редактирование элемента</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label">Наименование:</label>
                                            <input type="text" class="form-control" id="editItemNameInput" 
                                                   value="${item.name || ''}" placeholder="Введите наименование">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Единица измерения:</label>
                                            <input type="text" class="form-control" id="editItemUnitInput" 
                                                   value="${item.unit || 'шт'}" placeholder="ед.изм.">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Количество:</label>
                                            <input type="number" class="form-control" id="editItemQuantityInput" 
                                                   value="${item.quantity || 0}" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Цена:</label>
                                            <input type="number" class="form-control" id="editItemPriceInput" 
                                                   value="${item.price || 0}" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Наценка %:</label>
                                            <input type="number" class="form-control" id="editItemMarkupInput" 
                                                   value="${item.markup || 20}" min="0" max="100">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Скидка %:</label>
                                            <input type="number" class="form-control" id="editItemDiscountInput" 
                                                   value="${item.discount || 0}" min="0" max="100">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="editIsHeaderCheck" ${item.is_header ? 'checked' : ''}>
                                        <label class="form-check-label" for="editIsHeaderCheck">
                                            Заголовок группы (не участвует в расчетах)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="button" class="btn btn-primary" id="saveEditItemBtn">Сохранить</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Показываем модальное окно
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
                
                // Обработчик сохранения изменений
                modal.querySelector('#saveEditItemBtn').addEventListener('click', () => {
                    // Получаем значения полей
                    const name = modal.querySelector('#editItemNameInput').value.trim();
                    const unit = modal.querySelector('#editItemUnitInput').value.trim() || 'шт';
                    const quantity = parseFloat(modal.querySelector('#editItemQuantityInput').value) || 0;
                    const price = parseFloat(modal.querySelector('#editItemPriceInput').value) || 0;
                    const markup = parseFloat(modal.querySelector('#editItemMarkupInput').value) || 20;
                    const discount = parseFloat(modal.querySelector('#editItemDiscountInput').value) || 0;
                    const isHeader = modal.querySelector('#editIsHeaderCheck').checked;
                    
                    // Проверяем наименование
                    if (!name) {
                        if (typeof this.showNotification === 'function') {
                            this.showNotification('Введите наименование', 'warning');
                        }
                        return;
                    }
                    
                    // Обновляем элемент
                    item.name = name;
                    item.unit = unit;
                    item.quantity = quantity;
                    item.price = price;
                    item.markup = markup;
                    item.discount = discount;
                    item.is_header = isHeader;
                    
                    // Перестраиваем таблицу
                    if (typeof this.buildTableBody === 'function') {
                        this.buildTableBody();
                    } else if (typeof this.buildTable === 'function') {
                        this.buildTable();
                    }
                    
                    // Обновляем итоги
                    if (typeof this.updateTotals === 'function') {
                        this.updateTotals();
                    }
                    
                    // Обновляем статус
                    this.hasChanges = true;
                    if (typeof this.updateStatusIndicator === 'function') {
                        this.updateStatusIndicator('Изменено');
                    }
                    
                    // Закрываем модальное окно
                    bootstrapModal.hide();
                    
                    // Показываем уведомление
                    if (typeof this.showNotification === 'function') {
                        this.showNotification('Элемент обновлен', 'success');
                    }
                });
                
                // Удаляем модальное окно после закрытия
                modal.addEventListener('hidden.bs.modal', () => {
                    modal.remove();
                });
            } catch (error) {
                console.error('❌ Ошибка при редактировании элемента:', error);
            }
        };
        
        console.log('✅ Добавлен метод editItem');
    }
    
    if (!EstimateEditor.prototype.duplicateItem) {
        EstimateEditor.prototype.duplicateItem = function(sectionIndex, itemIndex) {
            console.log('🔄 Дублирование элемента:', sectionIndex, itemIndex);
            
            try {
                if (!this.data || !this.data.sections || !Array.isArray(this.data.sections)) {
                    console.warn('⚠️ Разделы не найдены');
                    return;
                }
                
                if (sectionIndex < 0 || sectionIndex >= this.data.sections.length) {
                    console.warn('⚠️ Некорректный индекс раздела:', sectionIndex);
                    return;
                }
                
                const section = this.data.sections[sectionIndex];
                
                if (!section.items || !Array.isArray(section.items)) {
                    console.warn('⚠️ Элементы не найдены в разделе:', sectionIndex);
                    return;
                }
                
                if (itemIndex < 0 || itemIndex >= section.items.length) {
                    console.warn('⚠️ Некорректный индекс элемента:', itemIndex);
                    return;
                }
                
                // Делаем глубокую копию элемента
                let newItem;
                try {
                    newItem = JSON.parse(JSON.stringify(section.items[itemIndex]));
                } catch (jsonError) {
                    console.warn('⚠️ Ошибка при клонировании элемента, делаем простую копию');
                    
                    const item = section.items[itemIndex];
                    newItem = {};
                    for (const key in item) {
                        if (Object.prototype.hasOwnProperty.call(item, key)) {
                            newItem[key] = item[key];
                        }
                    }
                }
                
                // Обновляем название для копии
                newItem.name = newItem.name ? `Копия: ${newItem.name}` : 'Копия элемента';
                
                // Добавляем дублированный элемент после оригинала
                section.items.splice(itemIndex + 1, 0, newItem);
                
                // Перестраиваем таблицу
                if (typeof this.buildTableBody === 'function') {
                    this.buildTableBody();
                } else if (typeof this.buildTable === 'function') {
                    this.buildTable();
                }
                
                // Обновляем итоги
                if (typeof this.updateTotals === 'function') {
                    this.updateTotals();
                }
                
                // Обновляем статус
                this.hasChanges = true;
                if (typeof this.updateStatusIndicator === 'function') {
                    this.updateStatusIndicator('Изменено');
                }
                
                // Показываем уведомление
                if (typeof this.showNotification === 'function') {
                    this.showNotification('Элемент дублирован', 'success');
                }
            } catch (error) {
                console.error('❌ Ошибка при дублировании элемента:', error);
            }
        };
        
        console.log('✅ Добавлен метод duplicateItem');
    }
    
    if (!EstimateEditor.prototype.moveItemUp) {
        EstimateEditor.prototype.moveItemUp = function(sectionIndex, itemIndex) {
            console.log('🔄 Перемещение элемента вверх:', sectionIndex, itemIndex);
            
            try {
                if (!this.data || !this.data.sections || !Array.isArray(this.data.sections)) {
                    console.warn('⚠️ Разделы не найдены');
                    return;
                }
                
                if (sectionIndex < 0 || sectionIndex >= this.data.sections.length) {
                    console.warn('⚠️ Некорректный индекс раздела:', sectionIndex);
                    return;
                }
                
                const section = this.data.sections[sectionIndex];
                
                if (!section.items || !Array.isArray(section.items)) {
                    console.warn('⚠️ Элементы не найдены в разделе:', sectionIndex);
                    return;
                }
                
                if (itemIndex <= 0 || itemIndex >= section.items.length) {
                    console.warn('⚠️ Некорректный индекс элемента:', itemIndex);
                    return;
                }
                
                // Меняем местами с предыдущим элементом
                const temp = section.items[itemIndex];
                section.items[itemIndex] = section.items[itemIndex - 1];
                section.items[itemIndex - 1] = temp;
                
                // Перестраиваем таблицу
                if (typeof this.buildTableBody === 'function') {
                    this.buildTableBody();
                } else if (typeof this.buildTable === 'function') {
                    this.buildTable();
                }
                
                // Обновляем итоги
                if (typeof this.updateTotals === 'function') {
                    this.updateTotals();
                }
                
                // Обновляем статус
                this.hasChanges = true;
                if (typeof this.updateStatusIndicator === 'function') {
                    this.updateStatusIndicator('Изменено');
                }
            } catch (error) {
                console.error('❌ Ошибка при перемещении элемента вверх:', error);
            }
        };
        
        console.log('✅ Добавлен метод moveItemUp');
    }
    
    if (!EstimateEditor.prototype.moveItemDown) {
        EstimateEditor.prototype.moveItemDown = function(sectionIndex, itemIndex) {
            console.log('🔄 Перемещение элемента вниз:', sectionIndex, itemIndex);
            
            try {
                if (!this.data || !this.data.sections || !Array.isArray(this.data.sections)) {
                    console.warn('⚠️ Разделы не найдены');
                    return;
                }
                
                if (sectionIndex < 0 || sectionIndex >= this.data.sections.length) {
                    console.warn('⚠️ Некорректный индекс раздела:', sectionIndex);
                    return;
                }
                
                const section = this.data.sections[sectionIndex];
                
                if (!section.items || !Array.isArray(section.items)) {
                    console.warn('⚠️ Элементы не найдены в разделе:', sectionIndex);
                    return;
                }
                
                if (itemIndex < 0 || itemIndex >= section.items.length - 1) {
                    console.warn('⚠️ Некорректный индекс элемента:', itemIndex);
                    return;
                }
                
                // Меняем местами со следующим элементом
                const temp = section.items[itemIndex];
                section.items[itemIndex] = section.items[itemIndex + 1];
                section.items[itemIndex + 1] = temp;
                
                // Перестраиваем таблицу
                if (typeof this.buildTableBody === 'function') {
                    this.buildTableBody();
                } else if (typeof this.buildTable === 'function') {
                    this.buildTable();
                }
                
                // Обновляем итоги
                if (typeof this.updateTotals === 'function') {
                    this.updateTotals();
                }
                
                // Обновляем статус
                this.hasChanges = true;
                if (typeof this.updateStatusIndicator === 'function') {
                    this.updateStatusIndicator('Изменено');
                }
            } catch (error) {
                console.error('❌ Ошибка при перемещении элемента вниз:', error);
            }
        };
        
        console.log('✅ Добавлен метод moveItemDown');
    }
    
    if (!EstimateEditor.prototype.addRowToSection) {
        EstimateEditor.prototype.addRowToSection = function(sectionIndex) {
            console.log('➕ Добавление строки в раздел:', sectionIndex);
            
            try {
                if (!this.data || !this.data.sections || !Array.isArray(this.data.sections)) {
                    console.warn('⚠️ Разделы не найдены');
                    return;
                }
                
                if (sectionIndex < 0 || sectionIndex >= this.data.sections.length) {
                    console.warn('⚠️ Некорректный индекс раздела:', sectionIndex);
                    return;
                }
                
                // Создаем модальное окно для добавления строки
                const modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Добавить строку в раздел "${this.data.sections[sectionIndex].title}"</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label">Наименование:</label>
                                            <input type="text" class="form-control" id="newItemNameInput" 
                                                   placeholder="Введите наименование работы/материала">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Единица измерения:</label>
                                            <input type="text" class="form-control" id="newItemUnitInput" 
                                                   value="шт" placeholder="ед.изм.">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Количество:</label>
                                            <input type="number" class="form-control" id="newItemQuantityInput" 
                                                   value="1" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Цена:</label>
                                            <input type="number" class="form-control" id="newItemPriceInput" 
                                                   value="0" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Наценка %:</label>
                                            <input type="number" class="form-control" id="newItemMarkupInput" 
                                                   value="20" min="0" max="100">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Скидка %:</label>
                                            <input type="number" class="form-control" id="newItemDiscountInput" 
                                                   value="0" min="0" max="100">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="newIsHeaderCheck">
                                        <label class="form-check-label" for="newIsHeaderCheck">
                                            Заголовок группы (не участвует в расчетах)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="button" class="btn btn-primary" id="addNewItemBtn">Добавить</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Показываем модальное окно
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
                
                // Обработчик добавления строки
                modal.querySelector('#addNewItemBtn').addEventListener('click', () => {
                    // Получаем значения полей
                    const name = modal.querySelector('#newItemNameInput').value.trim();
                    const unit = modal.querySelector('#newItemUnitInput').value.trim() || 'шт';
                    const quantity = parseFloat(modal.querySelector('#newItemQuantityInput').value) || 0;
                    const price = parseFloat(modal.querySelector('#newItemPriceInput').value) || 0;
                    const markup = parseFloat(modal.querySelector('#newItemMarkupInput').value) || 20;
                    const discount = parseFloat(modal.querySelector('#newItemDiscountInput').value) || 0;
                    const isHeader = modal.querySelector('#newIsHeaderCheck').checked;
                    
                    // Проверяем наименование
                    if (!name) {
                        if (typeof this.showNotification === 'function') {
                            this.showNotification('Введите наименование', 'warning');
                        }
                        return;
                    }
                    
                    // Создаем новый элемент
                    const newItem = {
                        name: name,
                        unit: unit,
                        quantity: quantity,
                        price: price,
                        markup: markup,
                        discount: discount,
                        is_header: isHeader
                    };
                    
                    // Добавляем элемент в раздел
                    const section = this.data.sections[sectionIndex];
                    if (!section.items) {
                        section.items = [];
                    }
                    section.items.push(newItem);
                    
                    // Перестраиваем таблицу
                    if (typeof this.buildTableBody === 'function') {
                        this.buildTableBody();
                    } else if (typeof this.buildTable === 'function') {
                        this.buildTable();
                    }
                    
                    // Обновляем итоги
                    if (typeof this.updateTotals === 'function') {
                        this.updateTotals();
                    }
                    
                    // Обновляем статус
                    this.hasChanges = true;
                    if (typeof this.updateStatusIndicator === 'function') {
                        this.updateStatusIndicator('Изменено');
                    }
                    
                    // Закрываем модальное окно
                    bootstrapModal.hide();
                    
                    // Показываем уведомление
                    if (typeof this.showNotification === 'function') {
                        this.showNotification('Строка добавлена', 'success');
                    }
                });
                
                // Удаляем модальное окно после закрытия
                modal.addEventListener('hidden.bs.modal', () => {
                    modal.remove();
                });
            } catch (error) {
                console.error('❌ Ошибка при добавлении строки в раздел:', error);
            }
        };
        
        console.log('✅ Добавлен метод addRowToSection');
    }
    
    console.log('✅ Все патчи для методов редактирования EstimateEditor успешно применены');
});
