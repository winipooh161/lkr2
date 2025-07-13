/**
 * Глобальный лоадер для загрузки файлов с уникальными визуальными стилями
 * для каждого типа файла: дизайн, схемы, документы, договор, прочее
 */

console.log('=== GLOBAL UPLOAD LOADER SCRIPT LOADING ===');

// Предотвращаем дублирование загрузки скрипта
if (window.GlobalUploadLoaderLoaded) {
    console.warn('Global Upload Loader already loaded, skipping');
} else {
    window.GlobalUploadLoaderLoaded = true;

// Создаем глобальный объект лоадера
window.GlobalUploadLoader = {
    
    // Конфигурация для разных типов файлов в сине-голубой тематике
    fileTypeConfigs: {
        design: {
            icon: '🎨',
            color: '#1E88E5',
            bgColor: 'rgba(30, 136, 229, 0.1)',
            borderColor: '#1E88E5',
            text: 'Загрузка дизайна',
            description: 'Обработка файлов дизайна...',
            progressColor: '#1E88E5',
            shadowColor: 'rgba(30, 136, 229, 0.3)'
        },
        scheme: {
            icon: '📐',
            color: '#00BCD4',
            bgColor: 'rgba(0, 188, 212, 0.1)',
            borderColor: '#00BCD4',
            text: 'Загрузка схемы',
            description: 'Обработка схематических файлов...',
            progressColor: '#00BCD4',
            shadowColor: 'rgba(0, 188, 212, 0.3)'
        },
        document: {
            icon: '📄',
            color: '#2196F3',
            bgColor: 'rgba(33, 150, 243, 0.1)',
            borderColor: '#2196F3',
            text: 'Загрузка документа',
            description: 'Обработка документов...',
            progressColor: '#2196F3',
            shadowColor: 'rgba(33, 150, 243, 0.3)'
        },
        contract: {
            icon: '📋',
            color: '#0288D1',
            bgColor: 'rgba(2, 136, 209, 0.1)',
            borderColor: '#0288D1',
            text: 'Загрузка договора',
            description: 'Обработка договорных документов...',
            progressColor: '#0288D1',
            shadowColor: 'rgba(2, 136, 209, 0.3)'
        },
        other: {
            icon: '📁',
            color: '#03A9F4',
            bgColor: 'rgba(3, 169, 244, 0.1)',
            borderColor: '#03A9F4',
            text: 'Загрузка файла',
            description: 'Обработка прочих файлов...',
            progressColor: '#03A9F4',
            shadowColor: 'rgba(3, 169, 244, 0.3)'
        }
    },
    
    // Элементы интерфейса
    overlay: null,
    loader: null,
    
    // Создание HTML-структуры лоадера
    createLoader: function() {
        if (this.overlay) {
            return; // Лоадер уже создан
        }
        
        console.log('Creating global upload loader');
        
        // Создаем overlay
        this.overlay = document.createElement('div');
        this.overlay.id = 'global-upload-overlay';
        this.overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
            transition: all 0.3s ease;
        `;
        
        // Создаем контейнер лоадера
        this.loader = document.createElement('div');
        this.loader.id = 'global-upload-loader';
        this.loader.style.cssText = `
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15);
            min-width: 380px;
            max-width: 500px;
            border: 3px solid #ddd;
            transform: scale(0.8);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        `;
        
        // HTML-структура лоадера
        this.loader.innerHTML = `
            <div class="loader-icon" style="font-size: 60px; margin-bottom: 20px; animation: pulse 2s infinite;">
                🔄
            </div>
            <div class="loader-title" style="font-size: 24px; font-weight: bold; margin-bottom: 10px; color: #333;">
                Загрузка файла
            </div>
            <div class="loader-description" style="font-size: 16px; color: #666; margin-bottom: 30px;">
                Обработка файла...
            </div>
            <div class="loader-filename" style="font-size: 14px; color: #999; margin-bottom: 20px; word-break: break-all;">
                
            </div>
            <div class="progress-container" style="background: #f0f0f0; border-radius: 10px; height: 20px; margin-bottom: 20px; overflow: hidden;">
                <div class="progress-bar" style="height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); width: 0%; transition: width 0.3s ease; border-radius: 10px; position: relative;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); animation: shimmer 2s infinite;"></div>
                </div>
            </div>
            <div class="progress-text" style="font-size: 14px; color: #666;">
                0%
            </div>
        `;
        
        // Добавляем CSS анимации
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.15); opacity: 0.8; }
            }
            
            @keyframes shimmer {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                40% { transform: translateY(-15px); }
                60% { transform: translateY(-8px); }
            }
            
            @keyframes rotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            @keyframes float {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                33% { transform: translateY(-10px) rotate(2deg); }
                66% { transform: translateY(5px) rotate(-2deg); }
            }
            
            @keyframes glow {
                0%, 100% { box-shadow: 0 0 5px currentColor; }
                50% { box-shadow: 0 0 20px currentColor, 0 0 30px currentColor; }
            }
            
            .file-type-design .loader-icon {
                animation: bounce 2s infinite;
                filter: drop-shadow(0 0 10px #1E88E5);
            }
            
            .file-type-scheme .loader-icon {
                animation: rotate 3s linear infinite;
                filter: drop-shadow(0 0 10px #00BCD4);
            }
            
            .file-type-document .loader-icon {
                animation: pulse 2s infinite;
                filter: drop-shadow(0 0 10px #2196F3);
            }
            
            .file-type-contract .loader-icon {
                animation: float 3s ease-in-out infinite;
                filter: drop-shadow(0 0 10px #0288D1);
            }
            
            .file-type-other .loader-icon {
                animation: bounce 2s infinite;
                filter: drop-shadow(0 0 10px #03A9F4);
            }
            
            /* Дополнительные эффекты для контейнера */
            .file-type-design {
                animation: glow 3s ease-in-out infinite;
                color: #1E88E5;
            }
            
            .file-type-scheme {
                animation: glow 4s ease-in-out infinite;
                color: #00BCD4;
            }
            
            .file-type-document {
                animation: glow 3.5s ease-in-out infinite;
                color: #2196F3;
            }
            
            .file-type-contract {
                animation: glow 4.5s ease-in-out infinite;
                color: #0288D1;
            }
            
            .file-type-other {
                animation: glow 3s ease-in-out infinite;
                color: #03A9F4;
            }
        `;
        document.head.appendChild(style);
        
        this.overlay.appendChild(this.loader);
        document.body.appendChild(this.overlay);
        
        console.log('Global upload loader created');
    },
    
    // Получение конфигурации для типа файла
    getFileTypeConfig: function(fileType) {
        const config = this.fileTypeConfigs[fileType];
        if (!config) {
            console.warn('Unknown file type:', fileType, 'using default config');
            return this.fileTypeConfigs.other;
        }
        return config;
    },
    
    // Показать лоадер
    show: function(filename, fileType) {
        this.createLoader();
        
        console.log('Showing global loader for file type:', fileType);
        
        const config = this.getFileTypeConfig(fileType);
        
        // Обновляем элементы лоадера
        const icon = this.loader.querySelector('.loader-icon');
        const title = this.loader.querySelector('.loader-title');
        const description = this.loader.querySelector('.loader-description');
        const filenameEl = this.loader.querySelector('.loader-filename');
        const progressBar = this.loader.querySelector('.progress-bar');
        
        // Применяем конфигурацию типа файла
        icon.textContent = config.icon;
        title.textContent = config.text;
        description.textContent = config.description;
        filenameEl.textContent = filename || '';
        
        // Применяем стили
        this.loader.style.borderColor = config.borderColor;
        this.loader.style.backgroundColor = config.bgColor;
        this.loader.style.boxShadow = `0 25px 80px ${config.shadowColor}`;
        title.style.color = config.color;
        progressBar.style.background = `linear-gradient(90deg, ${config.progressColor}, ${config.progressColor}dd)`;
        
        // Добавляем класс типа файла для анимации
        this.loader.className = '';
        this.loader.classList.add('file-type-' + fileType);
        
        // Показываем overlay
        this.overlay.style.display = 'flex';
        
        // Анимация появления
        setTimeout(() => {
            this.overlay.style.opacity = '1';
            this.loader.style.transform = 'scale(1)';
        }, 10);
        
        console.log('Global loader shown with config:', config);
    },
    
    // Обновить прогресс
    updateProgress: function(percent) {
        if (!this.loader) return;
        
        const progressBar = this.loader.querySelector('.progress-bar');
        const progressText = this.loader.querySelector('.progress-text');
        
        if (progressBar && progressText) {
            progressBar.style.width = percent + '%';
            progressText.textContent = percent + '%';
        }
    },
    
    // Показать успех
    showSuccess: function(message) {
        if (!this.loader) return;
        
        console.log('Showing success message:', message);
        
        const icon = this.loader.querySelector('.loader-icon');
        const title = this.loader.querySelector('.loader-title');
        const description = this.loader.querySelector('.loader-description');
        const progressBar = this.loader.querySelector('.progress-bar');
        
        // Обновляем содержимое
        icon.textContent = '✅';
        icon.style.animation = 'bounce 0.6s ease';
        title.textContent = 'Успешно!';
        title.style.color = '#4CAF50';
        description.textContent = message;
        
        // Зеленый прогресс-бар
        progressBar.style.background = 'linear-gradient(90deg, #4CAF50, #45a049)';
        progressBar.style.width = '100%';
        
        // Прячем через 2 секунды
        setTimeout(() => {
            this.hide();
        }, 2000);
    },
    
    // Показать ошибку
    showError: function(message) {
        if (!this.loader) return;
        
        console.log('Showing error message:', message);
        
        const icon = this.loader.querySelector('.loader-icon');
        const title = this.loader.querySelector('.loader-title');
        const description = this.loader.querySelector('.loader-description');
        const progressBar = this.loader.querySelector('.progress-bar');
        
        // Обновляем содержимое
        icon.textContent = '❌';
        icon.style.animation = 'pulse 0.6s ease';
        title.textContent = 'Ошибка!';
        title.style.color = '#F44336';
        description.textContent = message;
        
        // Красный прогресс-бар
        progressBar.style.background = 'linear-gradient(90deg, #F44336, #d32f2f)';
        
        // Прячем через 4 секунды
        setTimeout(() => {
            this.hide();
        }, 4000);
    },
    
    // Скрыть лоадер
    hide: function() {
        if (!this.overlay) return;
        
        console.log('Hiding global loader');
        
        // Анимация исчезновения
        this.overlay.style.opacity = '0';
        this.loader.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            this.overlay.style.display = 'none';
            
            // Сброс состояния
            if (this.loader) {
                const progressBar = this.loader.querySelector('.progress-bar');
                const progressText = this.loader.querySelector('.progress-text');
                
                if (progressBar) progressBar.style.width = '0%';
                if (progressText) progressText.textContent = '0%';
            }
        }, 300);
    },
    
    // Уничтожить лоадер
    destroy: function() {
        if (this.overlay) {
            this.overlay.remove();
            this.overlay = null;
            this.loader = null;
        }
    }
};

console.log('✅ Global Upload Loader initialized');

} // Закрываем блок if (window.GlobalUploadLoaderLoaded)
