<div class="estimates-table">
<table class="table table-hover table-striped align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th scope="col" width="5%">ID</th>
            <th scope="col" width="25%">Название</th>
            <th scope="col" width="20%">Объект</th>
            <th scope="col" width="10%">Тип</th>
            <th scope="col" width="10%">Сумма, ₽</th>
            <th scope="col" width="10%">Статус</th>
            <th scope="col" width="10%">Дата</th>
            <th scope="col" width="10%">Действия</th>
        </tr>
    </thead>
    <tbody>
        @forelse($estimates as $estimate)
            <tr>
                <td>{{ $estimate->id }}</td>
                <td>
                    <a href="{{ route('partner.estimates.edit', $estimate) }}" class="text-decoration-none fw-bold">
                        {{ $estimate->name }}
                    </a>
                    @if($estimate->description)
                        <p class="text-muted small mb-0">{{ Str::limit($estimate->description, 50) }}</p>
                    @endif
                </td>
                <td>
                    @if($estimate->project)
                        <a href="{{ route('partner.projects.show', $estimate->project) }}" class="text-decoration-none">
                            {{ $estimate->project->full_address }}
                        </a>
                        <p class="text-muted small mb-0">{{ $estimate->project->client_name }}</p>
                    @else
                        <span class="text-muted">Не привязана</span>
                    @endif
                </td>
                <td>
                    @switch($estimate->type)
                        @case('main')
                            <span class="badge bg-primary">Основная</span>
                            @break
                        @case('additional')
                            <span class="badge bg-info">Дополнительная</span>
                            @break
                        @case('materials')
                            <span class="badge bg-warning text-dark">Материалы</span>
                            @break
                        @default
                            <span class="badge bg-secondary">{{ $estimate->type }}</span>
                    @endswitch
                </td>
                <td class="text-end">
                    @if($estimate->total_amount > 0)
                        {{ number_format($estimate->total_amount, 2, '.', ' ') }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @switch($estimate->status)
                        @case('draft')
                            <span class="badge bg-secondary">Черновик</span>
                            @break
                        @case('pending')
                            <span class="badge bg-warning text-dark">На рассмотрении</span>
                            @break
                        @case('approved')
                            <span class="badge bg-success">Утверждена</span>
                            @break
                        @case('rejected')
                            <span class="badge bg-danger">Отклонена</span>
                            @break
                        @default
                            <span class="badge bg-secondary">{{ $estimate->status }}</span>
                    @endswitch
                </td>
                <td class="date-format">
                    {{ $estimate->updated_at }}
                </td>
                <td>
                    <div class="dropdown estimate-action-dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle estimate-action-btn" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false"
                                data-bs-auto-close="true"
                                id="dropdown-{{ $estimate->id }}">
                            <i class="fas fa-cogs me-1"></i> Действия
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('partner.estimates.edit', $estimate->id) }}">
                                    <i class="fas fa-edit me-2 text-primary"></i>Редактировать
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('partner.estimates.show', $estimate->id) }}">
                                    <i class="fas fa-eye me-2 text-info"></i>Просмотреть
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('partner.estimates.editor', $estimate->id) }}">
                                    <i class="fas fa-table me-2 text-info"></i>Табличный редактор
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <!-- Экспорт -->
                            <li>
                                <h6 class="dropdown-header text-center mb-2">
                                    <i class="fas fa-download me-2"></i>Экспорт сметы
                                </h6>
                            </li>
                            
                            <!-- Полная версия -->
                            <li>
                                <div class="px-3 py-2 export-section">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-table me-2 text-primary"></i>
                                            <strong class="text-primary">Полная версия</strong>
                                        </div>
                                        <small class="text-muted">все данные</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-outline-success btn-sm flex-fill" 
                                           href="{{ route('partner.estimates.export', $estimate->id) }}"
                                           title="Скачать Excel с полными данными">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </a>
                                        <a class="btn btn-outline-danger btn-sm flex-fill" 
                                           href="{{ route('partner.estimates.exportPdf', $estimate->id) }}"
                                           title="Скачать PDF с полными данными">
                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <!-- Для заказчика -->
                            <li>
                                <div class="px-3 py-2 export-section">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user me-2 text-info"></i>
                                            <strong class="text-info">Для заказчика</strong>
                                        </div>
                                        <small class="text-muted">клиентские цены</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-outline-success btn-sm flex-fill" 
                                           href="{{ route('partner.estimates.exportClient', $estimate->id) }}"
                                           title="Скачать Excel для заказчика">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </a>
                                        <a class="btn btn-outline-danger btn-sm flex-fill" 
                                           href="{{ route('partner.estimates.exportPdfClient', $estimate->id) }}"
                                           title="Скачать PDF для заказчика">
                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <!-- Для мастера -->
                            <li>
                                <div class="px-3 py-2 export-section">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-hard-hat me-2 text-warning"></i>
                                            <strong class="text-warning">Для мастера</strong>
                                        </div>
                                        <small class="text-muted">базовые цены</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-outline-success btn-sm flex-fill" 
                                           href="{{ route('partner.estimates.exportContractor', $estimate->id) }}"
                                           title="Скачать Excel для мастера">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </a>
                                        <a class="btn btn-outline-danger btn-sm flex-fill" 
                                           href="{{ route('partner.estimates.exportPdfContractor', $estimate->id) }}"
                                           title="Скачать PDF для мастера">
                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('partner.estimates.destroy', $estimate->id) }}" method="POST" class="d-inline delete-form" data-name="{{ $estimate->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-trash me-2"></i>Удалить
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="d-flex flex-column align-items-center">
                        <i class="fas fa-file-excel fa-3x text-muted mb-3"></i>
                        <h5>Нет сохраненных смет</h5>
                        <p class="text-muted">Создайте свою первую смету, нажав кнопку "Создать смету"</p>
                        <a href="{{ route('partner.estimates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i>Создать смету
                        </a>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Подтверждение удаления сметы
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = this.dataset.name;
            
            if (confirm(`Вы уверены, что хотите удалить смету "${name}"? Это действие нельзя отменить.`)) {
                this.submit();
            }
        });
    });
    
    // Улучшенная обработка клика по кнопке выпадающего меню
    document.querySelectorAll('.estimate-action-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Принудительно загружаем скрипт с исправлениями выпадающих меню, если еще не загружен
            if (!window.estimateDropdownsLoaded) {
                const script = document.createElement('script');
                script.src = '/js/estimates-dropdowns.js';
                script.onload = () => {
                    window.estimateDropdownsLoaded = true;
                    console.log('Скрипт с исправлениями выпадающих меню загружен');
                };
                document.head.appendChild(script);
            }

            // Если Bootstrap полностью загружен, пробуем создать/показать выпадающее меню
            if (typeof bootstrap !== 'undefined') {
                try {
                    const dropdownInstance = new bootstrap.Dropdown(button, {
                        autoClose: true
                    });
                    dropdownInstance.show();
                } catch (err) {
                    console.error('Ошибка при показе выпадающего меню:', err);
                }
            }
        });
    });
    
    // Обработчики для элементов внутри выпадающих меню
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            // Если клик не по элементу меню или по форме удаления,
            // предотвращаем закрытие выпадающего меню
            if (!e.target.classList.contains('dropdown-item') || 
                e.target.closest('form.delete-form')) {
                e.stopPropagation();
            }
        });
    });
    
    // Добавляем CSS стили для кнопок экспорта в выпадающих меню
    const exportStyles = `
        <style>
        /* Стили для выпадающих меню экспорта в таблице смет */
        .estimate-action-dropdown .dropdown-menu {
            min-width: 280px;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .estimate-action-dropdown .dropdown-header {
            background-color: #f8f9fa;
            border-radius: 0.5rem 0.5rem 0 0;
            margin: -0.5rem -1rem 0;
            padding: 0.75rem 1rem;
            font-weight: 600;
        }

        .estimate-action-dropdown .dropdown-divider {
            margin: 0.5rem 0;
            opacity: 0.3;
        }

        /* Стили для групп экспорта */
        .estimate-action-dropdown .export-section {
            padding: 0.75rem 1rem;
            background-color: #fff;
        }

        .estimate-action-dropdown .export-section:hover {
            background-color: #f8f9fa;
        }

        .estimate-action-dropdown .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
        }

        .estimate-action-dropdown .btn-outline-success:hover {
            background-color: #198754;
            border-color: #198754;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 0.125rem 0.25rem rgba(25, 135, 84, 0.25);
        }

        .estimate-action-dropdown .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 0.125rem 0.25rem rgba(220, 53, 69, 0.25);
        }

        /* Иконки */
        .estimate-action-dropdown i {
            font-size: 0.875rem;
        }

        /* Адаптивность для мобильных устройств */
        @media (max-width: 768px) {
            .estimate-action-dropdown .dropdown-menu {
                min-width: 250px !important;
                font-size: 0.875rem;
            }
            
            .estimate-action-dropdown .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }

        /* Улучшенный внешний вид кнопки действий */
        .estimate-action-btn {
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }

        .estimate-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
        }
        </style>
    `;
    
    // Добавляем стили в head
    document.head.insertAdjacentHTML('beforeend', exportStyles);
});
</script>
