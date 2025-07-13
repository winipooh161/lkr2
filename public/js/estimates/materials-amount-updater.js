/**
 * Модуль для автоматического обновления поля materials_amount в проекте
 * при изменении смет материалов
 * 
 * @version 1.0
 */

class MaterialsAmountUpdater {
    constructor() {
        this.projectId = null;
        this.estimateId = null;
        this.estimateType = null;
        this.init();
    }
    
    /**
     * Инициализация модуля
     */
    init() {
        // Получаем идентификаторы из метатегов или глобальных переменных
        this.estimateId = window.estimateId || this.getMetaContent('estimate-id');
        this.estimateType = window.estimateType || this.getMetaContent('estimate-type');
        this.projectId = window.projectId || this.getMetaContent('project-id');
        
        if (!this.estimateId) {
            console.warn('MaterialsAmountUpdater: estimate ID не найден');
            return;
        }
        
        // Привязываем обработчики событий
        this.bindEvents();
        
        console.log('✅ MaterialsAmountUpdater инициализирован', {
            estimateId: this.estimateId,
            estimateType: this.estimateType,
            projectId: this.projectId
        });
    }
    
    /**
     * Получение содержимого мета-тега
     */
    getMetaContent(name) {
        const meta = document.querySelector(`meta[name="${name}"]`);
        return meta ? meta.getAttribute('content') : null;
    }
    
    /**
     * Привязка обработчиков событий
     */
    bindEvents() {
        // Слушаем события сохранения данных в редакторе смет
        document.addEventListener('estimate-data-saved', (event) => {
            this.handleEstimateDataSaved(event);
        });
        
        // Слушаем изменения в таблице Handsontable
        document.addEventListener('formula-recalculated', (event) => {
            this.handleFormulaRecalculated(event);
        });
        
        // Слушаем завершение автосохранения
        document.addEventListener('auto-save-completed', (event) => {
            this.handleAutoSaveCompleted(event);
        });
    }
    
    /**
     * Обработчик события сохранения данных сметы
     */
    handleEstimateDataSaved(event) {
        const data = event.detail;
        
        if (data && data.estimate && data.estimate.total_amount !== undefined) {
            this.updateProjectMaterialsAmount(data.estimate.total_amount, data.estimate.type);
        }
    }
    
    /**
     * Обработчик пересчета формул
     */
    handleFormulaRecalculated(event) {
        const data = event.detail;
        
        if (data && data.totals) {
            let totalAmount = 0;
            
            // Для смет материалов используем materials_total
            if (this.estimateType === 'materials') {
                totalAmount = data.totals.client_materials_total || data.totals.materials_total || 0;
            } else {
                totalAmount = data.totals.client_grand_total || data.totals.grand_total || 0;
            }
            
            // Обновляем сумму в проекте с задержкой для избежания частых запросов
            this.debounceUpdateProjectAmount(totalAmount);
        }
    }
    
    /**
     * Обработчик завершения автосохранения
     */
    handleAutoSaveCompleted(event) {
        const data = event.detail;
        
        if (data && data.success && data.estimate) {
            this.updateProjectMaterialsAmount(data.estimate.total_amount, data.estimate.type);
        }
    }
    
    /**
     * Обновление суммы материалов в проекте
     */
    async updateProjectMaterialsAmount(totalAmount, estimateType = null) {
        try {
            if (!this.estimateId || totalAmount === undefined) {
                return;
            }
            
            const type = estimateType || this.estimateType || 'main';
            
            const response = await fetch(`/partner/estimates/${this.estimateId}/update-project-amount`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    type: type,
                    total_amount: totalAmount
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Сумма проекта обновлена:', {
                    type: type,
                    amount: totalAmount
                });
                
                // Генерируем событие для уведомления других компонентов
                document.dispatchEvent(new CustomEvent('project-amount-updated', {
                    detail: {
                        type: type,
                        amount: totalAmount,
                        estimateId: this.estimateId
                    }
                }));
                
                // Обновляем поля на странице, если они есть
                this.updateUIFields(type, totalAmount);
            } else {
                console.warn('⚠️ Ошибка обновления суммы проекта:', result.message);
            }
        } catch (error) {
            console.error('❌ Ошибка при обновлении суммы проекта:', error);
        }
    }
    
    /**
     * Обновление полей интерфейса
     */
    updateUIFields(type, amount) {
        if (type === 'materials') {
            const materialsAmountField = document.getElementById('materials_amount');
            if (materialsAmountField) {
                materialsAmountField.value = parseFloat(amount).toFixed(2);
                
                // Добавляем визуальную индикацию обновления
                materialsAmountField.classList.add('field-updated');
                setTimeout(() => {
                    materialsAmountField.classList.remove('field-updated');
                }, 2000);
            }
        } else if (type === 'main') {
            const workAmountField = document.getElementById('work_amount');
            if (workAmountField) {
                workAmountField.value = parseFloat(amount).toFixed(2);
                
                // Добавляем визуальную индикацию обновления
                workAmountField.classList.add('field-updated');
                setTimeout(() => {
                    workAmountField.classList.remove('field-updated');
                }, 2000);
            }
        }
        
        // Пересчитываем общую сумму, если есть соответствующая функция
        if (typeof calculateTotal === 'function') {
            calculateTotal();
        }
    }
    
    /**
     * Debounced версия обновления суммы проекта
     */
    debounceUpdateProjectAmount(amount) {
        if (this.updateTimeout) {
            clearTimeout(this.updateTimeout);
        }
        
        this.updateTimeout = setTimeout(() => {
            this.updateProjectMaterialsAmount(amount);
        }, 1000); // Задержка 1 секунда
    }
}

// Инициализируем модуль при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    window.MaterialsAmountUpdater = new MaterialsAmountUpdater();
});

// Экспортируем для глобального использования
window.MaterialsAmountUpdater = MaterialsAmountUpdater;
