/**
 * Дополнительные патчи для EstimateEditor
 * 
 * Этот файл содержит дополнительные патчи и исправления для предотвращения ошибок
 * и добавления недостающей функциональности в класс EstimateEditor
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🛠️ Инициализация дополнительных патчей для EstimateEditor...');
    
    // Ждем, пока загрузится класс EstimateEditor
    function checkForEstimateEditor() {
        if (typeof EstimateEditor !== 'undefined') {
            applyPatches();
        } else {
            setTimeout(checkForEstimateEditor, 100);
        }
    }
    
    // Применяем патчи к классу EstimateEditor
    function applyPatches() {
        console.log('🔧 Применение патчей к методам EstimateEditor');
        
        // Патч для метода init
        const originalInit = EstimateEditor.prototype.init;
        
        EstimateEditor.prototype.init = function() {
            console.log('🔄 Вызов патча метода init');
            
            console.log('📊 Начальное состояние редактора:', {
                data: this.data ? 'Инициализирован' : 'Отсутствует',
                options: this.options ? 'Инициализирован' : 'Отсутствует',
                containerId: this.containerId
            });
            
            // Инициализируем this.data если оно отсутствует
            if (!this.data) {
                console.warn('⚠️ this.data не инициализирован, создаем структуру');
                this.data = {
                    sheets: [{ data: [] }],
                    currentSheet: 0,
                    sections: [],
                    totals: {}
                };
            }
            
            // Инициализируем this.options если оно отсутствует
            if (!this.options) {
                console.warn('⚠️ this.options не инициализирован, создаем пустой объект');
                this.options = {};
            }
            
            // Вызываем оригинальный метод
            const result = originalInit ? originalInit.call(this) : Promise.resolve();
            
            console.log('✅ Оригинальный init выполнен успешно, результат:', result);
            
            return result;
        };
        
        console.log('✅ Патч для метода init успешно применен');
        
        // Добавляем метод validateCell, который отсутствует
        if (!EstimateEditor.prototype.validateCell) {
            EstimateEditor.prototype.validateCell = function(rowIndex, columnIndex, value) {
                console.log('🔄 Вызов патча метода validateCell');
                
                // Базовая валидация - проверка, что значение не undefined и не null
                if (value === undefined || value === null) {
                    return false;
                }
                
                // Дополнительная валидация в зависимости от типа колонки
                if (typeof value === 'string') {
                    return value.trim().length > 0;
                }
                
                if (typeof value === 'number') {
                    return !isNaN(value) && isFinite(value);
                }
                
                return true;
            };
            
            console.log('✅ Добавлен метод validateCell');
        }
        
        // Исправление метода calculateFormula для устранения ошибок с client_price
        const originalCalculateFormula = EstimateEditor.prototype.calculateFormula;
        
        if (originalCalculateFormula) {
            EstimateEditor.prototype.calculateFormula = function(formula, row, rowIndex) {
                try {
                    // Проверка входных данных
                    if (!formula || typeof formula !== 'string') {
                        return 0;
                    }
                    
                    if (!row || typeof row !== 'object') {
                        console.warn('⚠️ Некорректные данные строки в calculateFormula');
                        return 0;
                    }
                    
                    // Вызываем оригинальный метод с дополнительной защитой
                    return originalCalculateFormula.call(this, formula, row, rowIndex) || 0;
                    
                } catch (error) {
                    console.error('❌ Ошибка в calculateFormula:', error);
                    return 0;
                }
            };
            
            console.log('✅ Патч для метода calculateFormula успешно применен');
        }
        
        // Добавляем метод calculateTotals с расширенной проверкой данных
        if (!EstimateEditor.prototype.calculateTotals) {
            EstimateEditor.prototype.calculateTotals = function() {
                console.log('📊 Расчет итогов сметы');
                
                try {
                    // Проверка, что данные инициализированы
                    if (!this.data || !this.data.sheets) {
                        console.warn('⚠️ Данные не инициализированы в calculateTotals');
                        return { work: 0, materials: 0, total: 0, clientWork: 0, clientMaterials: 0, clientTotal: 0 };
                    }
                    
                    const currentSheet = this.data.currentSheet || 0;
                    const sheetData = this.data.sheets[currentSheet]?.data || [];
                    
                    let workTotal = 0;
                    let materialsTotal = 0;
                    let clientWorkTotal = 0;
                    let clientMaterialsTotal = 0;
                    
                    // Перебираем все строки данных
                    for (const row of sheetData) {
                        if (!row || typeof row !== 'object') continue;
                        
                        try {
                            // Парсим значения цены клиента
                            let clientPrice = 0;
                            if (row.client_price !== undefined) {
                                clientPrice = parseFloat(row.client_price);
                                if (isNaN(clientPrice)) clientPrice = 0;
                            } else {
                                // Если client_price отсутствует, рассчитываем его
                                const price = parseFloat(row.price) || 0;
                                const markup = parseFloat(row.markup) || 0;
                                const discount = parseFloat(row.discount) || 0;
                                clientPrice = price * (1 + markup/100) * (1 - discount/100);
                            }
                            
                            // Парсим количество
                            const quantity = parseFloat(row.quantity) || 0;
                            
                            // Рассчитываем стоимость клиента
                            let clientCost = 0;
                            if (row.client_cost !== undefined) {
                                clientCost = parseFloat(row.client_cost);
                                if (isNaN(clientCost)) clientCost = 0;
                            } else {
                                clientCost = quantity * clientPrice;
                            }
                            
                            // Распределяем по категориям в зависимости от названия
                            let isMaterial = false;
                            if (row.name && typeof row.name === 'string') {
                                const name = row.name.toLowerCase();
                                isMaterial = name.includes('материал') || 
                                           name.includes('краска') || 
                                           name.includes('плитка') ||
                                           name.includes('обои');
                            }
                            
                            if (isMaterial) {
                                materialsTotal += parseFloat(row.cost) || 0;
                                clientMaterialsTotal += clientCost;
                            } else {
                                workTotal += parseFloat(row.cost) || 0;
                                clientWorkTotal += clientCost;
                            }
                            
                        } catch (rowError) {
                            console.warn('⚠️ Ошибка обработки строки в calculateTotals:', rowError);
                        }
                    }
                    
                    // Рассчитываем общие итоги
                    const total = workTotal + materialsTotal;
                    const clientTotal = clientWorkTotal + clientMaterialsTotal;
                    
                    // Сохраняем итоги в данных
                    if (!this.data.totals) {
                        this.data.totals = {};
                    }
                    
                    this.data.totals = {
                        work_total: workTotal,
                        materials_total: materialsTotal,
                        grand_total: total,
                        client_work_total: clientWorkTotal,
                        client_materials_total: clientMaterialsTotal,
                        client_grand_total: clientTotal
                    };
                    
                    const result = {
                        work: workTotal,
                        materials: materialsTotal,
                        total: total,
                        clientWork: clientWorkTotal,
                        clientMaterials: clientMaterialsTotal,
                        clientTotal: clientTotal
                    };
                    
                    console.log('✅ Итоги сметы рассчитаны', result);
                    return result;
                    
                } catch (error) {
                    console.error('❌ Общая ошибка в calculateTotals:', error);
                    return { work: 0, materials: 0, total: 0, clientWork: 0, clientMaterials: 0, clientTotal: 0 };
                }
            };
            
            console.log('✅ Добавлен метод calculateTotals с расширенной проверкой данных');
        }
        
        // Добавляем метод saveData для сохранения данных
        if (!EstimateEditor.prototype.saveData) {
            EstimateEditor.prototype.saveData = function() {
                console.log('💾 Сохранение данных сметы...');
                
                try {
                    // Проверка, что данные инициализированы
                    if (!this.data) {
                        console.warn('⚠️ Данные не инициализированы, сохранение невозможно');
                        return Promise.reject(new Error('Данные не инициализированы'));
                    }
                    
                    // Получаем ID сметы из options или из контейнера
                    let estimateId = this.options?.estimateId;
                    if (!estimateId) {
                        const container = document.getElementById(this.containerId);
                        estimateId = container?.dataset?.estimateId;
                    }
                    
                    if (!estimateId) {
                        console.warn('⚠️ ID сметы не найден, сохранение невозможно');
                        return Promise.reject(new Error('ID сметы не найден'));
                    }
                    
                    // Подготавливаем данные для сохранения
                    const saveData = {
                        ...this.data,
                        meta: {
                            ...this.data.meta,
                            updated_at: new Date().toISOString(),
                            estimate_id: parseInt(estimateId)
                        }
                    };
                    
                    // Отправляем AJAX запрос для сохранения
                    return fetch(`/partner/estimates/${estimateId}/save-json-data`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify(saveData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('✅ Данные успешно сохранены:', data);
                        
                        // Показываем уведомление об успешном сохранении
                        if (window.showNotification) {
                            window.showNotification('success', 'Смета успешно сохранена');
                        } else if (window.Toastify) {
                            window.Toastify({
                                text: "Смета успешно сохранена",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#28a745",
                            }).showToast();
                        } else {
                            alert('Смета успешно сохранена');
                        }
                        
                        return data;
                    })
                    .catch(error => {
                        console.error('❌ Ошибка сохранения данных:', error);
                        
                        // Показываем уведомление об ошибке
                        if (window.showNotification) {
                            window.showNotification('error', 'Ошибка сохранения: ' + error.message);
                        } else if (window.Toastify) {
                            window.Toastify({
                                text: "Ошибка сохранения: " + error.message,
                                duration: 5000,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "#dc3545",
                            }).showToast();
                        } else {
                            alert('Ошибка сохранения: ' + error.message);
                        }
                        
                        throw error;
                    });
                    
                } catch (error) {
                    console.error('❌ Общая ошибка в saveData:', error);
                    return Promise.reject(error);
                }
            };
            
            console.log('✅ Добавлен метод saveData');
        }
        
        // Добавляем метод calculateClientPrice
        if (!EstimateEditor.prototype.calculateClientPrice) {
            EstimateEditor.prototype.calculateClientPrice = function(row) {
                const price = parseFloat(row.price) || 0;
                const markup = parseFloat(row.markup) || 0;
                const discount = parseFloat(row.discount) || 0;
                
                return price * (1 + markup/100) * (1 - discount/100);
            };
            
            console.log('✅ Добавлен метод calculateClientPrice');
        }
        
        // Добавляем фиктивный метод applyFormulas для совместимости
        if (!EstimateEditor.prototype.applyFormulas) {
            EstimateEditor.prototype.applyFormulas = function() {
                // Вызываем calculateTotals вместо applyFormulas
                if (typeof this.calculateTotals === 'function') {
                    return this.calculateTotals();
                }
                return {};
            };
            
            console.log('✅ Добавлен фиктивный метод applyFormulas');
        }
        
        // Исправление метода onCellChange для корректной обработки событий
        const originalOnCellChange = EstimateEditor.prototype.onCellChange;
        
        if (originalOnCellChange) {
            EstimateEditor.prototype.onCellChange = function(rowIndex, columnIndex, value) {
                try {
                    // Проверяем, если первый аргумент - это объект события, извлекаем данные
                    if (typeof rowIndex === 'object' && rowIndex !== null) {
                        console.log('🔧 Обнаружен объект события в onCellChange, извлекаем данные');
                        
                        // Попытка извлечь данные из события
                        if (rowIndex.target && rowIndex.target.dataset) {
                            const target = rowIndex.target;
                            const newRowIndex = parseInt(target.dataset.row);
                            const newColumnIndex = parseInt(target.dataset.column);
                            const newValue = target.value || '';
                            
                            if (!isNaN(newRowIndex) && !isNaN(newColumnIndex)) {
                                rowIndex = newRowIndex;
                                columnIndex = newColumnIndex;
                                value = newValue;
                            } else {
                                console.warn('⚠️ Не удалось извлечь корректные индексы из события');
                                return;
                            }
                        } else {
                            console.warn('⚠️ Не удалось извлечь данные из события, пропускаем обработку');
                            return;
                        }
                    }
                    
                    // Проверка, что данные инициализированы
                    if (!this.data || !this.data.sheets) {
                        console.warn('⚠️ Данные не инициализированы в onCellChange');
                        return;
                    }
                    
                    // Вызываем оригинальный метод, перехватывая возможные ошибки
                    try {
                        if (typeof rowIndex === 'number' && typeof columnIndex === 'number') {
                            originalOnCellChange.call(this, rowIndex, columnIndex, value);
                        }
                    } catch (error) {
                        console.error('❌ Ошибка при вызове оригинального метода onCellChange:', error);
                    }
                    
                    // Получаем текущие данные с дополнительными проверками
                    const currentSheet = this.data.currentSheet || 0;
                    
                    // Проверка наличия листа данных
                    if (!this.data.sheets[currentSheet]) {
                        console.warn('⚠️ Лист данных не найден в onCellChange');
                        return;
                    }
                    
                    // Проверка наличия данных листа
                    const sheetData = this.data.sheets[currentSheet].data;
                    if (!sheetData || !Array.isArray(sheetData)) {
                        console.warn('⚠️ Данные листа не инициализированы или не являются массивом в onCellChange');
                        return;
                    }
                    
                    // Проверка индекса строки
                    if (rowIndex < 0 || rowIndex >= sheetData.length) {
                        console.warn(`⚠️ Индекс строки ${rowIndex} вне диапазона в onCellChange`);
                        return;
                    }
                    
                    const row = sheetData[rowIndex];
                    if (!row) {
                        console.warn(`⚠️ Строка ${rowIndex} не найдена в onCellChange`);
                        return;
                    }
                    
                    // Обновляем значение в строке
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
                    
                    const updatedField = fieldMap[columnIndex] || 'unknown';
                    if (updatedField !== 'unknown') {
                        row[updatedField] = value;
                    }
                    
                    // Если изменилась цена, количество, наценка или скидка - пересчитываем цену и стоимость для клиента
                    if (['price', 'quantity', 'markup', 'discount'].includes(updatedField)) {
                        // Рассчитываем цену для клиента
                        const price = parseFloat(row.price) || 0;
                        const markup = parseFloat(row.markup) || 0;
                        const discount = parseFloat(row.discount) || 0;
                        const quantity = parseFloat(row.quantity) || 0;
                        
                        row.client_price = price * (1 + markup/100) * (1 - discount/100);
                        row.client_cost = quantity * row.client_price;
                        
                        console.log('🔄 Пересчитаны client_price и client_cost для строки', rowIndex);
                        
                        // Пересчитываем итоги
                        if (typeof this.calculateTotals === 'function') {
                            this.calculateTotals();
                        }
                        
                        // Перерисовываем строку, если метод доступен
                        if (this.ui && typeof this.ui.updateRow === 'function') {
                            this.ui.updateRow(rowIndex);
                        }
                    }
                    
                    // Вызываем автосохранение после изменения
                    if (typeof this.saveData === 'function') {
                        setTimeout(() => {
                            this.saveData();
                        }, 1000); // Сохраняем через 1 секунду после изменения
                    }
                    
                } catch (error) {
                    console.error('❌ Общая ошибка в патче onCellChange:', error);
                }
            };
            
            console.log('✅ Патч для метода onCellChange успешно применен');
        }
        
        // Патч для метода loadEstimateData
        const originalLoadEstimateData = EstimateEditor.prototype.loadEstimateData;
        
        if (originalLoadEstimateData) {
            EstimateEditor.prototype.loadEstimateData = async function() {
                console.log('🔄 Запуск патча для метода loadEstimateData');
                
                console.log('ℹ️ Данные перед загрузкой:', {
                    hasSheets: this.data?.sheets ? true : false,
                    sheetsCount: this.data?.sheets?.length || 0,
                    currentSheet: this.data?.currentSheet
                });
                
                // Инициализируем данные, если они отсутствуют
                if (!this.data) {
                    this.data = {
                        sheets: [{ data: [] }],
                        currentSheet: 0,
                        sections: [],
                        totals: {}
                    };
                }
                
                console.log('🔄 Вызов оригинального loadEstimateData...');
                try {
                    const result = await originalLoadEstimateData.call(this);
                    console.log('✅ Оригинальный loadEstimateData выполнен успешно');
                    
                    // Проверка и исправление структуры данных после загрузки
                    if (!this.data.sheets || !Array.isArray(this.data.sheets)) {
                        console.warn('⚠️ this.data.sheets отсутствует или пуст, исправляем');
                        this.data.sheets = [{ data: [] }];
                    }
                    
                    // Специальная обработка данных из шаблонов
                    if (this.data && this.data.sheets && this.data.sheets[0] && this.data.sheets[0].data) {
                        const sheetData = this.data.sheets[0].data;
                        console.log('📊 Обработка данных шаблона, строк:', sheetData.length);
                        
                        // Проверяем и дополняем данные строк
                        for (let i = 0; i < sheetData.length; i++) {
                            const row = sheetData[i];
                            if (row && typeof row === 'object' && !row._type) {
                                // Добавляем недостающие поля, если их нет
                                if (!row.hasOwnProperty('number')) row.number = i + 1;
                                if (!row.hasOwnProperty('cost')) {
                                    row.cost = (parseFloat(row.quantity) || 0) * (parseFloat(row.price) || 0);
                                }
                                if (!row.hasOwnProperty('client_price')) {
                                    const price = parseFloat(row.price) || 0;
                                    const markup = parseFloat(row.markup) || 0;
                                    const discount = parseFloat(row.discount) || 0;
                                    row.client_price = price * (1 + markup/100) * (1 - discount/100);
                                }
                                if (!row.hasOwnProperty('client_cost')) {
                                    row.client_cost = (parseFloat(row.quantity) || 0) * (parseFloat(row.client_price) || 0);
                                }
                            }
                        }
                    }
                    
                    return result;
                } catch (error) {
                    console.error('❌ Ошибка в оригинальном loadEstimateData:', error);
                    
                    // Если произошла ошибка, создаем минимальную структуру данных
                    if (!this.data.sheets) {
                        this.data.sheets = [{ data: [] }];
                    }
                }
                
                if (typeof this.data.currentSheet !== 'number' || this.data.currentSheet < 0) {
                    console.warn('⚠️ Некорректный currentSheet, устанавливаем в 0');
                    this.data.currentSheet = 0;
                }
                
                // Убеждаемся, что у текущего листа есть данные
                const currentSheet = this.data.currentSheet;
                if (!this.data.sheets[currentSheet]) {
                    this.data.sheets[currentSheet] = { data: [] };
                }
                
                if (!this.data.sheets[currentSheet].data) {
                    this.data.sheets[currentSheet].data = [];
                }
                
                console.log('ℹ️ Состояние данных после проверки структуры:', 
                    'листов:', this.data.sheets.length, 
                    'строк в текущем листе:', this.data.sheets[currentSheet].data.length
                );
                
                // Пересчитываем client_price и client_cost для всех строк
                console.log('🔄 Пересчет client_price и client_cost для всех строк после загрузки данных');
                
                const sheetData = this.data.sheets[currentSheet].data;
                let calculatedRows = 0;
                
                for (let i = 0; i < sheetData.length; i++) {
                    const row = sheetData[i];
                    if (row && typeof row === 'object') {
                        // Простой расчет
                        const price = parseFloat(row.price) || 0;
                        const markup = parseFloat(row.markup) || 0;
                        const discount = parseFloat(row.discount) || 0;
                        const quantity = parseFloat(row.quantity) || 0;
                        
                        row.client_price = price * (1 + markup/100) * (1 - discount/100);
                        row.client_cost = quantity * row.client_price;
                        calculatedRows++;
                    }
                }
                
                console.log(`ℹ️ Пересчитаны данные для ${calculatedRows} строк`);
                
                // Пересчитываем итоги
                if (typeof this.calculateTotals === 'function') {
                    this.calculateTotals();
                    console.log('✅ Итоги успешно пересчитаны');
                }
                
                return result;
            };
            
            console.log('✅ Патч для метода loadEstimateData успешно применен');
        }
        
        console.log('✅ Все дополнительные патчи для EstimateEditor успешно применены');
    }
    
    // Запускаем проверку
    checkForEstimateEditor();
});
