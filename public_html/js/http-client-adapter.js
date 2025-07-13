/**
 * Обертка для HTTP запросов (совместима с axios)
 * Обеспечивает работу даже без библиотеки axios
 * Версия: 1.0
 * Дата: 11.07.2025
 */

(function() {
    'use strict';

    // Проверяем наличие библиотеки axios
    const hasAxios = typeof axios !== 'undefined';
    
    // Создаем объект для хранения нашей обертки
    const HttpClient = {
        /**
         * Отправка POST запроса
         * @param {string} url - URL для запроса
         * @param {Object} data - Данные для отправки
         * @param {Object} config - Конфигурация (headers, и т.д.)
         * @returns {Promise} - Promise с результатом запроса
         */
        post: function(url, data, config = {}) {
            // Если доступен axios, используем его
            if (hasAxios) {
                console.log('🔄 Отправка запроса через axios');
                return axios.post(url, data, config);
            }
            
            // Иначе используем fetch API
            console.log('🔄 Отправка запроса через fetch (axios не найден)');
            
            const headers = config.headers || {};
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...headers
                },
                body: JSON.stringify(data),
                credentials: 'same-origin'
            })
            .then(response => {
                // Проверяем статус ответа
                if (!response.ok) {
                    throw new Error(`Ошибка HTTP: ${response.status}`);
                }
                
                // Преобразуем ответ в формат, совместимый с axios
                return response.json().then(data => {
                    return {
                        data: data,
                        status: response.status,
                        statusText: response.statusText,
                        headers: response.headers,
                        config: config
                    };
                });
            });
        },
        
        /**
         * Отправка GET запроса
         * @param {string} url - URL для запроса
         * @param {Object} config - Конфигурация (params, headers, и т.д.)
         * @returns {Promise} - Promise с результатом запроса
         */
        get: function(url, config = {}) {
            // Если доступен axios, используем его
            if (hasAxios) {
                console.log('🔄 Отправка GET запроса через axios');
                return axios.get(url, config);
            }
            
            // Иначе используем fetch API
            console.log('🔄 Отправка GET запроса через fetch (axios не найден)');
            
            // Обрабатываем параметры запроса
            let finalUrl = url;
            if (config.params) {
                const queryString = new URLSearchParams(config.params).toString();
                finalUrl = `${url}${url.includes('?') ? '&' : '?'}${queryString}`;
            }
            
            const headers = config.headers || {};
            
            return fetch(finalUrl, {
                method: 'GET',
                headers: headers,
                credentials: 'same-origin'
            })
            .then(response => {
                // Проверяем статус ответа
                if (!response.ok) {
                    throw new Error(`Ошибка HTTP: ${response.status}`);
                }
                
                // Преобразуем ответ в формат, совместимый с axios
                return response.json().then(data => {
                    return {
                        data: data,
                        status: response.status,
                        statusText: response.statusText,
                        headers: response.headers,
                        config: config
                    };
                });
            });
        }
    };
    
    // Добавляем обертку в глобальную область видимости
    window.httpClient = HttpClient;
    
    // Если axios не найден, создаем заглушку
    if (!hasAxios) {
        console.log('⚠️ Библиотека axios не найдена, создаю заглушку для совместимости');
        
        // Создаем заглушку для axios
        window.axios = {
            post: HttpClient.post,
            get: HttpClient.get,
            isAxiosMock: true // Флаг, что это заглушка
        };
        
        console.log('✅ Заглушка axios создана успешно');
    }
})();
