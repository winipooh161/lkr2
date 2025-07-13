/**
 * Патч для исправления обработки футера в JSON Table Editor
 * Версия: 1.0
 * Дата: 2025-07-11
 */

(function() {
    // Ожидаем загрузки DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Ждем инициализации редактора
        const checkInterval = setInterval(function() {
            if (window.jsonTableEditor) {
                clearInterval(checkInterval);
                applyFooterFix();
            }
        }, 500);
        
        // Функция применения исправления
        function applyFooterFix() {
            console.log('🛠️ Применение исправления для футера в JsonTableEditor');
            
            // Сохраняем оригинальную функцию
            const originalPrepareDataForSave = window.jsonTableEditor.prepareDataForSave;
            
            // Переопределяем функцию подготовки данных
            window.jsonTableEditor.prepareDataForSave = function() {
                console.log('📤 Проверка данных перед сохранением (патч)');
                
                // Получаем результат оригинального метода
                const result = originalPrepareDataForSave.call(this);
                
                // Проверяем и исправляем футеры в листах
                if (result && result.sheets) {
                    result.sheets.forEach((sheet, index) => {
                        // Ищем строки итогов в данных, если футер отсутствует
                        if (!sheet.footer || !sheet.footer.items || !Array.isArray(sheet.footer.items) || sheet.footer.items.length === 0) {
                            console.log(`⚠️ Отсутствует корректный футер в листе ${index + 1}, создаем...`);
                            
                            // Ищем строки итогов
                            const footerItems = [];
                            const regularItems = [];
                            
                            if (sheet.data && Array.isArray(sheet.data)) {
                                sheet.data.forEach(row => {
                                    if (row && (row._type === 'grand_total' || row.is_grand_total === true)) {
                                        footerItems.push(row);
                                    } else {
                                        regularItems.push(row);
                                    }
                                });
                                
                                // Если нашли строки футера, обновляем структуру
                                if (footerItems.length > 0) {
                                    console.log(`🔧 Извлечено ${footerItems.length} строк для футера из данных`);
                                    sheet.data = regularItems;
                                    sheet.footer = { items: footerItems };
                                } else {
                                    // Создаем минимальный футер
                                    console.log('🔧 Создаем минимальный футер');
                                    
                                    // Вычисляем итоговую сумму из данных
                                    let totalCost = 0;
                                    if (sheet.data && Array.isArray(sheet.data)) {
                                        totalCost = sheet.data.reduce((sum, row) => {
                                            if (row && typeof row.cost === 'number') {
                                                return sum + row.cost;
                                            }
                                            return sum;
                                        }, 0);
                                    }
                                    
                                    // Создаем футер с итоговой строкой
                                    sheet.footer = {
                                        items: [{
                                            _type: 'grand_total',
                                            is_grand_total: true,
                                            title: 'ИТОГО:',
                                            cost: totalCost,
                                            client_cost: totalCost
                                        }]
                                    };
                                }
                            } else {
                                // Если данных нет, создаем пустой футер
                                sheet.footer = {
                                    items: [{
                                        _type: 'grand_total',
                                        is_grand_total: true,
                                        title: 'ИТОГО:',
                                        cost: 0,
                                        client_cost: 0
                                    }]
                                };
                            }
                        }
                    });
                }
                
                return result;
            };
            
            // Сохраняем оригинальную функцию загрузки данных
            const originalLoadData = window.jsonTableEditor.loadData;
            
            // Переопределяем функцию загрузки данных
            window.jsonTableEditor.loadData = function(jsonData) {
                console.log('📥 Проверка данных при загрузке (патч)');
                
                // Преобразуем строку JSON в объект, если это строка
                let data = jsonData;
                if (typeof jsonData === 'string') {
                    try {
                        data = JSON.parse(jsonData);
                    } catch (e) {
                        console.error('❌ Ошибка парсинга данных:', e);
                    }
                }
                
                // Исправляем данные перед загрузкой
                if (data && data.sheets) {
                    data.sheets.forEach((sheet, index) => {
                        // Проверяем наличие футера в новом формате
                        let hasValidFooter = sheet.footer && sheet.footer.items && Array.isArray(sheet.footer.items) && sheet.footer.items.length > 0;
                        
                        // Проверяем наличие футера в старом формате
                        let hasOldFooterFormat = Array.isArray(sheet.footer) && sheet.footer.length > 0;
                        
                        if (!hasValidFooter && !hasOldFooterFormat) {
                            console.log(`⚠️ Отсутствует корректный футер в загружаемых данных для листа ${index + 1}`);
                            
                            // Ищем строки итогов
                            const footerItems = [];
                            const regularItems = [];
                            
                            if (sheet.data && Array.isArray(sheet.data)) {
                                sheet.data.forEach(row => {
                                    if (row && (row._type === 'grand_total' || row.is_grand_total === true)) {
                                        footerItems.push(row);
                                    } else {
                                        regularItems.push(row);
                                    }
                                });
                                
                                // Если нашли строки футера, обновляем структуру
                                if (footerItems.length > 0) {
                                    console.log(`🔧 Извлечено ${footerItems.length} строк для футера из данных`);
                                    sheet.data = regularItems;
                                    sheet.footer = { items: footerItems };
                                } else {
                                    // Создаем минимальный футер
                                    console.log('🔧 Создаем минимальный футер при загрузке');
                                    sheet.footer = {
                                        items: [{
                                            _type: 'grand_total',
                                            is_grand_total: true,
                                            title: 'ИТОГО ПО СМЕТЕ:',
                                            cost: 0,
                                            client_cost: 0
                                        }]
                                    };
                                }
                            }
                        } else if (hasOldFooterFormat) {
                            // Преобразуем старый формат в новый
                            console.log(`🔄 Преобразование устаревшего формата футера для листа ${index + 1}`);
                            const oldFooter = sheet.footer;
                            sheet.footer = {
                                items: oldFooter
                            };
                            console.log(`✅ Футер для листа ${index + 1} преобразован, строк: ${sheet.footer.items.length}`);
                        } else {
                            console.log(`✅ Футер для листа ${index + 1} в порядке, строк: ${sheet.footer.items.length}`);
                        }
                    });
                }
                
                // Вызываем оригинальную функцию с исправленными данными
                return originalLoadData.call(this, data);
            };
            
            console.log('✅ Патч для исправления футера успешно применен');
        }
    });
})();
