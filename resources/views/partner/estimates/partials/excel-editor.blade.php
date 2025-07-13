<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>Редактирование сметы</div>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="recalcButton" title="Пересчитать формулы">
                <i class="fas fa-calculator"></i> Пересчитать формулы
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Контейнер для выбора листов Excel -->
        <div class="bg-light border-bottom p-2 d-flex justify-content-between align-items-center" id="sheetTabsContainer">
            <div id="sheetTabs" class="overflow-auto flex-grow-1"></div>
            <div class="ms-2">
                <button type="button" class="btn btn-sm btn-outline-success" id="addSheetBtn" title="Добавить лист">
                    <i class="fas fa-plus"></i> Лист
                </button>
            </div>
        </div>
        
        <!-- Подсказка по использованию контекстного меню -->
        <div class="alert alert-info m-2 d-flex align-items-center" id="contextMenuHint">
            <i class="fas fa-info-circle me-2"></i>
            <div>
                Используйте <strong>правую кнопку мыши</strong> для вызова контекстного меню со всеми доступными действиями
                <button type="button" class="btn-close ms-auto" aria-label="Close" onclick="document.getElementById('contextMenuHint').style.display='none'"></button>
            </div>
        </div>

        <!-- Индикатор загрузки -->
        <div id="loadingIndicator" class="position-absolute top-50 start-50 translate-middle bg-white p-3 rounded shadow d-flex flex-column align-items-center justify-content-center" style="display: none; z-index: 1000; min-width: 150px; min-height: 150px;">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2">Загрузка...</div>
            <!-- Кнопка принудительного скрытия (появляется через 10 секунд) -->
            <button type="button" id="forceHideBtn" class="btn btn-sm btn-outline-danger mt-2" style="display: none;" onclick="forceHideAllLoaders()">
                <i class="fas fa-times"></i> Скрыть
            </button>
        </div>

        <!-- Контейнер для редактора Excel -->
        <div id="excelEditor" style=" height: 100vh; width: 100%; overflow: hidden;"></div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <div>
            <span id="lastSavedTime" class="text-muted"></span>
        </div>
        <div>
            <button type="button" id="saveJsonButton" class="btn btn-outline-success me-2" title="Сохранить данные в JSON">
                <i class="fas fa-code me-1"></i>Сохранить JSON
            </button>
            <button type="button" id="saveEstimateButton" class="btn btn-primary save-estimate-button">
                <i class="fas fa-save me-1"></i>{{ isset($estimate) ? 'Сохранить смету' : 'Создать смету' }}
            </button>
        </div>
    </div>
</div>
<script>
// Функция для отображения/скрытия индикатора загрузки
function showLoading(show, message = 'Загрузка...') {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        if (show) {
            loadingIndicator.style.display = 'flex';
            loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Сохранение...</div><button type="button" id="forceHideBtn" class="btn btn-sm btn-outline-danger mt-2" style="display: none;" onclick="forceHideAllLoaders()"><i class="fas fa-times"></i> Скрыть</button>';
            
            // Показываем кнопку принудительного скрытия через 10 секунд
            setTimeout(() => {
                const btn = document.getElementById('forceHideBtn');
                if (btn && loadingIndicator.style.display !== 'none') {
                    btn.style.display = 'block';
                }
            }, 10000);
        } else {
            loadingIndicator.style.setProperty('display', 'none', 'important');
        }
    }
    
    // Также проверяем наличие динамически созданного загрузчика
    const excelLoader = document.getElementById('excelLoader');
    if (excelLoader && !show) {
        excelLoader.remove();
    }
    
    // Управляем прозрачностью контейнера редактора
    const container = document.getElementById('excelEditor');
    if (container) {
        container.style.opacity = show ? '0.5' : '1';
    }
}

// Функция принудительной очистки всех индикаторов загрузки
function forceHideAllLoaders() {
    console.log('Принудительная очистка всех загрузчиков');
    
    // Скрываем основной индикатор
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.style.setProperty('display', 'none', 'important');
    }
    
    // Удаляем динамический загрузчик
    const excelLoader = document.getElementById('excelLoader');
    if (excelLoader) {
        excelLoader.remove();
    }
    
    // Восстанавливаем прозрачность
    const container = document.getElementById('excelEditor');
    if (container) {
        container.style.opacity = '1';
    }
    
    // Ищем и удаляем все элементы со спиннерами
    document.querySelectorAll('.spinner-border').forEach(spinner => {
        const parent = spinner.closest('[id*="loading"], [id*="loader"]');
        if (parent) {
            parent.style.setProperty('display', 'none', 'important');
        }
    });
    
    // Дополнительно скрываем все элементы с loading в ID или классе
    document.querySelectorAll('[id*="loading"], [class*="loading"], [id*="loader"], [class*="loader"]').forEach(element => {
        if (element.style) {
            element.style.setProperty('display', 'none', 'important');
        }
    });
}

// Делаем функцию доступной глобально
window.forceHideAllLoaders = forceHideAllLoaders;

// Обработчик кнопки сохранения JSON
const saveJsonBtn = document.getElementById('saveJsonButton');
if (saveJsonBtn) {
    saveJsonBtn.addEventListener('click', function() {
        // Если компонент для сохранения JSON доступен
        if (window.estimateJsonSaver) {
            // Показываем индикатор загрузки
            showLoading(true, 'Сохранение JSON...');
            
            // Пересчитываем формулы перед сохранением, если такая возможность есть
            if (window.ExcelFormulaSystem && typeof window.ExcelFormulaSystem.recalculateAll === 'function') {
                window.ExcelFormulaSystem.recalculateAll();
            }
            
            // Сохраняем JSON данные
            window.estimateJsonSaver.save()
                .then(() => {
                    // Скрываем индикатор загрузки
                    showLoading(false);
                    
                    // Обновляем время последнего сохранения
                    const lastSavedTimeElement = document.getElementById('lastSavedTime');
                    if (lastSavedTimeElement) {
                        const now = new Date();
                        lastSavedTimeElement.textContent = 'Последнее сохранение: ' + 
                            now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    }
                })
                .catch(error => {
                    console.error('Ошибка при сохранении JSON:', error);
                    showLoading(false);
                    
                    // Показываем уведомление об ошибке
                    if (typeof showToast === 'function') {
                        showToast('error', 'Ошибка сохранения JSON: ' + error.message);
                    }
                });
        } else {
            console.error('Компонент для сохранения JSON не найден');
            
            // Показываем уведомление об ошибке
            if (typeof showToast === 'function') {
                showToast('error', 'Компонент для сохранения JSON не найден');
            }
        }
    });
}

// Обработчик кнопки сохранения
const submitBtn = document.getElementById('submitBtn');
if (submitBtn) {
    submitBtn.addEventListener('click', function() {
        if (typeof saveExcelToServer === 'function') {
            saveExcelToServer();
            
            // Устанавливаем таймер принудительного скрытия индикатора через 30 секунд
            setTimeout(() => {
                console.log('Принудительное скрытие индикатора загрузки через таймаут');
                showLoading(false);
                if (typeof window.forceHideAllLoaders === 'function') {
                    window.forceHideAllLoaders();
                }
            }, 30000);
        } else {
            console.error('Function saveExcelToServer is not defined');
        }
    });
}

// Обработчики кнопок добавления строк и разделов удалены,
// теперь для этих действий используется контекстное меню
// при клике правой кнопкой мыши на строке

// Функция пересчета формул (для вызова из других мест)
window.recalculateAllFormulas = function() {
    console.log('Запуск пересчета формул');
    if (typeof window.forceRecalculateAll === 'function') {
        showLoading(true, 'Пересчет всех формул...');
        setTimeout(() => {
            try {
                window.forceRecalculateAll();
                console.log('✅ Пересчет выполнен успешно');
            } catch (error) {
                console.error('❌ Ошибка при пересчете формул:', error);
            } finally {
                showLoading(false);
            }
        }, 100);
    } else {
        console.error('Функция forceRecalculateAll не найдена');
        // Пытаемся использовать старую функцию, если новая не найдена
        if (typeof window.recalculateAll === 'function') {
            window.recalculateAll();
        }
    }
};

// Инициализация после загрузки страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('Инициализация контекстного меню и других компонентов...');
    
    // Обработчик кнопки пересчета формул
    const recalcButton = document.getElementById('recalcButton');
    if (recalcButton) {
        recalcButton.addEventListener('click', function() {
            if (window.recalculateAllFormulas && typeof window.recalculateAllFormulas === 'function') {
                window.recalculateAllFormulas();
            } else {
                window.recalculateAllFormulasUI();
            }
        });
    }
    
    // Инициализация функции пересчета формул (доступна глобально)
    window.recalculateAllFormulasUI = function() {
        if (window.ExcelFormulaSystem && window.ExcelFormulaSystem.recalculate) {
            showLoading(true, 'Пересчет формул...');
            try {
                window.ExcelFormulaSystem.recalculate();
                // Сохраняем изменения после пересчета
                window.ExcelFormulaSystem.save(false)
                    .catch(err => console.error('Ошибка при сохранении после пересчета:', err))
                        .finally(() => showLoading(false));
                } catch (error) {
                    console.error('Ошибка при пересчете формул:', error);
                    showLoading(false);
                    alert('Произошла ошибка при пересчете формул: ' + error.message);
                }
            } else {
                alert('Система формул не инициализирована');
            }
        });
    }
});

// Удаляем обработчик кнопки добавления листа из этого файла,
// так как он теперь добавляется только в excel-editor-fixed.js

// Кнопка сохранения в боковой панели (если существует)
const saveExcelBtn = document.getElementById('saveExcelBtn');
if (saveExcelBtn) {
    saveExcelBtn.addEventListener('click', function() {
        if (typeof saveExcelToServer === 'function') {
            saveExcelToServer();
        } else {
            console.error('Function saveExcelToServer is not defined');
        }
    });
}

// Функция обновления индикатора статуса изменений
function updateStatusIndicator() {
    const statusIndicator = document.getElementById('statusIndicator');
    if (statusIndicator) {
        statusIndicator.style.display = isFileModified ? 'block' : 'none';
    }
    
    // Обновляем надписи на кнопке сохранения
    const saveBtn = document.getElementById('saveExcelBtn');
    if (saveBtn) {
        if (isFileModified) {
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения*';
            saveBtn.classList.add('btn-primary');
            saveBtn.classList.remove('btn-outline-primary');
        } else {
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения';
            saveBtn.classList.add('btn-outline-primary');
            saveBtn.classList.remove('btn-primary');
        }
    }
}

// Дополнительная защита от зависшего индикатора загрузки
// Автоматически скрываем загрузчик через 30 секунд максимум
let loadingTimeoutId;

// Переопределяем showLoading для добавления таймаута
const originalShowLoading = window.showLoading || showLoading;
window.showLoading = function(show) {
    // Вызываем оригинальную функцию
    originalShowLoading(show);
    
    if (show) {
        // Устанавливаем таймаут на 30 секунд
        if (loadingTimeoutId) {
            clearTimeout(loadingTimeoutId);
        }
        loadingTimeoutId = setTimeout(() => {
            console.warn('Принудительное скрытие загрузчика по таймауту (30 секунд)');
            forceHideAllLoaders();
        }, 30000);
    } else {
        // Отменяем таймаут если загрузка завершена
        if (loadingTimeoutId) {
            clearTimeout(loadingTimeoutId);
            loadingTimeoutId = null;
        }
    }
};

// Также добавляем дополнительную проверку при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Скрываем все возможные загрузчики при загрузке страницы
    setTimeout(() => {
        if (typeof forceHideAllLoaders === 'function') {
            forceHideAllLoaders();
        }
    }, 1000);
});
</script>
