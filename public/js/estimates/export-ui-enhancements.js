/**
 * Улучшенная система экспорта смет с пользовательскими уведомлениями
 * Версия: 1.1
 */

class EstimateExportManager {
    constructor() {
        this.isExporting = false;
        this.queue = [];
        this.init();
    }

    init() {
        console.log('🚀 EstimateExportManager инициализирован');
        this.addExportEventListeners();
        this.createNotificationContainer();
    }

    createNotificationContainer() {
        if (!document.getElementById('export-notifications')) {
            const container = document.createElement('div');
            container.id = 'export-notifications';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1100;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('export-notifications');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.style.cssText = `
            margin-bottom: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        `;
        
        const icon = this.getIconForType(type);
        notification.innerHTML = `
            <i class="${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        container.appendChild(notification);

        // Автоматическое скрытие
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    }

    getIconForType(type) {
        const icons = {
            'info': 'fas fa-info-circle',
            'success': 'fas fa-check-circle',
            'warning': 'fas fa-exclamation-triangle',
            'danger': 'fas fa-exclamation-circle'
        };
        return icons[type] || icons.info;
    }

    addExportEventListeners() {
        // Обработчики для всех ссылок экспорта
        document.addEventListener('click', (e) => {
            const target = e.target.closest('a[href*="/export"]');
            if (target) {
                this.handleExportClick(e, target);
            }
        });
    }

    handleExportClick(event, link) {
        const href = link.getAttribute('href');
        if (!href) return;

        // Определяем тип экспорта
        let exportType = 'Смета';
        let format = 'Excel';
        let version = 'полная версия';

        if (href.includes('pdf')) {
            format = 'PDF';
        }

        if (href.includes('client')) {
            version = 'для заказчика';
        } else if (href.includes('contractor')) {
            version = 'для мастера';
        }

        // Показываем уведомление
        this.showNotification(
            `Начинается загрузка: ${exportType} (${version}) в формате ${format}`,
            'info',
            3000
        );

        // Добавляем визуальную обратную связь
        link.style.opacity = '0.6';
        link.style.pointerEvents = 'none';

        // Восстанавливаем состояние через 3 секунды
        setTimeout(() => {
            link.style.opacity = '';
            link.style.pointerEvents = '';
        }, 3000);

        // Через некоторое время показываем уведомление об успехе
        setTimeout(() => {
            this.showNotification(
                `Файл готов к загрузке: ${exportType} (${version}) в формате ${format}`,
                'success',
                4000
            );
        }, 1500);
    }

    // Метод для массового экспорта
    async exportMultiple(estimateIds, format = 'excel') {
        if (this.isExporting) {
            this.showNotification('Экспорт уже выполняется. Дождитесь завершения.', 'warning');
            return;
        }

        this.isExporting = true;
        let completed = 0;
        const total = estimateIds.length;

        this.showNotification(`Начинается массовый экспорт ${total} смет в формате ${format.toUpperCase()}`, 'info');

        for (const id of estimateIds) {
            try {
                const url = format === 'excel' 
                    ? `/partner/estimates/${id}/export`
                    : `/partner/estimates/${id}/export-pdf`;
                
                // Создаем скрытую ссылку для загрузки
                const link = document.createElement('a');
                link.href = url;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                completed++;
                this.showNotification(`Экспорт ${completed}/${total} завершен`, 'info', 2000);

                // Задержка между загрузками
                await this.delay(1000);

            } catch (error) {
                console.error(`Ошибка экспорта сметы ${id}:`, error);
                this.showNotification(`Ошибка при экспорте сметы ${id}`, 'danger');
            }
        }

        this.isExporting = false;
        this.showNotification(`Массовый экспорт завершен! Загружено ${completed} файлов.`, 'success');
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // Прогресс бар для длительных операций
    showExportProgress() {
        const existing = document.getElementById('export-progress');
        if (existing) existing.remove();

        const progressContainer = document.createElement('div');
        progressContainer.id = 'export-progress';
        progressContainer.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1200;
            min-width: 300px;
            text-align: center;
        `;

        progressContainer.innerHTML = `
            <div class="mb-3">
                <i class="fas fa-download fa-2x text-primary"></i>
            </div>
            <h5>Экспорт смет</h5>
            <p class="text-muted">Подготовка файлов для загрузки...</p>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 100%"></div>
            </div>
        `;

        document.body.appendChild(progressContainer);
    }

    hideExportProgress() {
        const progress = document.getElementById('export-progress');
        if (progress) {
            progress.remove();
        }
    }
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    window.estimateExportManager = new EstimateExportManager();
    console.log('✅ EstimateExportManager готов к работе');
});

// Экспорт для глобального использования
window.EstimateExportManager = EstimateExportManager;
