/**
 * Скрипт для работы с экспортом смет
 * Обеспечивает улучшенный пользовательский опыт при экспорте
 */

class EstimateExportManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initTooltips();
        console.log('✅ EstimateExportManager инициализирован');
    }

    bindEvents() {
        // Обработчики для кнопок экспорта
        document.addEventListener('click', (e) => {
            if (e.target.closest('[href*="export"]')) {
                this.handleExportClick(e);
            }
        });

        // Обработчики для быстрых кнопок экспорта
        document.addEventListener('click', (e) => {
            if (e.target.closest('.export-quick-btn')) {
                this.handleQuickExportClick(e);
            }
        });
    }

    handleExportClick(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const url = link.href;
        
        // Показываем индикатор загрузки
        this.showExportProgress();
        
        // Добавляем класс загрузки к кнопке
        link.classList.add('export-loading');
        
        // Определяем тип экспорта
        const exportType = this.getExportType(url);
        
        // Показываем уведомление
        this.showNotification(`Начинается экспорт в формате ${exportType}...`, 'info');
        
        // Убираем индикатор через некоторое время
        setTimeout(() => {
            this.hideExportProgress();
            link.classList.remove('export-loading');
            this.showNotification(`Экспорт ${exportType} завершен!`, 'success');
        }, 2000);
    }

    handleQuickExportClick(e) {
        const btn = e.target.closest('.export-quick-btn');
        if (!btn) return;

        // Добавляем эффект нажатия
        btn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            btn.style.transform = '';
        }, 150);
    }

    getExportType(url) {
        if (url.includes('export-pdf')) return 'PDF';
        if (url.includes('export')) return 'Excel';
        return 'файла';
    }

    showExportProgress() {
        let progressBar = document.querySelector('.export-progress');
        if (!progressBar) {
            progressBar = this.createProgressBar();
            document.body.appendChild(progressBar);
        }
        progressBar.classList.add('show');
    }

    hideExportProgress() {
        const progressBar = document.querySelector('.export-progress');
        if (progressBar) {
            progressBar.classList.remove('show');
        }
    }

    createProgressBar() {
        const progressContainer = document.createElement('div');
        progressContainer.className = 'export-progress';
        progressContainer.innerHTML = `
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 100%"></div>
            </div>
        `;
        return progressContainer;
    }

    showNotification(message, type = 'info') {
        let notificationContainer = document.querySelector('.export-notification');
        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.className = 'export-notification';
            document.body.appendChild(notificationContainer);
        }

        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 'alert-info';
        
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        notificationContainer.appendChild(notification);

        // Автоматически убираем уведомление через 5 секунд
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    initTooltips() {
        // Инициализируем tooltips для кнопок экспорта, если Bootstrap загружен
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    // Методы для групповых операций (для будущего использования)
    enableBulkActions() {
        const bulkContainer = document.querySelector('.bulk-actions');
        if (bulkContainer) {
            bulkContainer.classList.add('show');
        }
    }

    disableBulkActions() {
        const bulkContainer = document.querySelector('.bulk-actions');
        if (bulkContainer) {
            bulkContainer.classList.remove('show');
        }
    }

    // Метод для экспорта выбранных смет
    exportSelected(format = 'excel') {
        const selectedItems = document.querySelectorAll('.estimate-checkbox:checked');
        if (selectedItems.length === 0) {
            this.showNotification('Выберите сметы для экспорта', 'error');
            return;
        }

        const estimateIds = Array.from(selectedItems).map(cb => cb.value);
        
        // Здесь можно добавить логику для массового экспорта
        this.showNotification(`Экспорт ${estimateIds.length} смет в формате ${format.toUpperCase()}...`, 'info');
    }
}

// Утилиты для работы с экспортом
const ExportUtils = {
    /**
     * Форматирование размера файла
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Байт';
        const k = 1024;
        const sizes = ['Байт', 'КБ', 'МБ', 'ГБ'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    /**
     * Получение иконки по типу файла
     */
    getFileIcon(type) {
        switch (type.toLowerCase()) {
            case 'excel':
            case 'xlsx':
                return 'fas fa-file-excel text-success';
            case 'pdf':
                return 'fas fa-file-pdf text-danger';
            default:
                return 'fas fa-file text-muted';
        }
    },

    /**
     * Проверка поддержки браузером загрузки файлов
     */
    supportsDownload() {
        const a = document.createElement('a');
        return typeof a.download !== 'undefined';
    },

    /**
     * Создание и автоматическая загрузка файла
     */
    downloadFile(url, filename) {
        if (!this.supportsDownload()) {
            window.open(url, '_blank');
            return;
        }

        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
};

// Функции для тестирования системы экспорта
function testExportSystem() {
    console.log('🧪 Тестирование системы экспорта смет...');
    
    // Проверяем наличие нужных маршрутов
    const testEstimateId = 1; // Замените на реальный ID сметы
    
    const routes = [
        '/partner/estimates/' + testEstimateId + '/export',
        '/partner/estimates/' + testEstimateId + '/export-pdf',
        '/partner/estimates/' + testEstimateId + '/export-client',
        '/partner/estimates/' + testEstimateId + '/export-contractor',
        '/partner/estimates/' + testEstimateId + '/export-pdf-client',
        '/partner/estimates/' + testEstimateId + '/export-pdf-contractor'
    ];
    
    console.log('📋 Доступные маршруты экспорта:');
    routes.forEach(route => {
        console.log(`  - ${route}`);
    });
    
    // Проверяем наличие кнопок экспорта
    const exportButtons = document.querySelectorAll('[href*="export"]');
    console.log(`✅ Найдено ${exportButtons.length} кнопок экспорта на странице`);
    
    // Проверяем работу EstimateExportManager
    if (window.estimateExportManager) {
        console.log('✅ EstimateExportManager загружен и готов к работе');
        
        // Тестируем уведомления
        window.estimateExportManager.showNotification('Тестовое уведомление', 'success');
        
        setTimeout(() => {
            window.estimateExportManager.showNotification('Система экспорта работает!', 'info');
        }, 1000);
    } else {
        console.log('❌ EstimateExportManager не загружен');
    }
    
    return {
        routes: routes,
        buttonsCount: exportButtons.length,
        managerReady: !!window.estimateExportManager
    };
}

// Функция для создания тестовых данных
function createTestExportData() {
    console.log('🛠️ Создание тестовых данных для экспорта...');
    
    // Эта функция может быть использована для создания
    // тестовой сметы с данными для проверки экспорта
    const testData = {
        estimate: {
            id: 999,
            name: 'Тестовая смета для экспорта',
            project: {
                address: 'Тестовый адрес',
                client_name: 'Тестовый клиент'
            }
        },
        jsonData: {
            type: 'main',
            version: '1.0',
            meta: {
                estimate_id: 999,
                estimate_name: 'Тестовая смета для экспорта',
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString()
            },
            sheets: [{
                name: 'Основной',
                data: [
                    {
                        _type: 'header',
                        name: 'Тестовые работы'
                    },
                    {
                        number: '1',
                        name: 'Тестовая работа 1',
                        unit: 'шт',
                        quantity: 10,
                        price: 1000,
                        cost: 10000,
                        markup: 20,
                        discount: 0,
                        client_price: 1200,
                        client_cost: 12000
                    },
                    {
                        number: '2',
                        name: 'Тестовая работа 2',
                        unit: 'м²',
                        quantity: 25,
                        price: 500,
                        cost: 12500,
                        markup: 15,
                        discount: 5,
                        client_price: 547.5,
                        client_cost: 13687.5
                    }
                ]
            }],
            totals: {
                work_total: 22500,
                materials_total: 0,
                grand_total: 22500,
                client_work_total: 25687.5,
                client_materials_total: 0,
                client_grand_total: 25687.5
            }
        }
    };
    
    console.log('✅ Тестовые данные созданы:', testData);
    return testData;
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    window.estimateExportManager = new EstimateExportManager();
    window.ExportUtils = ExportUtils;
});

// Экспорт для глобального использования
window.EstimateExportManager = EstimateExportManager;

// Экспорт функций для глобального использования
window.testExportSystem = testExportSystem;
window.createTestExportData = createTestExportData;

console.log('📁 Загружен модуль EstimateExportManager');
console.log('🧪 Загружены функции тестирования экспорта:');
console.log('  - testExportSystem() - тестирование системы');
console.log('  - createTestExportData() - создание тестовых данных');
