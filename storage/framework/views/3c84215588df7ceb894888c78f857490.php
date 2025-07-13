


<script>
// Защита от ошибки с отсутствующим DOM-элементом
window.ensureExcelEditorExists = function() {
    if (!document.getElementById('excelEditor')) {
        console.warn('⚠️ Контейнер #excelEditor не найден, создаем заглушку');
        // Создаем контейнер для редактора, если его нет на странице
        const tempContainer = document.createElement('div');
        tempContainer.id = 'excelEditor';
        tempContainer.style.cssText = 'height: 100vh; width: 100%; overflow: hidden;';
        
        // Находим подходящее место для вставки
        const cardBody = document.querySelector('.card-body');
        if (cardBody) {
            cardBody.appendChild(tempContainer);
            console.log('✅ Создан временный контейнер для Excel-редактора');
            return true;
        }
        
        // Если не нашли .card-body, добавляем в body
        document.body.appendChild(tempContainer);
        console.log('✅ Создан временный контейнер для Excel-редактора (добавлен в body)');
        return true;
    }
    return true;
};

// Проверяем наличие контейнера при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    window.ensureExcelEditorExists();
});
</script>


<script src="<?php echo e(asset('js/estimates/loading-manager.js')); ?>?v=<?php echo e(time()); ?>"></script>
<script src="<?php echo e(asset('js/estimates/estimate-calculator-unified.js')); ?>?v=<?php echo e(time()); ?>"></script>
<script src="<?php echo e(asset('js/estimates/formula-bridge.js')); ?>?v=<?php echo e(time()); ?>"></script>
<script src="<?php echo e(asset('js/estimates/simple-excel-editor.js')); ?>?v=<?php echo e(time()); ?>"></script>


<script src="<?php echo e(asset('js/estimates/optimized-ui-controls.js')); ?>?v=<?php echo e(time()); ?>"></script>
<script src="<?php echo e(asset('js/estimates/create-enhancements.js')); ?>?v=<?php echo e(time()); ?>"></script>

<script>
    // Глобальная настройка оптимизаций
    window.EXCEL_OPTIMIZATION_ENABLED = true;
    
    // Проверка производительности системы пользователя для автоматического включения/выключения оптимизаций
    window.addEventListener('DOMContentLoaded', function() {
        // Проверяем наличие контейнера для редактора
        if (window.ensureExcelEditorExists) {
            window.ensureExcelEditorExists();
        }
        
        // Включаем индикатор загрузки
        if (typeof window.LoadingManager !== 'undefined') {
            window.LoadingManager.show('Инициализация редактора...');
            window.LoadingManager.registerTask('init', 'Инициализация компонентов');
        }
        
        // Создаем глобальную переменную для таблицы, если она отсутствует
        if (typeof window.hot === 'undefined') {
            window.hot = null;
        }
        
        // Инициализация мостового API для обеспечения совместимости
        console.log('Инициализация мостового API для совместимости со старым кодом');
        if (typeof window.ExcelFormulaSystem === 'undefined') {
            window.ExcelFormulaSystem = {
                recalculate: function() {
                    if (window.forceRecalculateAll) {
                        return window.forceRecalculateAll();
                    }
                    return false;
                },
                save: function(showNotification = true) {
                    return Promise.resolve();
                },
                // Добавляем дополнительные методы для совместимости
                init: function() {
                    console.log('ExcelFormulaSystem.init() вызван из мостового API');
                    return true;
                },
                isInitialized: function() {
                    return true;
                },
                getHot: function() {
                    return window.hot;
                }
            };
        }
        
        // Безопасный доступ к глобальным функциям
        const safeCallWithTimeout = function(funcName, timeout = 1500) {
            setTimeout(() => {
                try {
                    // Проверка наличия контейнера перед вызовом
                    if (funcName === 'forceShowAllColumns') {
                        window.ensureExcelEditorExists();
                    }
                    
                    if (typeof window[funcName] === 'function') {
                        window[funcName]();
                    } else {
                        console.warn(`Функция ${funcName} недоступна`);
                    }
                } catch (error) {
                    console.error(`Ошибка при вызове ${funcName}:`, error);
                }
            }, timeout);
        };
        
        // Инициализация редактора Excel после некоторой задержки
        setTimeout(() => {
            console.log('🔄 Проверка инициализации редактора Excel');
            if (typeof window.initExcelEditor === 'function') {
                console.log('🚀 Вызов initExcelEditor() для инициализации редактора');
                
                // Используем данные сметы, если доступны
                if (typeof window.ESTIMATE_DATA !== 'undefined' && window.ESTIMATE_DATA) {
                    const dataUrl = window.ESTIMATE_DATA.dataUrl;
                    console.log('📂 Используем dataUrl из ESTIMATE_DATA:', dataUrl);
                    window.initExcelEditor(dataUrl);
                } else {
                    // Используем URL-путь как запасной вариант
                    const pathParts = window.location.pathname.split('/');
                    const estimateId = pathParts[pathParts.indexOf('estimates') + 1];
                    
                    if (estimateId && !isNaN(parseInt(estimateId))) {
                        const dataUrl = `/partner/estimates/${estimateId}/getData`;
                        console.log('📂 Используем dataUrl на основе URL:', dataUrl);
                        window.initExcelEditor(dataUrl);
                    } else {
                        console.log('📂 Создаем новую смету (dataUrl не найден)');
                        window.initExcelEditor(null);
                    }
                }
            } else {
                console.warn('⚠️ Функция initExcelEditor не найдена');
            }
        }, 800);
        
        // Настройка форсированного отображения всех столбцов таблицы
        safeCallWithTimeout('forceShowAllColumns', 3000);
        
        // Завершаем задачу инициализации
        setTimeout(() => {
            if (typeof window.LoadingManager !== 'undefined') {
                window.LoadingManager.completeTask('init');
                // Скрываем все индикаторы загрузки
                if (typeof window.forceHideAllLoaders === 'function') {
                    window.forceHideAllLoaders();
                }
            }
        }, 3500);
    });
    
    // Глобальная инициализация редактора Excel для быстрого доступа
    window.initExcelEditor = window.initExcelEditor || function(dataUrl) {
        console.log('📄 Использование стандартной функции инициализации редактора Excel');
        
        // Проверяем наличие контейнера
        if (!window.ensureExcelEditorExists()) {
            console.error('❌ Не удалось создать контейнер для редактора');
            return false;
        }
        
        // Получаем контейнер для редактора
        const container = document.getElementById('excelEditor');
        if (!container) {
            console.error('❌ Контейнер #excelEditor не найден');
            return false;
        }
        
        // Создаем глобальный объект Handsontable, если он не существует
        if (!window.Handsontable) {
            console.error('❌ Библиотека Handsontable не загружена');
            return false;
        }
        
        // Создаем простую таблицу с базовыми данными
        const data = [
            ['1', 'РАЗДЕЛ 1', '', '', '', '', '', '', ''],
            ['1.1', 'Новая строка', 'шт', '1', '0', '0', '0', '0', '0'],
            ['', 'ИТОГО ПО СМЕТЕ:', '', '', '', '', '', '', '0']
        ];
        
        // Инициализируем таблицу с базовыми настройками
        window.hot = new Handsontable(container, {
            data: data,
            rowHeaders: true,
            colHeaders: ['№', 'Наименование работ', 'Ед. изм.', 'Кол-во', 'Цена', 'Сумма', 'Наценка %', 'Скидка %', 'Итого'],
            minSpareRows: 0,
            minSpareCols: 0,
            rowHeights: 25,
            manualColumnResize: true,
            manualRowResize: true,
            licenseKey: 'non-commercial-and-evaluation'
        });
        
        console.log('✅ Простой редактор Excel успешно инициализирован');
        
        // Если указан URL с данными, загружаем их
        if (dataUrl) {
            console.log('📥 Загрузка данных с URL:', dataUrl);
            fetch(dataUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        try {
                            // Проверяем формат данных
                            if (typeof data.data === 'string') {
                                // Пытаемся распарсить строку как JSON
                                try {
                                    const parsedData = JSON.parse(data.data);
                                    window.hot.loadData(parsedData);
                                    console.log('✅ Данные успешно загружены (после преобразования из строки)');
                                } catch (parseError) {
                                    console.error('❌ Невозможно преобразовать данные из строки в формат JSON:', parseError);
                                }
                            } else {
                                // Если данные уже в подходящем формате, используем их напрямую
                                window.hot.loadData(data.data);
                                console.log('✅ Данные успешно загружены');
                            }
                        } catch (loadError) {
                            console.error('❌ Ошибка при загрузке данных в таблицу:', loadError);
                        }
                    } else {
                        console.error('❌ Ошибка загрузки данных:', data.error || 'Неизвестная ошибка');
                    }
                })
                .catch(error => {
                    console.error('❌ Ошибка при запросе данных:', error);
                });
        }
        
        // Возвращаем ссылку на созданную таблицу
        return window.hot;
    };
</script>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/partner/estimates/partials/unified-excel-scripts.blade.php ENDPATH**/ ?>