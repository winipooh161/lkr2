/**
 * Исправление для работы с вложенными вкладками в проектах
 * Предотвращает конфликты между основными вкладками проекта и вложенными вкладками
 * внутри вкладок, таких как "Фото"
 */
document.addEventListener('DOMContentLoaded', function() {
    // Функция для обработки вложенных вкладок
    function initializeNestedTabs() {
        // Находим все вложенные вкладки (внутри конкретных разделов)
        const nestedTabContainers = document.querySelectorAll('.tab-pane .nav-tabs');
        
        nestedTabContainers.forEach(function(container) {
            // Находим все кнопки вложенных вкладок
            const nestedButtons = container.querySelectorAll('.nav-link');
            
            nestedButtons.forEach(function(button) {
                // Удаляем существующие обработчики (для предотвращения дублирования)
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                // Добавляем новый обработчик клика
                newButton.addEventListener('click', function(e) {
                    e.stopPropagation(); // Останавливаем всплытие события
                    e.preventDefault(); // Предотвращаем действие по умолчанию
                    
                    // Получаем целевую вкладку
                    const targetId = this.getAttribute('data-bs-target');
                    if (!targetId) {
                        console.error('Не найден атрибут data-bs-target у кнопки вложенной вкладки');
                        return;
                    }
                    
                    const targetPane = document.querySelector(targetId);
                    if (!targetPane) {
                        console.error(`Не найдена панель для вложенной вкладки: ${targetId}`);
                        return;
                    }
                    
                    // Деактивируем все кнопки в этом контейнере
                    const tabContainer = this.closest('.nav-tabs');
                    if (tabContainer) {
                        tabContainer.querySelectorAll('.nav-link').forEach(btn => {
                            btn.classList.remove('active');
                            btn.setAttribute('aria-selected', 'false');
                        });
                    }
                    
                    // Деактивируем все панели вкладок
                    const tabContentContainer = targetPane.closest('.tab-content');
                    if (tabContentContainer) {
                        tabContentContainer.querySelectorAll('.tab-pane').forEach(pane => {
                            pane.classList.remove('show', 'active');
                        });
                    }
                    
                    // Активируем выбранную вкладку и панель
                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');
                    targetPane.classList.add('show', 'active');
                    
                    // Предотвращаем влияние на основные вкладки
                    setTimeout(() => {
                        // Находим родительскую вкладку, в которой находится вложенная вкладка
                        const parentTab = this.closest('.tab-pane');
                        if (parentTab) {
                            const parentTabId = parentTab.id;
                            const mainTabButton = document.querySelector(`[data-bs-target="#${parentTabId}"]`);
                            
                            if (parentTabId === 'photos' && mainTabButton && !mainTabButton.classList.contains('active')) {
                                // Для вкладки фото мы обеспечиваем активность родительской вкладки
                                console.log('Поддержание активности вкладки фото');
                            }
                        }
                    }, 0);
                });
            });
        });
    }
    
    // Запускаем инициализацию сразу после загрузки страницы
    initializeNestedTabs();
    
    // Повторно запускаем после каждого переключения основных вкладок
    document.querySelectorAll('#projectTabs [data-bs-toggle="tab"]').forEach(function(element) {
        element.addEventListener('shown.bs.tab', function() {
            setTimeout(initializeNestedTabs, 100);
        });
    });
    
    // Добавляем специальную обработку для вкладки "Фото"
    const photosTab = document.getElementById('photos-tab');
    if (photosTab) {
        photosTab.addEventListener('shown.bs.tab', function() {
            setTimeout(function() {
                // Повторно инициализируем вложенные вкладки на вкладке "Фото"
                const photoTabsContainer = document.getElementById('photoCategoriesTab');
                if (photoTabsContainer) {
                    console.log('Инициализация вкладок категорий фото');
                    const photoCategoriesTabs = photoTabsContainer.querySelectorAll('.nav-link');
                    
                    photoCategoriesTabs.forEach(function(tab) {
                        // Удаляем существующие обработчики
                        const newTab = tab.cloneNode(true);
                        tab.parentNode.replaceChild(newTab, tab);
                        
                        // Добавляем новый обработчик
                        newTab.addEventListener('click', function(e) {
                            e.stopPropagation();
                            
                            // Активируем таб и соответствующий контент
                            const targetId = this.getAttribute('data-bs-target');
                            const targetContent = document.querySelector(targetId);
                            
                            // Убираем активный класс у всех табов и контента
                            photoTabsContainer.querySelectorAll('.nav-link').forEach(t => {
                                t.classList.remove('active');
                                t.setAttribute('aria-selected', 'false');
                            });
                            
                            document.querySelectorAll('#photoCategoriesTabContent .tab-pane').forEach(p => {
                                p.classList.remove('show', 'active');
                            });
                            
                            // Активируем выбранный таб и его контент
                            this.classList.add('active');
                            this.setAttribute('aria-selected', 'true');
                            
                            if (targetContent) {
                                targetContent.classList.add('show', 'active');
                            }
                        });
                    });
                }
            }, 200);
        });
    }
});
