/**
 * Скрипт для диагностики проблемы экспорта смет
 * Проверяет структуру данных разделов и элементов
 * 
 * @version 1.0
 */

console.log('🔍 ДИАГНОСТИКА ЭКСПОРТА СМЕТ - ИСПРАВЛЕНИЕ ПРОБЛЕМЫ is_header');
console.log('=' .repeat(80));

// Функция для анализа данных сметы
function analyzeEstimateData() {
    // Проверяем, есть ли данные в глобальной области
    if (typeof window.estimateData !== 'undefined') {
        console.log('✅ Найдены данные сметы в window.estimateData');
        analyzeSectionsData(window.estimateData);
    } else {
        console.log('⚠️ window.estimateData не найден, пытаемся загрузить данные...');
        loadEstimateDataForAnalysis();
    }
}

// Функция анализа структуры разделов
function analyzeSectionsData(data) {
    console.log('📊 АНАЛИЗ СТРУКТУРЫ ДАННЫХ:');
    console.log('------------------------------');
    
    if (data.sections && Array.isArray(data.sections)) {
        console.log(`✅ Найдено разделов: ${data.sections.length}`);
        
        data.sections.forEach((section, index) => {
            console.log(`\n📁 Раздел ${index + 1}: "${section.title || 'Без названия'}"`);
            console.log(`   ID: ${section.id || 'не указан'}`);
            console.log(`   Тип: ${section.type || 'не указан'}`);
            
            if (section.items && Array.isArray(section.items)) {
                console.log(`   📝 Элементов: ${section.items.length}`);
                
                section.items.forEach((item, itemIndex) => {
                    const hasIsHeader = 'is_header' in item;
                    const isHeaderValue = item.is_header;
                    const shouldBeIncluded = !hasIsHeader || isHeaderValue === false;
                    
                    console.log(`      ${itemIndex + 1}. "${item.name || 'Без названия'}"`);
                    console.log(`         📋 has_is_header: ${hasIsHeader}`);
                    console.log(`         📋 is_header: ${isHeaderValue}`);
                    console.log(`         📋 will_be_exported: ${shouldBeIncluded ? '✅ ДА' : '❌ НЕТ'}`);
                    
                    if (!shouldBeIncluded) {
                        console.log(`         ⚠️ ПРОБЛЕМА: Этот элемент НЕ будет экспортирован!`);
                    }
                });
            } else {
                console.log(`   ❌ Нет элементов в разделе`);
            }
        });
    } else {
        console.log('❌ Разделы не найдены в данных');
    }
    
    console.log('\n' + '=' .repeat(80));
    console.log('🎯 РЕЗЮМЕ ДИАГНОСТИКИ:');
    
    const problemItems = [];
    if (data.sections) {
        data.sections.forEach((section, sectionIndex) => {
            if (section.items) {
                section.items.forEach((item, itemIndex) => {
                    const hasIsHeader = 'is_header' in item;
                    const isHeaderValue = item.is_header;
                    const shouldBeIncluded = !hasIsHeader || isHeaderValue === false;
                    
                    if (!shouldBeIncluded) {
                        problemItems.push({
                            section: section.title,
                            item: item.name,
                            is_header: isHeaderValue
                        });
                    }
                });
            }
        });
    }
    
    if (problemItems.length > 0) {
        console.log(`❌ Найдено ${problemItems.length} элементов, которые НЕ будут экспортированы:`);
        problemItems.forEach(item => {
            console.log(`   - "${item.item}" в разделе "${item.section}" (is_header: ${item.is_header})`);
        });
        console.log('\n💡 РЕКОМЕНДАЦИЯ: Проверьте код экспорта в EstimateJsonExportService.php');
        console.log('   Условие должно быть: (!isset($item["is_header"]) || $item["is_header"] === false)');
    } else {
        console.log('✅ Все элементы должны правильно экспортироваться!');
    }
}

// Функция загрузки данных для анализа
async function loadEstimateDataForAnalysis() {
    try {
        // Пытаемся получить ID сметы из URL
        const urlParts = window.location.pathname.split('/');
        const estimateIdIndex = urlParts.findIndex(part => part === 'estimates') + 1;
        const estimateId = urlParts[estimateIdIndex];
        
        if (!estimateId) {
            console.log('❌ Не удается определить ID сметы из URL');
            return;
        }
        
        console.log(`🔄 Загружаем данные для сметы ID: ${estimateId}`);
        
        const response = await fetch(`/partner/estimates/${estimateId}/json-data`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        console.log('✅ Данные успешно загружены');
        analyzeSectionsData(data);
        
    } catch (error) {
        console.log(`❌ Ошибка загрузки данных: ${error.message}`);
        console.log('💡 Попробуйте выполнить анализ на странице редактирования сметы');
    }
}

// Функция для тестирования экспорта
function testExportUrls() {
    const urlParts = window.location.pathname.split('/');
    const estimateIdIndex = urlParts.findIndex(part => part === 'estimates') + 1;
    const estimateId = urlParts[estimateIdIndex];
    
    if (!estimateId) {
        console.log('❌ Не удается определить ID сметы для тестирования экспорта');
        return;
    }
    
    console.log('\n🚀 ССЫЛКИ ДЛЯ ТЕСТИРОВАНИЯ ЭКСПОРТА:');
    console.log('=====================================');
    console.log(`📊 Excel полный: /partner/estimates/${estimateId}/export`);
    console.log(`📊 Excel клиент: /partner/estimates/${estimateId}/export-client`);
    console.log(`📊 Excel мастер: /partner/estimates/${estimateId}/export-contractor`);
    console.log(`📄 PDF полный: /partner/estimates/${estimateId}/export-pdf`);
    console.log(`📄 PDF клиент: /partner/estimates/${estimateId}/export-pdf-client`);
    console.log(`📄 PDF мастер: /partner/estimates/${estimateId}/export-pdf-contractor`);
    console.log('\n💡 Скопируйте ссылки и протестируйте экспорт после исправлений');
}

// Запускаем диагностику
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Модуль диагностики экспорта загружен');
    
    // Даем время другим скриптам загрузиться
    setTimeout(() => {
        analyzeEstimateData();
        testExportUrls();
    }, 1000);
});

// Экспортируем функции для ручного вызова
window.debugExportFix = {
    analyze: analyzeEstimateData,
    testUrls: testExportUrls
};

console.log('🛠️ Для диагностики используйте:');
console.log('   window.debugExportFix.analyze() - анализ данных');
console.log('   window.debugExportFix.testUrls() - ссылки для тестирования');
