<script>
/**
 * Компонент для автоматического пересчета формул в редакторе смет
 * Версия: 4.0 (полная интеграция с excel-formula-turbo.js)
 */

// Создаем адаптер для обратной совместимости с предыдущей версией API
if (typeof window.ExcelFormulaSystem === 'undefined') {
    // Хранилище для обратных вызовов
    const callbacks = {
        'error': [],
        'recalculate': [],
        'change': [],
        'update': []
    };

    window.ExcelFormulaSystem = {
        // Основные методы расчета - используем единую систему расчета формул
        recalculateAll: function() {
            console.log('🔄 Запуск полного пересчета формул через унифицированный адаптер');
            
            // Используем новую систему расчета, если доступна
            if (typeof window.recalculateAllWithTotals === 'function') {
                const result = window.recalculateAllWithTotals();
                // Вызываем все зарегистрированные callback'и
                this._triggerCallbacks('recalculate', { complete: true });
                return result;
            } else if (typeof window.recalculateAll === 'function') {
                const result = window.recalculateAll();
                this._triggerCallbacks('recalculate', { complete: true });
                return result;
            } else {
                console.error('❌ Функция пересчета формул не найдена в единой системе формул');
                return false;
            }
        },
        
        // Пересчет для массива строк
        recalculateByRows: function(rows) {
            if (rows && rows.length === 0) return true;
            
            console.log(`🔢 Пересчет формул для строк [${rows ? rows.join(', ') : 'все'}] через унифицированный адаптер`);
            
            // Используем новую единую систему расчета
            let result = false;
            
            if (typeof window.excelFormulaTurbo?.calculateRowFormulas === 'function') {
                // Новый метод для расчета массива строк (через экспортированный интерфейс)
                result = window.excelFormulaTurbo.calculateRowFormulas(rows);
                this._triggerCallbacks('recalculate', { rows: rows, complete: false });
                
                // Явно вызываем обновление итогов
                if (typeof window.excelFormulaTurbo.updateSectionTotals === 'function') {
                    window.excelFormulaTurbo.updateSectionTotals();
                }
            } else if (typeof window.calculateRowFormulas === 'function') {
                // Новый метод для расчета массива строк (через глобальные функции)
                result = window.calculateRowFormulas(rows);
                this._triggerCallbacks('recalculate', { rows: rows, complete: false });
                
                // Явно вызываем обновление итогов
                if (typeof window.updateSectionTotals === 'function') {
                    window.updateSectionTotals();
                }
            } else if (typeof window.excelFormulaTurbo?.recalculateRow === 'function') {
                // Метод из интерфейса турбо-системы
                result = true;
                for (const row of rows) {
                    if (!window.excelFormulaTurbo.recalculateRow(row)) {
                        result = false;
                    }
                }
                
                this._triggerCallbacks('recalculate', { rows: rows, complete: false });
                
                // Обновляем итоги через интерфейс
                if (typeof window.excelFormulaTurbo.updateSectionTotals === 'function') {
                    window.excelFormulaTurbo.updateSectionTotals();
                }
            } else if (typeof window.recalculateRow === 'function') {
                // Старый метод или глобальная функция (пересчитываем каждую строку по отдельности)
                result = true;
                for (const row of rows) {
                    if (!window.recalculateRow(row)) {
                        result = false;
                    }
                }
                
                this._triggerCallbacks('recalculate', { rows: rows, complete: false });
                
                // Обновляем итоги
                if (typeof window.updateSectionTotals === 'function') {
                    window.updateSectionTotals();
                }
            } else {
                console.error('❌ Ни одна функция для пересчета строк не найдена в единой системе формул');
                result = false;
            }
            
            return result;
        },
        prepareDataForSave: function() {
            if (typeof window.clearFormulaCache === 'function') {
                window.clearFormulaCache();
            }
            return true;
        },

        // Методы для обратной совместимости со старыми системами
        recalculateTotals: function() {
            console.log('📊 Обновление итогов через унифицированный адаптер (recalculateTotals)');
            
            // Проверяем наличие оптимизированного метода
            if (typeof window.recalculateTotalsWithoutReordering === 'function') {
                const result = window.recalculateTotalsWithoutReordering();
                this._triggerCallbacks('recalculate', { totalsUpdated: true });
                
                // Принудительно обновляем отображение после пересчета
                if (typeof window.forceRenderTable === 'function') {
                    setTimeout(() => window.forceRenderTable(), 50);
                }
                
                return result;
            } else if (typeof window.updateSectionTotals === 'function') {
                // Резервный вариант
                const result = window.updateSectionTotals();
                this._triggerCallbacks('recalculate', { totalsUpdated: true });
                
                // Принудительно обновляем отображение после пересчета
                if (typeof window.forceRenderTable === 'function') {
                    setTimeout(() => window.forceRenderTable(), 50);
                }
                
                return result;
            } else {
                console.error('❌ Функции пересчета итогов не найдены в единой системе формул');
                return false;
            }
        },
        
        recalculateTotalsWithoutReordering: function() {
            console.log('📊 Обновление итогов без пересортировки через унифицированный адаптер (recalculateTotalsWithoutReordering)');
            
            // Предпочитаем специальный метод без пересортировки, если он доступен
            if (typeof window.recalculateTotalsWithoutReordering === 'function') {
                const result = window.recalculateTotalsWithoutReordering();
                this._triggerCallbacks('recalculate', { totalsUpdated: true });
                
                // Принудительно обновляем отображение после пересчета
                if (typeof window.forceRenderTable === 'function') {
                    setTimeout(() => window.forceRenderTable(), 50);
                }
                
                return result;
            } else if (typeof window.updateSectionTotals === 'function') {
                // Резервный вариант
                const result = window.updateSectionTotals();
                this._triggerCallbacks('recalculate', { totalsUpdated: true });
                
                // Принудительно обновляем отображение после пересчета
                if (typeof window.forceRenderTable === 'function') {
                    setTimeout(() => window.forceRenderTable(), 50);
                }
                
                return result;
            } else {
                console.error('❌ Функции пересчета итогов не найдены в единой системе формул');
                return false;
            }
        },
        
        // Метод для пересчета одной строки (для обратной совместимости)
        recalculateRow: function(row) {
            console.log(`🔢 Пересчет формул для строки ${row} через унифицированный адаптер (recalculateRow)`);
            
            if (typeof window.calculateRowFormulas === 'function') {
                const result = window.calculateRowFormulas(row);
                this._triggerCallbacks('recalculate', { rows: [row], complete: false });
                return result;
            } else if (typeof window.recalculateRow === 'function') {
                const result = window.recalculateRow(row);
                this._triggerCallbacks('recalculate', { rows: [row], complete: false });
                return result;
            } else {
                console.error('❌ Функция для пересчета строки не найдена в единой системе формул');
                return false;
            }
        },
        
        // Система обратных вызовов
        registerCallback: function(eventName, callback) {
            if (!callbacks[eventName]) {
                callbacks[eventName] = [];
            }
            callbacks[eventName].push(callback);
            console.log(`✅ Зарегистрирован обработчик для события ${eventName}`);
            return this;
        },
        
        // Вызов всех зарегистрированных обработчиков
        _triggerCallbacks: function(eventName, data) {
            if (callbacks[eventName] && callbacks[eventName].length > 0) {
                callbacks[eventName].forEach(callback => {
                    try {
                        callback(data);
                    } catch (e) {
                        console.error('❌ Ошибка при выполнении обработчика:', e);
                    }
                });
            }
        },
        
        // Методы для диагностики
        getPerformanceStats: function() {
            return {
                lastCalculationTime: window.lastCalculationTime || 0,
                cacheSize: 0,
                formulasCount: 0
            };
        },
        
        validateAllFormulas: function() {
            return {
                valid: true,
                errorCount: 0,
                errors: []
            };
        },
        
        getAllFormulas: function() {
            return [];
        }
    };
    
    console.log('✅ Создан расширенный адаптер для системы формул Turbo');
}

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем наличие необходимых компонентов и инициализируем новую систему формул
    if (typeof window.initFormulaCalculator === 'function') {
        window.initFormulaCalculator();
        console.log('🚀 Система расчета формул Turbo инициализирована через DOMContentLoaded');
    }
    
    // Переменная для отслеживания состояния инициализации Handsontable
    let handsontableInitialized = false;
    let setupComplete = false;
    
    // Функция для ожидания инициализации Handsontable
    function waitForHandsontable() {
        if (typeof window.hot !== 'undefined' && window.hot) {
            if (!handsontableInitialized) {
                console.log('✅ Handsontable обнаружен, продолжаем инициализацию...');
                handsontableInitialized = true;
                setupHandsontableEvents();
            }
        } else {
            console.log('⏳ Ожидание инициализации Handsontable...');
            setTimeout(waitForHandsontable, 300);
        }
    }
    
    // Настройка всех обработчиков событий для Handsontable
    function setupHandsontableEvents() {
        if (setupComplete || !window.hot) return;
        
        console.log('🔄 Инициализация компонента автоматического пересчета формул');

        // Обработчик изменений в таблице - используем единую систему пересчета формул
        window.hot.addHook('afterChange', function(changes, source) {
            if (!changes || changes === null || source === 'loadData' || source === 'formula') return;
            
            const changedRows = new Set();
            const relevantColumns = [3, 4, 6, 7]; // Количество, Цена, Наценка, Скидка
            let hasRelevantChanges = false;
            
            changes.forEach(change => {
                const [row, prop, oldValue, newValue] = change;
                const col = typeof prop === 'number' ? prop : window.hot.propToCol(prop);
                
                // Проверяем, что изменение произошло в нужных столбцах
                if (oldValue !== newValue) {
                    changedRows.add(row);
                    if (relevantColumns.includes(col)) {
                        hasRelevantChanges = true;
                        console.log(`🔄 Изменение в важном столбце ${col} строки ${row}: ${oldValue} → ${newValue}`);
                    }
                }
            });
            
            // Если есть изменения в релевантных столбцах, запускаем пересчет
            if (hasRelevantChanges && changedRows.size > 0) {
                console.log(`📊 Запуск пересчета для ${changedRows.size} строк через единую систему формул`);
                
                // Небольшая задержка перед пересчетом для обеспечения стабильности
                setTimeout(() => {
                    // Использование единой системы через адаптер
                    window.ExcelFormulaSystem.recalculateByRows(Array.from(changedRows));
                }, 10);
            }
        });

        // Обработчик создания строк
        window.hot.addHook('afterCreateRow', function(index, amount, source) {
            console.log(`🔄 Автопересчет формул после добавления ${amount} строк начиная с индекса ${index}`);
            setTimeout(() => {
                window.ExcelFormulaSystem.recalculateAll();
            }, 100);
        });

        // Обработчик удаления строк
        window.hot.addHook('afterRemoveRow', function(index, amount, source) {
            console.log(`🔄 Автопересчет формул после удаления ${amount} строк начиная с индекса ${index}`);
            setTimeout(() => {
                window.ExcelFormulaSystem.recalculateAll();
            }, 100);
        });
        
        // Обработчик загрузки данных
        window.hot.addHook('afterLoadData', function() {
            console.log('📊 Автоматический пересчет формул после загрузки данных');
            setTimeout(() => {
                // Инициализируем формулы, если они еще не инициализированы
                if (typeof window.initFormulaCalculator === 'function') {
                    window.initFormulaCalculator();
                }
                // Запускаем пересчет формул
                window.ExcelFormulaSystem.recalculateAll();
            }, 300); // Небольшая задержка для гарантии завершения загрузки
        });
        
        setupComplete = true;
    }
    
    // Запускаем ожидание
    waitForHandsontable();
    
    // Также подписываемся на событие handsontable-ready
    document.addEventListener('handsontable-ready', function() {
        console.log('🔔 Получено событие handsontable-ready');
        if (!setupComplete) {
            setupHandsontableEvents();
        }
        
        // Инициализируем формулы и запускаем пересчет
        setTimeout(() => {
            if (typeof window.initFormulaCalculator === 'function') {
                window.initFormulaCalculator();
            }
            
            window.ExcelFormulaSystem.recalculateAll();
        }, 200);
    });
    
    // Реагируем на событие formula-system-ready
    document.addEventListener('formula-system-ready', function(event) {
        console.log(`🔔 Получено событие formula-system-ready (версия ${event.detail?.version || 'неизвестна'})`);
        setTimeout(() => {
            window.ExcelFormulaSystem.recalculateAll();
        }, 200);
    });
    
    // Сохраняем данные с формулами перед отправкой формы
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'estimateForm') {
            console.log('📝 Подготовка данных и формул перед отправкой формы');
            
            // Очищаем кэш формул перед сохранением
            if (typeof window.clearFormulaCache === 'function') {
                window.clearFormulaCache();
            } else {
                window.ExcelFormulaSystem.prepareDataForSave();
            }
        }
    });

    console.log('✅ Компонент автоматического пересчета формул v3.1 успешно инициализирован');
});
</script>
