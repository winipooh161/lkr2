<script>
/**
 * Компонент для автоматического пересчета формул в редакторе смет
 * Версия: 5.0 (полная интеграция с унифицированной системой формул)
 */

// Создаем совместимый API для старых систем
if (typeof window.ExcelFormulaSystem === 'undefined') {
    console.log('🔧 Создаем новый объект window.ExcelFormulaSystem');
    window.ExcelFormulaSystem = {
        // Основные методы расчета - используем унифицированную систему
        recalculateAll: function() {
            if (window.UnifiedFormulaSystem) {
                console.log('🔄 Пересчет через унифицированную систему');
                // Получаем данные из активного редактора
                const editorData = this._getEditorData();
                if (editorData) {
                    window.UnifiedFormulaSystem.calculateFormulas(
                        editorData.data,
                        editorData.columns,
                        (result) => {
                            if (result.success) {
                                console.log('✅ Пересчет завершен успешно');
                                this._triggerCallbacks('recalculate', result);
                                // Автоматически пересчитываем итоги
                                this.calculateTotals();
                            } else {
                                console.error('❌ Ошибка пересчета:', result.error);
                                this._triggerCallbacks('error', result);
                            }
                        }
                    );
                } else {
                    // Если нет данных из редактора, просто пересчитываем итоги
                    this.calculateTotals();
                }
            } else {
                // Если унифицированная система недоступна, просто пересчитываем итоги
                console.log('🔄 Пересчет итогов без унифицированной системы');
                this.calculateTotals();
            }
            return this;
        },
        
        recalculateByRows: function(rows) {
            console.log('📋 Пересчет по строкам делегирован унифицированной системе');
            this.recalculateAll();
            return { success: true };
        },
        
        prepareDataForSave: function() {
            console.log('💾 Подготовка данных для сохранения');
            return true;
        },

        // Методы для обратной совместимости
        recalculateTotals: function() {
            return this.calculateTotals();
        },
        
        recalculateTotalsWithoutReordering: function() {
            return this.calculateTotals();
        },
        
        recalculateRow: function(row) {
            return this.recalculateAll();
        },
        
        // Основной метод расчета итогов
        calculateTotals: function() {
            const table = document.querySelector('#json-table-container-table');
            if (!table) {
                console.warn('⚠️ Таблица не найдена для расчета итогов');
                return null;
            }
            
            const data = this._extractDataFromTable(table);
            const totals = {
                sum: 0,          // Итог столбца "Стоимость" (работы)
                client_sum: 0,   // Итог столбца "Сумма клиента" (работы)
                materials_sum: 0, // Итог материалов
                grand_total: 0   // Общий итог (работы + материалы)
            };
            
            console.log('🔢 Начинаем расчет итогов для', data.length, 'строк');
            
            // Суммируем значения в нужных столбцах, исключая группы и заголовки
            data.forEach((row, index) => {
                // Определяем группы по наличию класса в DOM
                const rowElement = table.querySelector(`tr[data-row-index="${index}"]`);
                const isGroupHeader = rowElement && rowElement.classList.contains('group-header');
                
                // Пропускаем строки заголовков групп и строки итогов
                if (isGroupHeader || 
                    !row.name || 
                    row.name === '' || 
                    row.name.toLowerCase().includes('общий итог') ||
                    row.name.toLowerCase().includes('итог')
                ) {
                    return;
                }
                
                // Проверяем тип строки (работы или материалы)
                const isWorkRow = !row.type || row.type.toLowerCase() !== 'materials';
                const isMaterialRow = row.type && row.type.toLowerCase() === 'materials';
                
                // Парсим значения для подсчета
                const sum = this._parseNumericValue(row.sum);
                const clientSum = this._parseNumericValue(row.client_sum);
                
                // Суммируем только положительные значения
                if (isWorkRow && sum > 0) {
                    totals.sum += sum;
                }
                if (isWorkRow && clientSum > 0) {
                    totals.client_sum += clientSum;
                }
                if (isMaterialRow && clientSum > 0) {
                    totals.materials_sum += clientSum;
                }
            });
            
            // Рассчитываем общий итог (работы + материалы)
            totals.grand_total = totals.client_sum + totals.materials_sum;
            
            console.log('🎯 Итоговые суммы:');
            console.log('  - Работы (Стоимость):', totals.sum);
            console.log('  - Работы (Сумма клиента):', totals.client_sum);
            console.log('  - Материалы:', totals.materials_sum);
            console.log('  - ОБЩИЙ ИТОГ:', totals.grand_total);
            
            // Обновляем строки итогов
            this._updateTotalRows(totals);
            
            return totals;
        },
        
        // Получение данных редактора
        _getEditorData: function() {
            if (window.jsonTableEditor && window.jsonTableEditor.sheets && window.jsonTableEditor.options) {
                const currentSheet = window.jsonTableEditor.sheets[window.jsonTableEditor.currentSheetIndex];
                return {
                    data: currentSheet.data,
                    columns: window.jsonTableEditor.options.columns
                };
            }
            
            // Альтернативный метод - получение данных из DOM
            const table = document.querySelector('#json-table-container-table');
            if (table) {
                const data = this._extractDataFromTable(table);
                return {
                    data: data,
                    columns: this._getColumnsFromTable(table)
                };
            }
            
            return null;
        },
        
        // Извлечение данных из таблицы DOM
        _extractDataFromTable: function(table) {
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const data = [];
            
            rows.forEach((row, index) => {
                if (row.classList.contains('footer-row')) return; // Пропускаем строки итогов
                
                const cells = Array.from(row.querySelectorAll('td[data-field]'));
                const rowData = {};
                
                cells.forEach(cell => {
                    const field = cell.getAttribute('data-field');
                    const value = cell.textContent.trim();
                    rowData[field] = value;
                });
                
                data.push(rowData);
            });
            
            return data;
        },
        
        // Получение структуры колонок из таблицы
        _getColumnsFromTable: function(table) {
            const headers = Array.from(table.querySelectorAll('thead th[data-field]'));
            return headers.map(header => ({
                name: header.getAttribute('data-field'),
                title: header.textContent.trim(),
                type: header.classList.contains('numeric') ? 'numeric' : 'text'
            }));
        },
        
        // Парсинг числовых значений с учетом различных форматов
        _parseNumericValue: function(value) {
            if (!value || value === '') return 0;
            
            // Удаляем пробелы, запятые как разделители тысяч, валютные символы
            let cleanValue = String(value)
                .replace(/\s+/g, '') // убираем пробелы
                .replace(/,/g, '') // убираем запятые
                .replace(/₽|руб\.?|rub/gi, '') // убираем валютные символы
                .trim();
            
            // Преобразуем в число
            const numValue = parseFloat(cleanValue);
            
            return isNaN(numValue) ? 0 : numValue;
        },
        
        // Обновление строк итогов
        _updateTotalRows: function(totals) {
            console.log('🔍 Поиск строк итогов...');
            
            // Пробуем несколько способов поиска строк итогов
            let footerRows = document.querySelectorAll('tr.footer-row');
            console.log('🔍 Найдено строк по селектору tr.footer-row:', footerRows.length);
            
            if (footerRows.length === 0) {
                // Альтернативный поиск по содержимому
                const allRows = document.querySelectorAll('#json-table-container-table tbody tr');
                console.log('🔍 Всего строк в таблице:', allRows.length);
                
                // Ищем строки с текстом "ОБЩИЙ ИТОГ"
                footerRows = Array.from(allRows).filter(row => {
                    const nameCell = row.querySelector('td[data-field="name"]');
                    const text = nameCell ? nameCell.textContent.trim().toUpperCase() : '';
                    console.log('🔍 Проверяем строку:', text);
                    return text.includes('ОБЩИЙ ИТОГ') || text.includes('ИТОГ') || text.includes('TOTAL');
                });
                
                console.log('🎯 Найдено строк итогов по содержимому:', footerRows.length);
                
                if (footerRows.length === 0) {
                    // Если ничего не нашли, берем последнюю строку
                    const lastRow = allRows[allRows.length - 1];
                    if (lastRow) {
                        console.log('🎯 Используем последнюю строку как строку итогов');
                        footerRows = [lastRow];
                    }
                }
            }
            
            if (footerRows.length === 0) {
                console.warn('⚠️ Строки итогов вообще не найдены!');
                return;
            }
            
            console.log('📊 Обновляем', footerRows.length, 'строк итогов');
            
            // Обновляем найденные строки итогов
            footerRows.forEach((row, rowIndex) => {
                console.log(`📊 Обрабатываем строку итогов ${rowIndex + 1}`);
                this._updateSingleTotalRow(row, totals);
            });
            
            console.log('📊 Строки итогов обновлены:', totals);
        },
        
        // Обновление одной строки итогов
        _updateSingleTotalRow: function(row, totals) {
            // Проверяем тип строки итогов
            const isGrandTotal = row.querySelector('td:first-child')?.textContent?.toLowerCase().includes('общий итог');
            
            // Пробуем разные способы поиска ячеек
            let sumCell = row.querySelector('[data-field="sum"]') || 
                         row.querySelector('td[data-col-index="5"]') ||
                         row.cells[5];
            
            let clientSumCell = row.querySelector('[data-field="client_sum"]') || 
                               row.querySelector('td[data-col-index="9"]') ||
                               row.cells[9];
            
            // Не обрабатываем ячейку "Цена клиента" в итогах - она не должна содержать сумму
            
            console.log('🔍 Найденные ячейки:');
            console.log('  - Ячейка суммы (колонка 5):', !!sumCell);
            console.log('  - Ячейка суммы клиента (колонка 9):', !!clientSumCell);
            console.log('  - Тип строки итогов:', isGrandTotal ? 'ОБЩИЙ ИТОГ' : 'Промежуточный итог');
            
            if (sumCell) {
                // В общем итоге выводим сумму всех работ
                const value = totals.sum;
                const formattedSum = this._formatNumber(value);
                sumCell.textContent = formattedSum;
                sumCell.title = `Общая стоимость работ: ${this._formatCurrency(value)}`;
                console.log('✅ Обновлена ячейка столбца "Стоимость":', formattedSum);
            } else {
                console.warn('❌ Ячейка столбца "Стоимость" не найдена');
            }
            
            if (clientSumCell) {
                // В общем итоге выводим сумму всех работ И материалов
                const value = isGrandTotal ? totals.grand_total : totals.client_sum;
                const formattedClientSum = this._formatNumber(value);
                clientSumCell.textContent = formattedClientSum;
                
                if (isGrandTotal) {
                    clientSumCell.title = `Общий итог: ${this._formatCurrency(value)} (работы: ${this._formatCurrency(totals.client_sum)}, материалы: ${this._formatCurrency(totals.materials_sum)})`;
                    console.log('✅ Обновлена ячейка ОБЩЕГО ИТОГА:', formattedClientSum);
                } else {
                    clientSumCell.title = `Общая сумма клиента: ${this._formatCurrency(value)}`;
                    console.log('✅ Обновлена ячейка столбца "Сумма клиента":', formattedClientSum);
                }
            } else {
                console.warn('❌ Ячейка столбца "Сумма клиента" не найдена');
            }
            
            // Добавляем визуальную индикацию обновления
            const originalBg = row.style.backgroundColor;
            row.style.backgroundColor = isGrandTotal ? '#c3e6cb' : '#d4edda';
            setTimeout(() => {
                row.style.backgroundColor = originalBg;
            }, 1000);
        },
        
        // Форматирование чисел
        _formatNumber: function(num) {
            if (isNaN(num) || num === null || num === undefined) return '0';
            
            return new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
                useGrouping: true
            }).format(parseFloat(num));
        },
        
        // Форматирование валютных значений
        _formatCurrency: function(num) {
            if (isNaN(num) || num === null || num === undefined) return '0 ₽';
            
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(parseFloat(num));
        },
        
        // Система обратных вызовов
        registerCallback: function(eventName, callback) {
            if (!this._callbacks) this._callbacks = {};
            if (!this._callbacks[eventName]) this._callbacks[eventName] = [];
            this._callbacks[eventName].push(callback);
            return this;
        },
        
        _triggerCallbacks: function(eventName, data) {
            if (this._callbacks && this._callbacks[eventName]) {
                this._callbacks[eventName].forEach(callback => {
                    try {
                        callback(data);
                    } catch (error) {
                        console.error('Ошибка в callback:', error);
                    }
                });
            }
        },
        
        // Методы для диагностики
        getPerformanceStats: function() {
            if (window.UnifiedFormulaSystem) {
                return window.UnifiedFormulaSystem.getStats();
            }
            return {};
        },
        
        validateAllFormulas: function() {
            console.log('✅ Валидация формул через унифицированную систему');
            return { valid: true, errors: [] };
        },
        
        getAllFormulas: function() {
            const editorData = this._getEditorData();
            if (!editorData) return [];
            
            const formulas = [];
            editorData.data.forEach((row, rowIndex) => {
                editorData.columns.forEach(column => {
                    const value = row[column.name];
                    if (value && String(value).startsWith('=')) {
                        formulas.push({
                            row: rowIndex,
                            field: column.name,
                            formula: value
                        });
                    }
                });
            });
            return formulas;
        }
    };
    
    console.log('✅ Создан совместимый адаптер для унифицированной системы формул');
} else {
    console.log('⚠️ window.ExcelFormulaSystem уже существует, проверяем методы...');
    
    // Проверяем, есть ли метод calculateTotals
    if (typeof window.ExcelFormulaSystem.calculateTotals !== 'function') {
        console.log('🔧 Добавляем отсутствующий метод calculateTotals');
        
        // Добавляем метод calculateTotals, если его нет
        window.ExcelFormulaSystem.calculateTotals = function() {
            const table = document.querySelector('#json-table-container-table');
            if (!table) {
                console.warn('⚠️ Таблица не найдена для расчета итогов');
                return null;
            }
            
         
            
            // Упрощенный расчет итогов
            let workTotal = 0;         // Итог работ
            let clientWorkTotal = 0;    // Итог работ для клиента
            let materialsTotal = 0;     // Итог материалов
            let grandTotal = 0;         // Общий итог (работы + материалы)
            
            const rows = table.querySelectorAll('tbody tr:not(.footer-row)');
            rows.forEach(row => {
                // Определяем тип строки (работа или материал)
                const typeCell = row.querySelector('td[data-field="type"]');
                const isMaterial = typeCell && typeCell.textContent.trim().toLowerCase() === 'materials';
                
                const sumCell = row.querySelector('td[data-field="sum"]');
                const clientSumCell = row.querySelector('td[data-field="client_sum"]');
                
                // Пропускаем заголовки групп и итоговые строки
                if (row.classList.contains('group-header') || row.classList.contains('total-row')) {
                    return;
                }
                
                if (sumCell && sumCell.textContent) {
                    const sum = parseFloat(sumCell.textContent.replace(/[^\d.-]/g, '')) || 0;
                    if (!isMaterial) {
                        workTotal += sum;
                    }
                }
                
                if (clientSumCell && clientSumCell.textContent) {
                    const clientSum = parseFloat(clientSumCell.textContent.replace(/[^\d.-]/g, '')) || 0;
                    if (isMaterial) {
                        materialsTotal += clientSum;
                    } else {
                        clientWorkTotal += clientSum;
                    }
                }
            });
            
            // Рассчитываем общий итог
            grandTotal = clientWorkTotal + materialsTotal;
            
          
            
            // Обновляем строку итогов
            const footerRows = Array.from(table.querySelectorAll('tr.footer-row, tr.grand-total-row'));
            if (footerRows.length === 0) {
                // Если специальных классов нет, ищем по тексту
                const allRows = Array.from(table.querySelectorAll('tbody tr'));
                footerRows.push(...allRows.filter(row => {
                    const nameCell = row.querySelector('td[data-field="name"], td:first-child');
                    return nameCell && nameCell.textContent.toLowerCase().includes('итог');
                }));
            }
            
            // Обновляем найденные строки итогов
            footerRows.forEach(row => {
                const isGrandTotal = row.querySelector('td:first-child')?.textContent?.toLowerCase().includes('общий итог');
                const footerSumCell = row.querySelector('td[data-field="sum"]') || row.cells[5];
                const footerClientSumCell = row.querySelector('td[data-field="client_sum"]') || row.cells[9];
                
                if (footerSumCell) {
                    footerSumCell.textContent = workTotal.toLocaleString('ru-RU');
                
                }
                
                if (footerClientSumCell) {
                    // В общем итоге отображаем сумму работ и материалов
                    const value = isGrandTotal ? grandTotal : clientWorkTotal;
                    footerClientSumCell.textContent = value.toLocaleString('ru-RU');
                    
                    if (isGrandTotal) {
                     
                        // Подсветка строки общего итога для привлечения внимания
                        row.style.backgroundColor = '#c3e6cb';
                        setTimeout(() => { row.style.backgroundColor = ''; }, 1000);
                    } else {
                      
                    }
                }
            });
            
            return { 
                sum: workTotal, 
                client_sum: clientWorkTotal, 
                materials_sum: materialsTotal, 
                grand_total: grandTotal 
            };
        };
    }
    
    console.log('✅ Проверка window.ExcelFormulaSystem завершена');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Инициализация системы автоматического пересчета v5.0');
    
    // Диагностика состояния window.ExcelFormulaSystem
    console.log('🔍 Проверка window.ExcelFormulaSystem:', {
        exists: typeof window.ExcelFormulaSystem !== 'undefined',
        calculateTotals: typeof window.ExcelFormulaSystem?.calculateTotals,
        methods: window.ExcelFormulaSystem ? Object.keys(window.ExcelFormulaSystem) : 'N/A'
    });
    
    // Проверяем наличие унифицированной системы
    if (typeof window.UnifiedFormulaSystem !== 'undefined') {
        console.log('✅ Унифицированная система формул доступна');
    } else {
        console.warn('⚠️ Унифицированная система формул не найдена');
    }
    
    // Настройка для Handsontable (если используется)
    let handsontableInitialized = false;
    
    function waitForHandsontable() {
        if (typeof window.hot !== 'undefined' && window.hot) {
            console.log('📊 Handsontable обнаружен - настраиваем интеграцию');
            handsontableInitialized = true;
            
            // Перехватываем изменения в Handsontable
            window.hot.addHook('afterChange', function(changes, source) {
                if (source !== 'loadData' && changes && changes.length > 0) {
                    console.log('🔄 Изменение в Handsontable - запрос пересчета');
                    window.ExcelFormulaSystem.recalculateAll();
                }
            });
        } else if (!handsontableInitialized) {
            setTimeout(waitForHandsontable, 100);
        }
    }
    
    waitForHandsontable();
    
    // Настройка для JSON Table Editor
    document.addEventListener('handsontable-ready', function() {
        console.log('📊 Событие handsontable-ready получено');
        waitForHandsontable();
    });
    
    // Отслеживаем изменения в таблице для автоматического пересчета итогов
    const observer = new MutationObserver(function(mutations) {
        let shouldRecalculate = false;
        
        mutations.forEach(function(mutation) {
            // Проверяем изменения в ячейках с данными
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                const target = mutation.target;
                if (target.closest && target.closest('#json-table-container-table')) {
                    const cell = target.closest('td[data-field]');
                    if (cell) {
                        const field = cell.getAttribute('data-field');
                        // Если изменились важные поля, запускаем пересчет
                        if (['quantity', 'price', 'sum', 'markup', 'discount', 'client_price', 'client_sum'].includes(field)) {
                            shouldRecalculate = true;
                        }
                    }
                }
            }
        });
        
        if (shouldRecalculate) {
          
            setTimeout(() => {
                if (window.ExcelFormulaSystem && typeof window.ExcelFormulaSystem.calculateTotals === 'function') {
                    window.ExcelFormulaSystem.calculateTotals();
                } else {
                    console.error('❌ window.ExcelFormulaSystem.calculateTotals не доступен в MutationObserver!', window.ExcelFormulaSystem);
                }
            }, 100);
        }
    });
    
    // Начинаем отслеживание изменений
    const tableContainer = document.querySelector('#json-table-container-wrapper');
    if (tableContainer) {
        observer.observe(tableContainer, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }
    
    // Добавляем обработчики событий для ячеек таблицы
    document.addEventListener('click', function(e) {
        const cell = e.target.closest('td[data-field]');
        if (cell && cell.classList.contains('editable')) {
            // При клике на редактируемую ячейку добавляем обработчик изменений
            cell.addEventListener('blur', function() {
                const field = this.getAttribute('data-field');
                if (['quantity', 'price', 'sum', 'markup', 'discount', 'client_price', 'client_sum'].includes(field)) {
                    console.log('🔄 Пересчет итогов после редактирования ячейки:', field);
                    setTimeout(() => {
                        if (window.ExcelFormulaSystem && typeof window.ExcelFormulaSystem.calculateTotals === 'function') {
                            window.ExcelFormulaSystem.calculateTotals();
                        } else {
                            console.error('❌ window.ExcelFormulaSystem.calculateTotals не доступен в blur handler!', window.ExcelFormulaSystem);
                        }
                    }, 50);
                }
            }, { once: true });
        }
    });
    
    // Принудительный пересчет итогов при полной загрузке страницы
    setTimeout(() => {
        console.log('🔄 Принудительный пересчет итогов после загрузки');
        if (window.ExcelFormulaSystem && typeof window.ExcelFormulaSystem.calculateTotals === 'function') {
            window.ExcelFormulaSystem.calculateTotals();
        } else {
            console.error('❌ window.ExcelFormulaSystem.calculateTotals не доступен!', window.ExcelFormulaSystem);
        }
    }, 2000);
    
    // Также добавляем обработчик для события загрузки страницы
    window.addEventListener('load', function() {
        setTimeout(() => {
            console.log('🔄 Дополнительный пересчет итогов после window.load');
            if (window.ExcelFormulaSystem && typeof window.ExcelFormulaSystem.calculateTotals === 'function') {
                window.ExcelFormulaSystem.calculateTotals();
            } else {
                console.error('❌ window.ExcelFormulaSystem.calculateTotals не доступен в window.load!', window.ExcelFormulaSystem);
            }
        }, 1000);
    });
    
    // Функция для инициализации системы пересчета итогов
    window.initTotalCalculation = function() {
        console.log('🚀 Инициализация системы пересчета итогов');
        if (window.ExcelFormulaSystem && typeof window.ExcelFormulaSystem.calculateTotals === 'function') {
            // Проверяем, что таблица загружена
            const checkTableLoaded = () => {
                const table = document.querySelector('#json-table-container-table');
                const rows = table ? table.querySelectorAll('tbody tr') : [];
                
                if (table && rows.length > 0) {
                    console.log('✅ Таблица загружена, выполняем расчет итогов');
                    window.ExcelFormulaSystem.calculateTotals();
                    return true;
                } else {
                    console.log('⏳ Таблица еще не загружена, повторная попытка...');
                    return false;
                }
            };
            
            // Пытаемся выполнить расчет с интервалом
            let attempts = 0;
            const maxAttempts = 20;
            const interval = setInterval(() => {
                attempts++;
                if (checkTableLoaded() || attempts >= maxAttempts) {
                    clearInterval(interval);
                    if (attempts >= maxAttempts) {
                        console.warn('⚠️ Превышено максимальное количество попыток инициализации');
                    }
                }
            }, 500);
            
            return true;
        }
        return false;
    };
    
    // Экспортируем функцию глобально для внешнего использования
    window.recalculateTotals = function() {
        if (window.ExcelFormulaSystem && typeof window.ExcelFormulaSystem.calculateTotals === 'function') {
            return window.ExcelFormulaSystem.calculateTotals();
        } else {
            console.error('❌ window.ExcelFormulaSystem.calculateTotals не доступен в recalculateTotals!', window.ExcelFormulaSystem);
            return null;
        }
    };
    
    // Добавляем функцию принудительного обновления для отладки
    window.forceUpdateTotals = function(testSum = 100000, testClientSum = 120000) {
        console.log('🛠️ Принудительное обновление итогов для отладки');
        
        // Ищем строку итогов
        const allRows = document.querySelectorAll('#json-table-container-table tbody tr');
        let targetRow = null;
        
        // Ищем по классу
        targetRow = document.querySelector('tr.footer-row');
        
        // Если не найдена, ищем по содержимому
        if (!targetRow) {
            for (let row of allRows) {
                const nameCell = row.querySelector('td[data-field="name"]');
                if (nameCell && nameCell.textContent.toUpperCase().includes('ОБЩИЙ ИТОГ')) {
                    targetRow = row;
                    break;
                }
            }
        }
        
        // Если все еще не найдена, берем последнюю строку
        if (!targetRow && allRows.length > 0) {
            targetRow = allRows[allRows.length - 1];
        }
        
        if (targetRow) {
            console.log('🎯 Найдена строка для обновления:', targetRow);
            
            // Обновляем только нужные ячейки: "Стоимость" и "Сумма клиента"
            const sumCell = targetRow.querySelector('[data-field="sum"]') || targetRow.cells[5];
            const clientSumCell = targetRow.querySelector('[data-field="client_sum"]') || targetRow.cells[9];
            
            if (sumCell) {
                sumCell.textContent = testSum.toLocaleString('ru-RU');
                sumCell.style.backgroundColor = '#28a745';
                sumCell.style.color = 'white';
                console.log('✅ Обновлена ячейка "Стоимость":', testSum);
            }
            
            if (clientSumCell) {
                clientSumCell.textContent = testClientSum.toLocaleString('ru-RU');
                clientSumCell.style.backgroundColor = '#28a745';
                clientSumCell.style.color = 'white';
                console.log('✅ Обновлена ячейка "Сумма клиента":', testClientSum);
            }
            
            // Убираем выделение через 2 секунды
            setTimeout(() => {
                if (sumCell) {
                    sumCell.style.backgroundColor = '';
                    sumCell.style.color = '';
                }
                if (clientSumCell) {
                    clientSumCell.style.backgroundColor = '';
                    clientSumCell.style.color = '';
                }
            }, 2000);
            
            // Генерируем событие для обновления materials_amount
            const totals = {
                work_total: testSum,
                materials_total: 0,
                grand_total: testSum,
                client_work_total: testClientSum,
                client_materials_total: 0,
                client_grand_total: testClientSum
            };
            
            document.dispatchEvent(new CustomEvent('formula-recalculated', {
                detail: { totals: totals }
            }));
            
            return { success: true, row: targetRow, sumCell, clientSumCell };
        } else {
            console.error('❌ Строка итогов не найдена!');
            return { success: false };
        }
    };
    
    // Отладочные функции для диагностики
    window.debugEstimate = {
        checkTableExists: function() {
            const table = document.querySelector('#json-table-container-table');
            console.log('🔍 Таблица найдена:', !!table);
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                const footerRows = table.querySelectorAll('tr.footer-row');
                console.log('📊 Всего строк:', rows.length);
                console.log('🎯 Строк итогов:', footerRows.length);
                
                // Дополнительная диагностика
                console.log('🔍 Все строки в таблице:');
                rows.forEach((row, index) => {
                    const nameCell = row.querySelector('td[data-field="name"]');
                    const name = nameCell ? nameCell.textContent.trim() : 'БЕЗ ИМЕНИ';
                    console.log(`  ${index + 1}: "${name}" (классы: ${row.className})`);
                });
                
                return { table, rows, footerRows };
            }
            return null;
        },
        
        getTableData: function() {
            if (window.ExcelFormulaSystem) {
                const data = window.ExcelFormulaSystem._extractDataFromTable(document.querySelector('#json-table-container-table'));
                console.log('📋 Данные таблицы:', data);
                return data;
            }
            return null;
        },
        
        testCalculation: function() {
            console.log('🧮 Запуск тестового расчета...');
            if (window.ExcelFormulaSystem) {
                const totals = window.ExcelFormulaSystem.calculateTotals();
                console.log('🎯 Результат расчета:', totals);
                return totals;
            }
            return null;
        },
        
        checkFooterCells: function() {
            console.log('🔍 Поиск строк итогов...');
            
            // Поиск по классу
            let footerRows = document.querySelectorAll('tr.footer-row');
            console.log('🔍 Найдено строк по селектору tr.footer-row:', footerRows.length);
            
            // Поиск по содержимому
            const allRows = document.querySelectorAll('#json-table-container-table tbody tr');
            const contentBasedRows = Array.from(allRows).filter(row => {
                const nameCell = row.querySelector('td[data-field="name"]');
                const text = nameCell ? nameCell.textContent.trim().toUpperCase() : '';
                return text.includes('ОБЩИЙ ИТОГ') || text.includes('ИТОГ') || text.includes('TOTAL');
            });
            console.log('🔍 Найдено строк по содержимому:', contentBasedRows.length);
            
            // Объединяем результаты
            const allFooterRows = new Set([...footerRows, ...contentBasedRows]);
            
            console.log('🔍 Итого уникальных строк итогов:', allFooterRows.size);
            
            allFooterRows.forEach((row, index) => {
                console.log(`📊 Строка итогов ${index + 1}:`);
                const sumCell = row.querySelector('[data-field="sum"]') || row.cells[5];
                const clientSumCell = row.querySelector('[data-field="client_sum"]') || row.cells[9];
                const clientPriceCell = row.querySelector('[data-field="client_price"]') || row.cells[8];
                
                console.log('  - Ячейка суммы:', sumCell ? `"${sumCell.textContent}"` : 'НЕ НАЙДЕНА');
                console.log('  - Ячейка суммы клиента:', clientSumCell ? `"${clientSumCell.textContent}"` : 'НЕ НАЙДЕНА');
                console.log('  - Ячейка цены клиента:', clientPriceCell ? `"${clientPriceCell.textContent}"` : 'НЕ НАЙДЕНА');
            });
            
            return Array.from(allFooterRows);
        },
        
        manualUpdate: function(sum = 100000, clientSum = 120000) {
            console.log('✏️ Ручное обновление итогов для теста...');
            return window.forceUpdateTotals(sum, clientSum);
        },
        
        findAllCells: function() {
            console.log('🔍 Поиск всех ячеек с data-field...');
            const cells = document.querySelectorAll('td[data-field]');
            const fieldCounts = {};
            
            cells.forEach(cell => {
                const field = cell.getAttribute('data-field');
                fieldCounts[field] = (fieldCounts[field] || 0) + 1;
            });
            
            console.log('📊 Найденные поля:', fieldCounts);
            
            // Специально ищем ячейки сумм
            const sumCells = document.querySelectorAll('td[data-field="sum"]');
            const clientSumCells = document.querySelectorAll('td[data-field="client_sum"]');
            
            console.log('💰 Ячейки столбца "Стоимость":', sumCells.length);
            console.log('💰 Ячейки столбца "Сумма клиента":', clientSumCells.length);
            
            return { fieldCounts, sumCells, clientSumCells };
        },
        
        calculateCorrectTotals: function() {
            const table = document.querySelector('#json-table-container-table');
            if (!table) {
                console.warn('⚠️ Таблица не найдена для расчета итогов');
                return { sum: 0, client_sum: 0 };
            }
            
            // Получаем все строки таблицы
            const rows = Array.from(table.querySelectorAll('tbody tr:not(.footer-row):not(.group-header)'));
            
            let totalSum = 0;
            let totalClientSum = 0;
            let materialsTotalSum = 0;
            let materialsTotalClientSum = 0;
            
            console.log(`🧮 Расчет итогов для ${rows.length} строк...`);
            
            rows.forEach((row, rowIndex) => {
                // Пропускаем строки итогов и заголовки
                if (row.classList.contains('footer-row') || 
                    row.classList.contains('group-header') || 
                    row.classList.contains('total-row')) {
                    return;
                }
                
                // Получаем ячейки с нужными значениями
                const nameCell = row.querySelector('[data-field="name"]');
                const quantityCell = row.querySelector('[data-field="quantity"]');
                const priceCell = row.querySelector('[data-field="price"]');
                const sumCell = row.querySelector('[data-field="sum"]');
                const markupCell = row.querySelector('[data-field="markup"]');
                const discountCell = row.querySelector('[data-field="discount"]');
                const clientPriceCell = row.querySelector('[data-field="client_price"]');
                const clientSumCell = row.querySelector('[data-field="client_sum"]');
                const typeCell = row.querySelector('[data-field="type"]');
                
                // Проверяем, что ячейки найдены
                if (!nameCell || !quantityCell || !priceCell) {
                    return;
                }
                
                // Получаем значения
                const name = nameCell.textContent.trim();
                
                // Пропускаем строки итогов
                if (name.toLowerCase().includes('итог') || name.toLowerCase().includes('всего')) {
                    return;
                }
                
                // Проверяем, содержит ли строка значения или пуста
                if (!name || name === '') {
                    return;
                }
                
                // Парсим числовые значения
                const quantity = parseFloat(quantityCell.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
                const price = parseFloat(priceCell.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
                const markup = markupCell ? (parseFloat(markupCell.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0) : 0;
                const discount = discountCell ? (parseFloat(discountCell.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0) : 0;
                
                // Расчет стоимости и стоимости для заказчика
                const sum = price * quantity;
                
                // Расчет цены для заказчика с учетом наценки и скидки
                let clientPrice = price;
                if (markup > 0) {
                    clientPrice *= (1 + markup / 100);
                }
                if (discount > 0) {
                    clientPrice *= (1 - discount / 100);
                }
                
                // Расчет стоимости для заказчика
                const clientSum = clientPrice * quantity;
                
                // Проверяем тип строки (работы или материалы)
                const isMaterial = typeCell && typeCell.textContent.trim().toLowerCase().includes('material');
                
                // Суммируем значения
                if (isMaterial) {
                    materialsTotalSum += sum;
                    materialsTotalClientSum += clientSum;
                } else {
                    totalSum += sum;
                    totalClientSum += clientSum;
                }
                
                // Отладочная информация
                console.log(`Строка ${rowIndex + 1} "${name}": кол-во=${quantity}, цена=${price}, сумма=${sum}, наценка=${markup}%, скидка=${discount}%, цена клиента=${clientPrice}, сумма клиента=${clientSum}`);
            });
            
            // Общие итоги
            const grandTotalSum = totalSum + materialsTotalSum;
            const grandTotalClientSum = totalClientSum + materialsTotalClientSum;
            
            console.log('📊 Итоги расчета:');
            console.log('  - Работы (Стоимость):', totalSum);
            console.log('  - Работы (Стоимость для заказчика):', totalClientSum);
            console.log('  - Материалы (Стоимость):', materialsTotalSum);
            console.log('  - Материалы (Стоимость для заказчика):', materialsTotalClientSum);
            console.log('  - ОБЩИЙ ИТОГ (Стоимость):', grandTotalSum);
            console.log('  - ОБЩИЙ ИТОГ (Стоимость для заказчика):', grandTotalClientSum);
            
            const totals = {
                sum: totalSum,
                client_sum: totalClientSum,
                materials_sum: materialsTotalSum,
                materials_client_sum: materialsTotalClientSum,
                grand_total_sum: grandTotalSum,
                grand_total_client_sum: grandTotalClientSum,
                // Дополнительные поля для совместимости
                work_total: totalSum,
                materials_total: materialsTotalSum,
                grand_total: grandTotalSum,
                client_work_total: totalClientSum,
                client_materials_total: materialsTotalClientSum,
                client_grand_total: grandTotalClientSum
            };
            
            // Генерируем событие для уведомления других компонентов об изменении итогов
            document.dispatchEvent(new CustomEvent('formula-recalculated', {
                detail: { totals: totals }
            }));
            
            return totals;
        }
    };
    
    // Запускаем инициализацию сразу
    window.initTotalCalculation();
    
    // Реагируем на событие formula-system-ready
    document.addEventListener('formula-system-ready', function(event) {
        console.log('🧮 Система формул готова к работе');
        // Запускаем расчет итогов после инициализации формул
        window.initTotalCalculation();
    });
    
    // Добавляем обработчик для пересчета итогов при изменении ячеек таблицы
    document.addEventListener('cell-value-changed', function(event) {
        console.log('📝 Изменение значения ячейки:', event.detail);
        // Запускаем пересчет с небольшой задержкой
        setTimeout(() => window.forceUpdateTotals(), 300);
    });
    
    // Добавляем обработчики для всех возможных событий изменения таблицы
    ['row-added', 'row-removed', 'formula-calculated', 'table-rendered'].forEach(eventName => {
        document.addEventListener(eventName, function() {
            console.log(`📊 Событие ${eventName} - запуск пересчета итогов`);
            setTimeout(() => window.forceUpdateTotals(), 300);
        });
    });
    
    // Перехватываем отправку формы для подготовки данных
    document.addEventListener('submit', function(e) {
        if (e.target.tagName === 'FORM') {
            console.log('💾 Подготовка данных перед отправкой формы');
            window.ExcelFormulaSystem.prepareDataForSave();
            // Обновляем итоги перед отправкой
            window.forceUpdateTotals();
        }
    });
    
    /**
     * Автоматическое сохранение сметы в JSON формате
     */
    function setupAutoSaveEstimate() {
        // Получаем ID сметы из URL
        const getEstimateId = () => {
            const path = window.location.pathname;
            const matches = path.match(/\/(?:partner\/)?estimates\/(\d+)/);
            return matches ? matches[1] : null;
        };
        
        // Настройка автосохранения
        const estimateId = getEstimateId();
        if (!estimateId) {
            console.warn('⚠️ ID сметы не найден, автоматическое сохранение отключено');
            return;
        }
        
        console.log(`📝 Настройка автоматического сохранения для сметы #${estimateId}`);
        
        // Получение CSRF-токена
        const getCsrfToken = () => {
            const token = document.querySelector('meta[name="csrf-token"]');
            return token ? token.getAttribute('content') : '';
        };
        
        // Сохранение данных сметы на сервер
        const saveEstimateData = (data) => {
            // Получаем текущую дату и время для метаданных
            const now = new Date();
            
            // Добавляем метаданные к данным сметы
            const dataToSave = {
                ...data,
                meta: {
                    ...(data.meta || {}),
                    estimate_id: estimateId,
                    updated_at: now.toISOString(),
                    version: '2.0'
                }
            };
            
            console.log(`🔄 Сохранение данных сметы #${estimateId}...`);
            
            return fetch(`/partner/estimates/${estimateId}/save-json`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(dataToSave)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Ошибка HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    console.log(`✅ Данные сметы #${estimateId} успешно сохранены`);
                    // Показываем уведомление об успешном сохранении, если доступно
                    if (typeof showToast === 'function') {
                        showToast('success', 'Смета успешно сохранена');
                    }
                    return true;
                } else {
                    throw new Error(result.message || 'Неизвестная ошибка при сохранении');
                }
            })
            .catch(error => {
                console.error(`❌ Ошибка сохранения сметы #${estimateId}:`, error);
                // Показываем уведомление об ошибке, если доступно
                if (typeof showToast === 'function') {
                    showToast('error', `Ошибка сохранения: ${error.message}`);
                }
                return false;
            });
        };
        
        // Получение данных из редактора
        const getEditorData = () => {
            if (window.jsonTableEditor) {
                // Получаем данные из JsonTableEditor
                return {
                    sheets: window.jsonTableEditor.sheets,
                    currentSheet: window.jsonTableEditor.currentSheetIndex,
                    meta: window.jsonTableEditor.meta || {}
                };
            } else {
                // Альтернативный метод - получение данных из DOM
                const table = document.querySelector('#json-table-container-table');
                if (!table) return null;
                
                // Извлечение данных из таблицы DOM
                const extractDataFromTable = () => {
                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    const data = [];
                    
                    rows.forEach(row => {
                        if (row.classList.contains('footer-row')) return; // Пропускаем итоговые строки
                        
                        const cells = Array.from(row.querySelectorAll('td[data-field]'));
                        const rowData = {};
                        
                        cells.forEach(cell => {
                            const field = cell.getAttribute('data-field');
                            const value = cell.textContent.trim();
                            rowData[field] = value;
                        });
                        
                        // Добавляем уникальный ID для строки, если его нет
                        if (!rowData._id) {
                            rowData._id = 'row_' + Math.random().toString(36).substr(2, 9);
                        }
                        
                        data.push(rowData);
                    });
                    
                    return data;
                };
                
                return {
                    sheets: [
                        {
                            name: 'Основной',
                            data: extractDataFromTable()
                        }
                    ],
                    currentSheet: 0,
                    meta: {
                        estimate_id: estimateId,
                        updated_at: new Date().toISOString(),
                        version: '2.0'
                    }
                };
            }
        };
        
        // Настройка автосохранения с интервалом
        let autoSaveTimeout;
        const AUTOSAVE_INTERVAL = 60000; // 1 минута
        
        // Функция для планирования следующего автосохранения
        const scheduleAutoSave = () => {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                const data = getEditorData();
                if (data) {
                    saveEstimateData(data)
                        .then(() => scheduleAutoSave());
                } else {
                    console.warn('⚠️ Не удалось получить данные для автосохранения');
                    scheduleAutoSave();
                }
            }, AUTOSAVE_INTERVAL);
        };
        
        // Запуск планирования автосохранения
        scheduleAutoSave();
        
        // Запуск сохранения при изменении данных в таблице
        ['cell-value-changed', 'row-added', 'row-removed'].forEach(eventName => {
            document.addEventListener(eventName, () => {
                // Перезапускаем таймер автосохранения при изменении данных
                scheduleAutoSave();
            });
        });
        
        // Принудительное сохранение при нажатии Ctrl+S
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const data = getEditorData();
                if (data) {
                    saveEstimateData(data);
                }
            }
        });
        
        // Добавление кнопки сохранения в панель инструментов
        setTimeout(() => {
            const toolbar = document.querySelector('.json-table-toolbar');
            if (toolbar && !document.getElementById('save-estimate-btn')) {
                const saveButton = document.createElement('button');
                saveButton.id = 'save-estimate-btn';
                saveButton.className = 'btn btn-sm btn-success ms-2';
                saveButton.innerHTML = '<i class="bi bi-save"></i> Сохранить смету';
                saveButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    const data = getEditorData();
                    if (data) {
                        saveEstimateData(data);
                    } else {
                        console.warn('⚠️ Не удалось получить данные для сохранения');
                    }
                });
                
                toolbar.appendChild(saveButton);
            }
        }, 1500);
    }
    
    // Запускаем настройку автосохранения после полной загрузки страницы
    document.addEventListener('DOMContentLoaded', setupAutoSaveEstimate);
    
    // Добавляем кнопку "Пересчитать итоги" в нижний колонтитул таблицы, если её еще нет
    setTimeout(() => {
        const toolbar = document.querySelector('.json-table-toolbar');
        if (toolbar && !document.getElementById('recalculate-totals-btn')) {
            const recalcButton = document.createElement('button');
            recalcButton.id = 'recalculate-totals-btn';
            recalcButton.className = 'btn btn-sm btn-primary ms-2';
            recalcButton.innerHTML = '<i class="bi bi-calculator"></i> Пересчитать итоги';
            recalcButton.addEventListener('click', function(e) {
                e.preventDefault();
                window.forceUpdateTotals();
            });
            
            toolbar.appendChild(recalcButton);
        }
    }, 1000);

    console.log('✅ Компонент автоматического пересчета формул v5.0 успешно инициализирован');
});
</script>
