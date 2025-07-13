/**
 * EstimateEditorFormulas - Модуль для работы с формулами в редакторе смет
 * 
 * Предоставляет функциональность для анализа, вычисления и применения формул
 * в таблицах редактора смет.
 * 
 * @version 1.0
 */

class EstimateEditorFormulas {
    /**
     * @param {EstimateEditor} editor - Экземпляр основного редактора
     */
    constructor(editor) {
        this.editor = editor;
        
        // Определение стандартных функций для формул
        this.functions = {
            sum: this.sum,
            multiply: this.multiply,
            divide: this.divide,
            subtract: this.subtract,
            avg: this.avg,
            min: this.min,
            max: this.max,
            round: this.round,
            roundup: this.roundUp,
            rounddown: this.roundDown,
            if: this.ifCondition
        };
        
        // Кэш для уже вычисленных значений
        this.calculationCache = new Map();
        
        // Привязка методов
        this.bindMethods();
    }
    
    /**
     * Привязка методов к контексту
     */
    bindMethods() {
        // Привязываем методы
        this.calculate = this.calculate.bind(this);
        this.evaluateFormula = this.evaluateFormula.bind(this);
        this.parseFormula = this.parseFormula.bind(this);
        this.getCellValueByReference = this.getCellValueByReference.bind(this);
        
        // Привязываем функции
        for (const key of Object.keys(this.functions)) {
            this.functions[key] = this.functions[key].bind(this);
        }
    }
    
    /**
     * Вычисление всех формул в таблице
     * @param {Object} data - Данные таблицы
     * @returns {Object} - Обновленные данные
     */
    calculate(data) {
        if (!data || !data.sheets || !Array.isArray(data.sheets)) {
            return data;
        }
        
        // Очищаем кэш перед вычислением
        this.calculationCache.clear();
        
        const currentSheet = data.currentSheet || 0;
        const sheetData = data.sheets[currentSheet].data;
        
        if (!sheetData || !Array.isArray(sheetData)) {
            return data;
        }
        
        // Обрабатываем каждую строку данных
        for (let i = 0; i < sheetData.length; i++) {
            const row = sheetData[i];
            
            if (row._type === 'header') {
                continue; // Пропускаем заголовки
            }
            
            // Проверяем каждое поле в строке
            for (const key in row) {
                // Пропускаем системные поля
                if (key.startsWith('_')) {
                    continue;
                }
                
                const value = row[key];
                
                // Если значение - строка и начинается с =, то это формула
                if (typeof value === 'string' && value.startsWith('=')) {
                    try {
                        // Вычисляем формулу
                        const result = this.evaluateFormula(value.substr(1), row, i);
                        
                        // Обновляем значение в данных
                        row[key] = result;
                    } catch (error) {
                        console.error(`Ошибка вычисления формулы ${value} в ячейке ${key}, строка ${i}:`, error);
                        row[key] = 0; // Устанавливаем значение по умолчанию при ошибке
                    }
                }
            }
        }
        
        // Вычисляем итоги
        this.calculateTotals(data);
        
        return data;
    }
    
    /**
     * Вычисление итоговых значений
     * @param {Object} data - Данные таблицы
     */
    calculateTotals(data) {
        const currentSheet = data.currentSheet || 0;
        const sheetData = data.sheets[currentSheet].data;
        
        let workTotal = 0;
        let materialsTotal = 0;
        
        // Проходим по всем строкам и суммируем значения
        for (const row of sheetData) {
            // Пропускаем заголовки
            if (row._type === 'header') {
                continue;
            }
            
            // Если есть поле client_cost, добавляем его к итогу
            if (row.client_cost !== undefined) {
                const cost = parseFloat(row.client_cost) || 0;
                
                // Если в имени содержится "материал", считаем как материал
                if (row.name && typeof row.name === 'string' && 
                    row.name.toLowerCase().includes('материал')) {
                    materialsTotal += cost;
                } else {
                    workTotal += cost;
                }
            }
        }
        
        // Обновляем итоги
        if (!data.totals) {
            data.totals = {};
        }
        
        data.totals.work_total = workTotal;
        data.totals.materials_total = materialsTotal;
        data.totals.grand_total = workTotal + materialsTotal;
    }
    
    /**
     * Вычисление формулы
     * @param {string} formula - Формула для вычисления (без знака =)
     * @param {Object} currentRow - Текущая строка данных
     * @param {number} rowIndex - Индекс текущей строки
     * @returns {number} - Результат вычисления
     */
    evaluateFormula(formula, currentRow, rowIndex) {
        // Проверяем кэш
        const cacheKey = `${formula}_${rowIndex}`;
        if (this.calculationCache.has(cacheKey)) {
            return this.calculationCache.get(cacheKey);
        }
        
        try {
            // Парсим формулу
            const parsedFormula = this.parseFormula(formula, currentRow, rowIndex);
            
            // Вычисляем результат
            let result;
            
            // Безопасное вычисление с помощью Function
            try {
                // Создаем функцию для вычисления выражения
                const evalFunction = new Function('return ' + parsedFormula);
                result = evalFunction();
            } catch (e) {
                console.error('Ошибка при вычислении выражения:', parsedFormula, e);
                result = 0;
            }
            
            // Сохраняем результат в кэш
            this.calculationCache.set(cacheKey, result);
            
            return result;
        } catch (error) {
            console.error('Ошибка при вычислении формулы:', formula, error);
            return 0;
        }
    }
    
    /**
     * Парсинг формулы - заменяем ссылки на ячейки и вызовы функций на их значения
     * @param {string} formula - Исходная формула
     * @param {Object} currentRow - Текущая строка данных
     * @param {number} rowIndex - Индекс текущей строки
     * @returns {string} - Преобразованная формула для вычисления
     */
    parseFormula(formula, currentRow, rowIndex) {
        // Заменяем ссылки на ячейки
        let parsedFormula = formula.replace(/([A-Za-z_][A-Za-z0-9_]*)/g, (match) => {
            // Проверяем, что это не функция из нашего списка
            if (this.functions[match.toLowerCase()]) {
                return match; // Оставляем название функции как есть
            }
            
            // Пытаемся получить значение из текущей строки
            if (currentRow[match] !== undefined) {
                return currentRow[match];
            }
            
            // Если это ссылка на ячейку в другой строке (например A5, B10)
            const cellReference = this.parseCellReference(match);
            if (cellReference) {
                return this.getCellValueByReference(cellReference);
            }
            
            // Если ничего не подошло, возвращаем 0
            return '0';
        });
        
        // Заменяем вызовы функций на их результаты
        parsedFormula = this.parseFunctionCalls(parsedFormula, currentRow, rowIndex);
        
        return parsedFormula;
    }
    
    /**
     * Парсинг ссылки на ячейку в формате A5, B10 и т.п.
     * @param {string} reference - Ссылка на ячейку
     * @returns {Object|null} - Объект с колонкой и строкой или null
     */
    parseCellReference(reference) {
        const match = reference.match(/^([A-Z]+)(\d+)$/i);
        if (!match) return null;
        
        const column = this.columnNameToIndex(match[1]);
        const row = parseInt(match[2]) - 1; // Переводим из 1-индексации в 0-индексацию
        
        return { column, row };
    }
    
    /**
     * Преобразование имени колонки в индекс (A=0, B=1, ..., Z=25, AA=26, ...)
     * @param {string} name - Имя колонки
     * @returns {number} - Индекс колонки
     */
    columnNameToIndex(name) {
        name = name.toUpperCase();
        let index = 0;
        
        for (let i = 0; i < name.length; i++) {
            index = index * 26 + name.charCodeAt(i) - 64; // 'A'.charCodeAt(0) = 65
        }
        
        return index - 1; // Переводим из 1-индексации в 0-индексацию
    }
    
    /**
     * Получение значения ячейки по ссылке
     * @param {Object} reference - Ссылка на ячейку
     * @returns {number} - Значение ячейки
     */
    getCellValueByReference(reference) {
        if (!reference) return 0;
        
        const { column, row } = reference;
        
        // Проверяем, что индексы в допустимых пределах
        const currentSheet = this.editor.data.currentSheet || 0;
        const sheetData = this.editor.data.sheets[currentSheet].data;
        
        if (row < 0 || row >= sheetData.length) {
            return 0;
        }
        
        // Получаем строку данных
        const dataRow = sheetData[row];
        if (!dataRow) return 0;
        
        // Если это строка с заголовком, возвращаем 0
        if (dataRow._type === 'header') {
            return 0;
        }
        
        // Получаем колонку
        const structure = this.editor.data.structure;
        if (!structure || !structure.columns || column >= structure.columns.length) {
            return 0;
        }
        
        const columnKey = structure.columns[column].key;
        if (!columnKey) return 0;
        
        // Получаем значение
        let value = dataRow[columnKey];
        
        // Если значение - формула, вычисляем ее
        if (typeof value === 'string' && value.startsWith('=')) {
            value = this.evaluateFormula(value.substr(1), dataRow, row);
        }
        
        return parseFloat(value) || 0;
    }
    
    /**
     * Парсинг вызовов функций в формуле
     * @param {string} formula - Формула
     * @param {Object} currentRow - Текущая строка данных
     * @param {number} rowIndex - Индекс строки
     * @returns {string} - Формула с вычисленными результатами функций
     */
    parseFunctionCalls(formula, currentRow, rowIndex) {
        // Регулярное выражение для поиска вызовов функций
        const functionRegex = /(\w+)\(([^()]*(?:\([^()]*\)[^()]*)*)\)/g;
        
        let result = formula;
        let match;
        
        // Рекурсивно обрабатываем все вызовы функций
        while ((match = functionRegex.exec(result)) !== null) {
            const [fullMatch, funcName, argsString] = match;
            const func = this.functions[funcName.toLowerCase()];
            
            if (func) {
                // Рекурсивно обрабатываем аргументы, если они содержат вызовы функций
                const processedArgs = this.parseFunctionCalls(argsString, currentRow, rowIndex);
                
                // Разбиваем аргументы по запятым, учитывая вложенные скобки
                const args = this.splitArgs(processedArgs);
                
                // Преобразуем строковые аргументы в числа
                const numArgs = args.map(arg => {
                    try {
                        const evalFunction = new Function('return ' + arg.trim());
                        return evalFunction();
                    } catch (e) {
                        return 0;
                    }
                });
                
                // Вычисляем результат функции
                const functionResult = func(numArgs);
                
                // Заменяем вызов функции на результат
                result = result.replace(fullMatch, functionResult);
                
                // Сбрасываем индекс поиска
                functionRegex.lastIndex = 0;
            }
        }
        
        return result;
    }
    
    /**
     * Разделение строки аргументов с учетом вложенных скобок
     * @param {string} argsString - Строка с аргументами
     * @returns {Array<string>} - Массив аргументов
     */
    splitArgs(argsString) {
        const args = [];
        let currentArg = '';
        let depth = 0;
        
        for (let i = 0; i < argsString.length; i++) {
            const char = argsString[i];
            
            if (char === '(' || char === '[' || char === '{') {
                depth++;
                currentArg += char;
            } else if (char === ')' || char === ']' || char === '}') {
                depth--;
                currentArg += char;
            } else if (char === ',' && depth === 0) {
                args.push(currentArg);
                currentArg = '';
            } else {
                currentArg += char;
            }
        }
        
        if (currentArg) {
            args.push(currentArg);
        }
        
        return args;
    }
    
    // Функции для формул
    
    /**
     * Функция суммирования
     * @param {Array<number>} args - Аргументы функции
     * @returns {number} - Сумма аргументов
     */
    sum(args) {
        return args.reduce((sum, val) => sum + (parseFloat(val) || 0), 0);
    }
    
    /**
     * Функция умножения
     * @param {Array<number>} args - Аргументы функции
     * @returns {number} - Произведение аргументов
     */
    multiply(args) {
        return args.reduce((product, val) => product * (parseFloat(val) || 0), 1);
    }
    
    /**
     * Функция деления
     * @param {Array<number>} args - Аргументы функции
     * @returns {number} - Результат деления
     */
    divide(args) {
        if (args.length < 2) return 0;
        
        const divisor = parseFloat(args[1]) || 1;
        if (divisor === 0) return 0; // Предотвращаем деление на ноль
        
        return (parseFloat(args[0]) || 0) / divisor;
    }
    
    /**
     * Функция вычитания
     * @param {Array<number>} args - Аргументы функции
     * @returns {number} - Результат вычитания
     */
    subtract(args) {
        if (args.length < 2) return 0;
        
        const first = parseFloat(args[0]) || 0;
        return args.slice(1).reduce((result, val) => result - (parseFloat(val) || 0), first);
    }
    
    /**
     * Функция вычисления среднего значения
     * @param {Array<number>} args - Аргументы функции
     * @returns {number} - Среднее значение
     */
    avg(args) {
        if (args.length === 0) return 0;
        
        const sum = args.reduce((total, val) => total + (parseFloat(val) || 0), 0);
        return sum / args.length;
    }
    
    /**
     * Функция нахождения минимального значения
     * @param {Array<number>} args - Аргументы функции
     * @returns {number} - Минимальное значение
     */
    min(args) {
        if (args.length === 0) return 0;
        
        return Math.min(...args.map(val => parseFloat(val) || 0));
    }
    
    /**
     * Функция нахождения максимального значения
     * @param {Array<number>} args - Аргументы функции
     * @returns {number} - Максимальное значение
     */
    max(args) {
        if (args.length === 0) return 0;
        
        return Math.max(...args.map(val => parseFloat(val) || 0));
    }
    
    /**
     * Функция округления
     * @param {Array<number>} args - Аргументы функции (значение, [количество знаков после запятой])
     * @returns {number} - Округленное значение
     */
    round(args) {
        if (args.length === 0) return 0;
        
        const value = parseFloat(args[0]) || 0;
        const decimals = args.length > 1 ? parseInt(args[1]) || 0 : 0;
        
        const factor = Math.pow(10, decimals);
        return Math.round(value * factor) / factor;
    }
    
    /**
     * Функция округления вверх
     * @param {Array<number>} args - Аргументы функции (значение, [количество знаков после запятой])
     * @returns {number} - Округленное вверх значение
     */
    roundUp(args) {
        if (args.length === 0) return 0;
        
        const value = parseFloat(args[0]) || 0;
        const decimals = args.length > 1 ? parseInt(args[1]) || 0 : 0;
        
        const factor = Math.pow(10, decimals);
        return Math.ceil(value * factor) / factor;
    }
    
    /**
     * Функция округления вниз
     * @param {Array<number>} args - Аргументы функции (значение, [количество знаков после запятой])
     * @returns {number} - Округленное вниз значение
     */
    roundDown(args) {
        if (args.length === 0) return 0;
        
        const value = parseFloat(args[0]) || 0;
        const decimals = args.length > 1 ? parseInt(args[1]) || 0 : 0;
        
        const factor = Math.pow(10, decimals);
        return Math.floor(value * factor) / factor;
    }
    
    /**
     * Функция условия IF
     * @param {Array<number|boolean>} args - Аргументы функции (условие, значение при истине, значение при лжи)
     * @returns {number} - Результат условия
     */
    ifCondition(args) {
        if (args.length < 3) return 0;
        
        const condition = args[0];
        const trueValue = parseFloat(args[1]) || 0;
        const falseValue = parseFloat(args[2]) || 0;
        
        return condition ? trueValue : falseValue;
    }
}
