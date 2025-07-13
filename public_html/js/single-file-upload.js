/**
 * ЕДИНСТВЕННЫЙ скрипт для обработки загрузки файлов в проектах
 * Исправляет проблему с модальными окнами и предотвращает дублирование загрузок
 */

console.log('=== SINGLE FILE UPLOAD SCRIPT LOADING ===');
console.log('Script URL:', document.currentScript ? document.currentScript.src : 'unknown');
console.log('Page URL:', window.location.href);

// Проверяем, доступен ли Axios глобально, если нет - загружаем
if (typeof axios === 'undefined') {
    console.log('Axios not found, loading from CDN');
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js';
    script.async = true;
    script.onload = function() {
        console.log('Axios successfully loaded from CDN');
    };
    document.head.appendChild(script);
} else {
    console.log('Axios is already available globally');
}

// Глобальные переменные для предотвращения дублирования
window.fileUploadHandlers = window.fileUploadHandlers || {
    initialized: false,
    handledButtons: new Set(),
    activeUploads: new Set()
};

// Дополнительная защита от дублирования на уровне глобального объекта
if (window.fileUploadHandlersScriptLoaded) {
    console.warn('DUPLICATE SCRIPT LOAD DETECTED - Already loaded');
} else {
    window.fileUploadHandlersScriptLoaded = true;

document.addEventListener('DOMContentLoaded', function() {
    // Предотвращаем повторную инициализацию
    if (window.fileUploadHandlers.initialized) {
        console.log('File upload handlers already initialized, skipping');
        return;
    }
    
    console.log('Initializing single file upload handler');
    window.fileUploadHandlers.initialized = true;
    
    // Список всех модальных окон для загрузки файлов
    const uploadModals = [
        'uploadDesignModal', 
        'uploadSchemeModal', 
        'uploadDocumentModal', 
        'uploadContractModal', 
        'uploadOtherModal'
    ];
    
    // Исправляем видимость форм в модальных окнах при инициализации
    uploadModals.forEach(function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const form = modal.querySelector('form');
        if (!form) return;
        
        console.log('Setting up form visibility for modal:', modalId);
        
        // Убираем класс d-none с формы
        form.classList.remove('d-none');
        form.style.display = 'block';
        
        // Активируем кнопки
        const buttons = modal.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.disabled = false;
        });
    });
    
    // Обработчик для показа модальных окон
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('shown.bs.modal', function() {
            console.log('Modal shown:', modal.id);
            
            const form = modal.querySelector('form');
            if (!form) return;
            
            // Убираем класс d-none и устанавливаем display: block
            form.classList.remove('d-none');
            form.style.display = 'block';
            
            // Сбрасываем состояние прогресс-бара
            const progressContainer = modal.querySelector('.upload-progress');
            if (progressContainer) {
                progressContainer.classList.add('d-none');
                progressContainer.style.display = 'none';
            }
            
            // Активируем кнопки
            const buttons = modal.querySelectorAll('button');
            buttons.forEach(btn => {
                btn.disabled = false;
            });
        });
    });
    
    // ЕДИНСТВЕННЫЙ обработчик для всех кнопок загрузки файлов
    console.log('🔍 Searching for upload buttons...');
    const uploadButtons = document.querySelectorAll('.upload-file-btn');
    console.log('📊 Found ' + uploadButtons.length + ' upload buttons:', uploadButtons);
    
    uploadButtons.forEach((button, index) => {
        console.log(`🔘 Processing button ${index + 1}:`, button);
        
        // Создаем уникальный ID для кнопки
        const buttonId = button.id || 'upload_btn_' + Math.random().toString(36).substr(2, 9);
        if (!button.id) {
            button.id = buttonId;
            console.log('🏷️ Assigned new ID to button:', buttonId);
        }
        
        // Проверяем, был ли уже добавлен обработчик для этой кнопки
        if (window.fileUploadHandlers.handledButtons.has(buttonId)) {
            console.log('⏭️ Button already has handler, skipping:', buttonId);
            return;
        }
        
        // Отмечаем кнопку как обработанную
        window.fileUploadHandlers.handledButtons.add(buttonId);
        console.log('✅ Adding upload handler to button:', buttonId);
          button.addEventListener('click', function(e) {
            handleUploadButtonClick.call(this, e, buttonId);
        });
    });
    
    // Обработчик для динамически загружаемых кнопок (при переключении вкладок)
    function initializeUploadButtons() {
        console.log('🔄 Re-initializing upload buttons...');
        const uploadButtons = document.querySelectorAll('.upload-file-btn');
        console.log('📊 Found ' + uploadButtons.length + ' upload buttons for re-initialization');
        
        uploadButtons.forEach((button, index) => {
            const buttonId = button.id || 'upload_btn_' + Math.random().toString(36).substr(2, 9);
            if (!button.id) button.id = buttonId;
            
            // Проверяем, был ли уже добавлен обработчик
            if (window.fileUploadHandlers.handledButtons.has(buttonId)) {
                return;
            }
            
            // Отмечаем кнопку как обработанную
            window.fileUploadHandlers.handledButtons.add(buttonId);
            console.log('✅ Adding handler to dynamic button:', buttonId);
            
            button.addEventListener('click', function(e) {
                console.log('=== DYNAMIC UPLOAD BUTTON CLICKED ===');
                console.log('Button ID:', buttonId);
                handleUploadButtonClick.call(this, e, buttonId);
            });
        });
    }
    
    // Функция обработки клика на кнопку загрузки
    function handleUploadButtonClick(e, buttonId) {
        console.log('=== UPLOAD BUTTON CLICKED ===');
        console.log('Button ID:', buttonId);
        console.log('Button element:', this);
        console.log('Event details:', e);
        e.preventDefault();
        e.stopPropagation();
        
        // Находим ближайшую модалку и форму внутри неё
        const uploadButton = this;
        const modal = uploadButton.closest('.modal');
        const form = modal.querySelector('form');
        
        if (!form) {
            console.error('Форма не найдена внутри модального окна');
            alert('Ошибка: форма не найдена в модальном окне');
            return;
        }
        
        // Убираем класс d-none у формы, если он есть
        form.classList.remove('d-none');
        form.style.display = 'block';
        
        const formData = new FormData(form);
        
        // Добавляем вывод информации о передаваемых данных для отладки
        console.log('Form action:', form.action);
        console.log('Form method:', form.method);
        for (let [key, value] of formData.entries()) {
            console.log('Form data:', key, value instanceof File ? value.name : value);
        }
        
        // Проверяем, выбран ли файл
        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert('Пожалуйста, выберите файл для загрузки');
            return;
        }
        
        // Создаем уникальный ключ для этой загрузки
        const uploadKey = form.action + '_' + fileInput.files[0].name + '_' + fileInput.files[0].size;
        
        // Проверяем, не идет ли уже загрузка этого файла
        if (window.fileUploadHandlers.activeUploads.has(uploadKey)) {
            console.warn('Загрузка этого файла уже идет, игнорируем дублирующий запрос');
            return;
        }
        
        // Отмечаем загрузку как активную
        window.fileUploadHandlers.activeUploads.add(uploadKey);
        
        // Показываем глобальный лоадер
        if (window.GlobalUploadLoader) {
            // Определяем тип файла из ID модального окна
            let fileType = 'other'; // по умолчанию
            if (modal.id.includes('Design')) fileType = 'design';
            else if (modal.id.includes('Scheme')) fileType = 'scheme';
            else if (modal.id.includes('Document')) fileType = 'document';
            else if (modal.id.includes('Contract')) fileType = 'contract';
            else if (modal.id.includes('Other')) fileType = 'other';
            
            console.log('Showing global loader for file type:', fileType, 'modal ID:', modal.id);
            window.GlobalUploadLoader.show(fileInput.files[0].name, fileType);
        }
        
        // Проверяем доступность axios
        const axiosAvailable = typeof axios !== 'undefined';
        console.log('Axios available:', axiosAvailable);
        
        // Если axios недоступен, отправляем форму стандартным способом
        if (!axiosAvailable) {
            console.warn('Axios недоступен, отправляем форму стандартным способом');
            if (window.GlobalUploadLoader) {
                window.GlobalUploadLoader.hide();
            }
            form.submit();
            return;
        }
        
        const progressContainer = modal.querySelector('.upload-progress');
        
        // Если контейнер прогресса не найден, отправляем форму стандартным способом
        if (!progressContainer) {
            console.warn('Контейнер прогресса загрузки не найден, отправляем форму стандартным способом');
            if (window.GlobalUploadLoader) {
                window.GlobalUploadLoader.hide();
            }
            form.submit();
            return;
        }
        
        const progressBar = progressContainer.querySelector('.progress-bar');
        const progressInfo = progressContainer.querySelector('.progress-info');
        
        // Показываем прогресс загрузки
        form.style.display = 'none'; 
        progressContainer.classList.remove('d-none');
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressInfo.textContent = 'Подготовка к загрузке...';
        
        // Отключаем кнопки
        const buttons = modal.querySelectorAll('button');
        buttons.forEach(btn => btn.disabled = true);
        
        // Выполняем AJAX загрузку файла
        axios.post(form.action, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            onUploadProgress: function(progressEvent) {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                progressBar.style.width = percentCompleted + '%';
                progressInfo.textContent = `Загрузка: ${percentCompleted}%`;
                
                // Обновляем прогресс в глобальном лоадере
                if (window.GlobalUploadLoader) {
                    window.GlobalUploadLoader.updateProgress(percentCompleted);
                }
            }
        })
        .then(function(response) {
            console.log('✅ Успешный ответ сервера:', response.data);
            
            // Удаляем ключ загрузки из активных
            window.fileUploadHandlers.activeUploads.delete(uploadKey);
            
            // Показываем успех в глобальном лоадере
            if (window.GlobalUploadLoader) {
                window.GlobalUploadLoader.showSuccess('Файл успешно загружен!');
            }
            
            // Обрабатываем успешную загрузку
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.remove('progress-bar-striped');
            progressBar.classList.add('bg-success');
            progressInfo.textContent = 'Файл успешно загружен!';
            
            // Перезагружаем страницу через 1 секунду, чтобы показать новый файл в правильной вкладке
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        })
        .catch(function(error) {
            console.error('❌ Ошибка загрузки файла:', error);
            
            // Удаляем ключ загрузки из активных
            window.fileUploadHandlers.activeUploads.delete(uploadKey);
            
            let errorMessage = 'Произошла ошибка при загрузке файла';
            
            // Обрабатываем ошибку
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.remove('progress-bar-striped');
            progressBar.classList.add('bg-danger');
            
            if (error.response) {
                console.error('Ответ сервера:', error.response.data);
                console.error('Статус HTTP:', error.response.status);
                
                if (error.response.data.errors) {
                    // Вывод всех ошибок валидации
                    const errorMessages = Object.values(error.response.data.errors).flat().join(', ');
                    errorMessage = 'Ошибка: ' + errorMessages;
                    progressInfo.textContent = errorMessage;
                } else if (error.response.data.error) {
                    errorMessage = 'Ошибка: ' + error.response.data.error;
                    progressInfo.textContent = errorMessage;
                } else if (error.response.data.message) {
                    errorMessage = 'Ошибка: ' + error.response.data.message;
                    progressInfo.textContent = errorMessage;
                } else {
                    errorMessage = `Ошибка сервера: ${error.response.status}`;
                    progressInfo.textContent = errorMessage;
                }
            } else if (error.request) {
                console.error('Запрос был сделан, но нет ответа', error.request);
                errorMessage = 'Сервер не отвечает. Проверьте соединение.';
                progressInfo.textContent = errorMessage;
            } else {
                console.error('Ошибка настройки запроса', error.message);
                errorMessage = 'Произошла ошибка при загрузке файла: ' + error.message;
                progressInfo.textContent = errorMessage;
            }
            
            // Показываем ошибку в глобальном лоадере
            if (window.GlobalUploadLoader) {
                window.GlobalUploadLoader.showError(errorMessage);
            }
            
            // Включаем кнопки
            buttons.forEach(btn => btn.disabled = false);
            
            // Возвращаем форму через 3 секунды
            setTimeout(function() {
                progressContainer.classList.add('d-none');
                progressContainer.style.display = 'none';
                form.style.display = 'block';
                form.classList.remove('d-none');
            }, 3000);
        });
    }
    
    // Слушаем события переключения вкладок
    document.addEventListener('shown.bs.tab', function(e) {
        console.log('🔄 Tab switched, re-initializing upload buttons');
        setTimeout(initializeUploadButtons, 100);
        setTimeout(initializeDeleteButtons, 100); // Добавляем инициализацию кнопок удаления
    });
    
    // Слушаем события показа модальных окон
    document.addEventListener('shown.bs.modal', function(e) {
        console.log('🔄 Modal shown, re-initializing upload buttons');
        setTimeout(initializeUploadButtons, 100);
        setTimeout(initializeDeleteButtons, 100); // Добавляем инициализацию кнопок удаления
    });
    
    // Инициализация кнопок удаления файлов
    function initializeDeleteButtons() {
        console.log('🗑️ Initializing delete buttons...');
        const deleteButtons = document.querySelectorAll('.delete-file');
        console.log('📊 Found ' + deleteButtons.length + ' delete buttons');
        
        deleteButtons.forEach((button, index) => {
            // Проверяем, не добавлен ли уже обработчик
            if (button.hasAttribute('data-delete-handler-added')) {
                return;
            }
            
            // Отмечаем, что обработчик добавлен
            button.setAttribute('data-delete-handler-added', 'true');
            console.log(`✅ Adding delete handler to button ${index + 1}`);
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🗑️ Delete button clicked');
                
                if (!confirm('Вы уверены, что хотите удалить этот файл? Это действие невозможно отменить.')) {
                    return;
                }
                
                const fileId = this.getAttribute('data-file-id');
                const projectId = this.getAttribute('data-project-id');
                const fileItem = this.closest('.file-item');
                
                console.log('Deleting file:', {fileId, projectId});
                
                if (!fileId || !projectId) {
                    console.error('Missing file ID or project ID');
                    alert('Ошибка: не удается определить файл для удаления');
                    return;
                }
                
                // Отключаем кнопку, чтобы предотвратить повторные клики
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Удаление...';
                
                // Проверяем доступность axios
                if (typeof axios === 'undefined') {
                    console.error('Axios недоступен для удаления файла');
                    alert('Ошибка: невозможно удалить файл (отсутствует axios)');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-trash me-1"></i>Удалить';
                    return;
                }
                
                // Отправляем запрос на удаление - используем правильный роут
                const deleteUrl = `/partner/projects/${projectId}/files/${fileId}`;
                console.log('DELETE URL:', deleteUrl);
                
                axios.delete(deleteUrl, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(function(response) {
                    console.log('✅ File deleted successfully:', response.data);
                    
                    // Анимация удаления элемента
                    if (fileItem) {
                        fileItem.style.opacity = '0';
                        fileItem.style.transform = 'scale(0.8)';
                        fileItem.style.transition = 'all 0.3s ease';
                        
                        setTimeout(() => {
                            fileItem.remove();
                            
                            // Проверяем, остались ли файлы в контейнере
                            const filesContainer = document.querySelector('.files-container');
                            if (filesContainer && filesContainer.children.length === 0) {
                                // Если файлов больше нет, перезагружаем страницу для показа пустого состояния
                                window.location.reload();
                            }
                        }, 300);
                    }
                })
                .catch(function(error) {
                    console.error('❌ Error deleting file:', error);
                    
                    let errorMessage = 'Произошла ошибка при удалении файла';
                    if (error.response) {
                        console.error('Server response:', error.response.data);
                        console.error('HTTP status:', error.response.status);
                        
                        if (error.response.data.message) {
                            errorMessage = error.response.data.message;
                        } else if (error.response.status === 404) {
                            errorMessage = 'Файл не найден';
                        } else if (error.response.status === 403) {
                            errorMessage = 'Нет прав для удаления файла';
                        } else {
                            errorMessage = `Ошибка сервера: ${error.response.status}`;
                        }
                    } else if (error.request) {
                        console.error('No response received:', error.request);
                        errorMessage = 'Сервер не отвечает. Проверьте соединение.';
                    }
                    
                    alert(errorMessage);
                    
                    // Восстанавливаем кнопку
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-trash me-1"></i>Удалить';
                }.bind(this));
            });
        });
    }
    
    // Инициализируем кнопки удаления при загрузке страницы
    initializeDeleteButtons();
    
    console.log('✅ Single file upload handler initialization completed');
});

} // Закрываем блок if (window.fileUploadHandlersScriptLoaded)