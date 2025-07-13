/**
 * Обработчик выпадающих меню для смет
 * Также загружает утилиты для исправления структуры данных
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Инициализация обработчиков выпадающих меню смет');
    
    // Загружаем утилиту для исправления структуры данных
    const fixScript = document.createElement('script');
    fixScript.src = '/js/estimates/fix-data-structure.js?v=' + Date.now();
    fixScript.onload = function() {
        console.log('🔧 Утилита исправления данных загружена');
        
        // Автоматически исправляем структуру если мы на странице редактирования
        if (window.location.pathname.includes('/estimates/') && 
            window.location.pathname.includes('/editor')) {
            console.log('🎯 Автоматическое исправление структуры данных...');
            
            // Небольшая задержка для загрузки основных скриптов
            setTimeout(() => {
                if (typeof window.fixCurrentEstimate === 'function') {
                    window.fixCurrentEstimate();
                }
            }, 2000);
        }
    };
    document.head.appendChild(fixScript);
});

console.log('✅ Модуль estimates-dropdowns загружен');