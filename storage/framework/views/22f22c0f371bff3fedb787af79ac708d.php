


<script src="<?php echo e(asset('js/estimates/excel-editor-fixed.js')); ?>?v=<?php echo e(time()); ?>"></script>
<script src="<?php echo e(asset('js/estimates/excel-sheet-manager.js')); ?>?v=<?php echo e(time()); ?>"></script>


<script src="<?php echo e(asset('js/estimates/excel-row-format.js')); ?>?v=<?php echo e(time()); ?>"></script>


<script src="<?php echo e(asset('js/estimates/excel-ultra-boost.js')); ?>?v=<?php echo e(time()); ?>"></script>


<script src="<?php echo e(asset('js/estimates/excel-unified-optimizer.js')); ?>?v=<?php echo e(time()); ?>"></script>


<script src="<?php echo e(asset('js/estimates/system-check.js')); ?>?v=<?php echo e(time()); ?>"></script>


<script src="<?php echo e(asset('js/estimates/excel-formula-manager-fixed.js')); ?>?v=<?php echo e(time()); ?>"></script>


<script>
    // Переопределяем стандартную функцию загрузки файлов на оптимизированную версию
    if (window.EXCEL_OPTIMIZATION_ENABLED && typeof ultraLoadExcelFile === 'function') {
        console.log('🚀 Включена ультра-оптимизированная загрузка файлов Excel');
        
        // Сохраняем оригинальную функцию как резервную
        window.originalLoadExcelFile = window.loadExcelFile;
        
        // Заменяем на оптимизированную версию
        window.loadExcelFile = function(url) {
            return ultraLoadExcelFile(url);
        };
    }
    
    // Переопределяем стандартную функцию загрузки данных листа на оптимизированную версию
    if (window.EXCEL_OPTIMIZATION_ENABLED && typeof ultraLoadSheetData === 'function') {
        console.log('🚀 Включена ультра-оптимизированная загрузка данных листа');
        
        // Сохраняем оригинальную функцию как резервную
        window.originalLoadSheetData = window.loadSheetData;
        
        // Заменяем на оптимизированную версию
        window.loadSheetData = function(sheetIndex) {
            return ultraLoadSheetData(sheetIndex);
        };
    }
    
    // Переопределяем стандартную функцию форматирования на оптимизированную версию
    if (window.EXCEL_OPTIMIZATION_ENABLED && typeof ultraApplySheetFormatting === 'function') {
        console.log('🚀 Включено ультра-оптимизированное форматирование');
        
        // Сохраняем оригинальную функцию как резервную
        window.originalApplySheetFormatting = window.applySheetFormatting;
        
        // Заменяем на оптимизированную версию
        window.applySheetFormatting = function(sheetData, skipRender) {
            return ultraApplySheetFormatting(sheetData, skipRender);
        };
    }
    
    // Переопределяем функцию индикатора загрузки на оптимизированную версию
    if (window.EXCEL_OPTIMIZATION_ENABLED && typeof showUltraLoading === 'function') {
        console.log('🚀 Включен ультра-оптимизированный индикатор загрузки');
        
        // Заменяем на оптимизированную версию
        // Проверяем, не был ли уже переопределен showLoading
        if (typeof window.showLoading === 'function' && 
            window.showLoading.toString().indexOf('showUltraLoading') === -1) {
            // Сохраняем оригинальную функцию как резервную, если еще не сохранена
            if (!window.originalShowLoading) {
                window.originalShowLoading = window.showLoading;
            }
            
            // Заменяем функцию
            window.showLoading = function(show, message) {
                return showUltraLoading(show, message);
            };
        }
    }
</script>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/partner/estimates/partials/excel-scripts.blade.php ENDPATH**/ ?>