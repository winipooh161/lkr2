// Импортируем axios (для Vite)
import axios from 'axios';

/**
 * Обработка загрузки файлов для проектов
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Project file upload script loaded');
    
    // Проверяем, доступен ли Axios глобально
    if (typeof axios === 'undefined') {
        console.error('Axios не доступен глобально! Это может вызвать ошибки.');
    } else {
        console.log('Axios доступен:', axios.version);
    }
    
    // Инициализация модальных окон при открытии
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(modalToggler => {
        modalToggler.addEventListener('click', function() {
            const targetModalId = this.getAttribute('data-bs-target');
            if(!targetModalId) return;
            
            const modal = document.querySelector(targetModalId);
            if(!modal) return;
            
            console.log('Modal opened:', targetModalId);
            
            // Убираем класс d-none у формы при открытии модалки
            const form = modal.querySelector('form');
            if(form) {
                console.log('Form found in modal:', form);
                form.classList.remove('d-none');
                form.style.display = 'block';
            }
            
            // Сбрасываем состояние прогресс-бара
            const progressContainer = modal.querySelector('.upload-progress');
            if(progressContainer) {
                progressContainer.classList.add('d-none');
            }
            
            // Активируем кнопки
            const buttons = modal.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = false);
        });
    });
    
    // Обработчик для всех кнопок загрузки файлов
    document.querySelectorAll('.upload-file-btn').forEach(button => {
        console.log('Upload button found:', button);
        
        button.addEventListener('click', function(e) {
            console.log('Upload button clicked');
            e.preventDefault();
            
            // Находим ближайшую модалку и форму внутри неё
            const uploadButton = this;
            const modal = uploadButton.closest('.modal');
            const form = modal.querySelector('form');
            
            if (!form) {
                console.error('Форма не найдена внутри модального окна');
                return;
            }
            
            // Убираем класс d-none у формы, если он есть
            form.classList.remove('d-none');
            form.style.display = 'block';
            
            const formData = new FormData(form);
            const progressContainer = modal.querySelector('.upload-progress');
            
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
            
            // Если контейнер прогресса не найден, отправляем форму стандартным способом
            if (!progressContainer) {
                console.warn('Контейнер прогресса загрузки не найден, отправляем форму стандартным способом');
                form.submit();
                return;
            }
            
            const progressBar = progressContainer.querySelector('.progress-bar');
            const progressInfo = progressContainer.querySelector('.progress-info');
            
            // Показываем прогресс загрузки
            form.style.display = 'none'; 
            progressContainer.classList.remove('d-none');
            progressContainer.style.display = 'block'; // Явно устанавливаем display:block
            progressBar.style.width = '0%';
            progressInfo.textContent = 'Подготовка к загрузке...';
            
            // Отключаем кнопки
            const buttons = modal.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
            
            // Запрос на загрузку файла
            axios.post(form.action, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                onUploadProgress: function(progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = percentCompleted + '%';
                    progressInfo.textContent = `Загрузка: ${percentCompleted}%`;
                }
            })
            .then(function(response) {
                console.log('Успешный ответ сервера:', response.data);
                
                // Обрабатываем успешную загрузку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-success');
                progressInfo.textContent = 'Файл успешно загружен!';
                
                // Перезагружаем страницу через 1 секунду
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            })
            .catch(function(error) {
                // Обрабатываем ошибку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-danger');
                
                console.error('Ошибка загрузки файла:', error);
                if (error.response) {
                    console.error('Ответ сервера:', error.response.data);
                    console.error('Статус HTTP:', error.response.status);
                    
                    if (error.response.data.errors) {
                        // Вывод всех ошибок валидации
                        const errorMessages = Object.values(error.response.data.errors).flat().join(', ');
                        progressInfo.textContent = 'Ошибка: ' + errorMessages;
                    } else if (error.response.data.error) {
                        progressInfo.textContent = 'Ошибка: ' + error.response.data.error;
                    } else if (error.response.data.message) {
                        progressInfo.textContent = 'Ошибка: ' + error.response.data.message;
                    } else {
                        progressInfo.textContent = `Ошибка сервера: ${error.response.status}`;
                    }
                } else if (error.request) {
                    console.error('Запрос был сделан, но нет ответа', error.request);
                    progressInfo.textContent = 'Сервер не отвечает. Проверьте соединение.';
                } else {
                    console.error('Ошибка настройки запроса', error.message);
                    progressInfo.textContent = 'Произошла ошибка при загрузке файла: ' + error.message;
                }
                
                // Включаем кнопки
                buttons.forEach(btn => btn.disabled = false);
                
                // Возвращаем форму через 2 секунды
                setTimeout(function() {
                    progressContainer.classList.add('d-none');
                    progressContainer.style.display = 'none';
                    form.style.display = 'block';
                    form.classList.remove('d-none');
                }, 2000);
            });
        });
    });
    
    // Специальный обработчик для схем с id uploadSchemeButton (если он существует отдельно)
    const uploadSchemeButton = document.getElementById('uploadSchemeButton');
    if (uploadSchemeButton) {
        uploadSchemeButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const modal = document.getElementById('uploadSchemeModal');
            const form = document.getElementById('uploadSchemeForm');
            
            if (!form) {
                console.error('Форма uploadSchemeForm не найдена');
                return;
            }
            
            form.classList.remove('d-none');
            form.style.display = 'block';
            
            const formData = new FormData(form);
            const progressContainer = modal.querySelector('.upload-progress');
            
            // Добавляем вывод информации о передаваемых данных для отладки
            console.log('Scheme form action:', form.action);
            console.log('Scheme form method:', form.method);
            for (let [key, value] of formData.entries()) {
                console.log('Scheme form data:', key, value instanceof File ? value.name : value);
            }
            
            // Проверяем, выбран ли файл
            const fileInput = form.querySelector('input[type="file"]');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                alert('Пожалуйста, выберите файл для загрузки');
                return;
            }
            
            // Если контейнер прогресса не найден, отправляем форму стандартным способом
            if (!progressContainer) {
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
            
            // Запрос на загрузку файла
            axios.post(form.action, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                onUploadProgress: function(progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = percentCompleted + '%';
                    progressInfo.textContent = `Загрузка: ${percentCompleted}%`;
                }
            })
            .then(function(response) {
                // Обрабатываем успешную загрузку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-success');
                progressInfo.textContent = 'Файл успешно загружен!';
                
                // Перезагружаем страницу через 1 секунду
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            })
            .catch(function(error) {
                // Обрабатываем ошибку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-danger');
                
                console.error('Ошибка загрузки файла схемы:', error);
                if (error.response) {
                    console.error('Ответ сервера:', error.response.data);
                    console.error('Статус HTTP:', error.response.status);
                    
                    if (error.response.data.errors) {
                        // Вывод всех ошибок валидации
                        const errorMessages = Object.values(error.response.data.errors).flat().join(', ');
                        progressInfo.textContent = 'Ошибка: ' + errorMessages;
                    } else if (error.response.data.error) {
                        progressInfo.textContent = 'Ошибка: ' + error.response.data.error;
                    } else if (error.response.data.message) {
                        progressInfo.textContent = 'Ошибка: ' + error.response.data.message;
                    } else {
                        progressInfo.textContent = `Ошибка сервера: ${error.response.status}`;
                    }
                } else {
                    progressInfo.textContent = 'Произошла ошибка при загрузке файла.';
                }
                
                // Включаем кнопки
                buttons.forEach(btn => btn.disabled = false);
                
                // Возвращаем форму через 2 секунды
                setTimeout(function() {
                    progressContainer.classList.add('d-none');
                    progressContainer.style.display = 'none';
                    form.style.display = 'block';
                    form.classList.remove('d-none');
                }, 2000);
            });
        });
    }
    
    // Обработчик удаления файлов
    document.querySelectorAll('.delete-file').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Вы уверены, что хотите удалить этот файл? Это действие невозможно отменить.')) {
                return;
            }
            
            const fileId = this.getAttribute('data-file-id');
            const projectId = this.getAttribute('data-project-id'); // Добавляем получение project_id из атрибута
            const fileItem = document.querySelector(`.file-item[data-file-id="${fileId}"]`);
            
            // Исправляем URL для соответствия маршрутам Laravel
            axios.delete(`/partner/projects/${projectId}/files/${fileId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(function(response) {
                // Анимация удаления элемента
                fileItem.style.opacity = '0';
                fileItem.style.transform = 'scale(0.8)';
                fileItem.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    fileItem.remove();
                    
                    // Проверяем, остались ли ещё файлы в контейнере
                    const container = document.querySelector('.files-container');
                    if (container && container.children.length === 0) {
                        // Если файлов не осталось, перезагружаем страницу
                        window.location.reload();
                    }
                }, 300);
            })
            .catch(function(error) {
                console.error('Ошибка удаления файла:', error);
                alert('Ошибка при удалении файла: ' + (error.response?.data?.message || 'Неизвестная ошибка'));
            });
        });
    });
    
    // Добавляем обработчик события bootstrap:mounted для модальных окон
    document.addEventListener('shown.bs.modal', function(event) {
        const modal = event.target;
        console.log('Modal shown event fired:', modal.id);
        
        const form = modal.querySelector('form');
        if (form) {
            form.classList.remove('d-none');
            form.style.display = 'block';
        }
    });
});
