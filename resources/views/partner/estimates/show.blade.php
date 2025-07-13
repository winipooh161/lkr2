@extends('layouts.app')

<!-- Улучшения интерфейса экспорта -->
<script src="{{ asset('js/estimates/export-ui-enhancements.js') }}?v={{ time() }}"></script>

<!-- Добавляем стили для кнопок экспорта -->
<style>
/* Стили для выпадающего меню экспорта на странице просмотра сметы */
.dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.dropdown-menu .dropdown-header {
    background-color: #f8f9fa;
    border-radius: 0.5rem 0.5rem 0 0;
    margin: -0.5rem -1rem 0;
    padding: 0.75rem 1rem;
    font-weight: 600;
}

.dropdown-menu .dropdown-divider {
    margin: 0.5rem 0;
    opacity: 0.3;
}

/* Стили для групп экспорта */
.export-section {
    padding: 0.75rem 1rem;
    background-color: #fff;
}

.export-section:hover {
    background-color: #f8f9fa;
}

.export-section .section-title {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.export-section .section-description {
    font-size: 0.75rem;
    color: #6c757d;
    margin-left: auto;
}

.export-section .btn-group {
    gap: 0.5rem;
}

.export-section .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.15s ease-in-out;
}

.export-section .btn-outline-success:hover {
    background-color: #198754;
    border-color: #198754;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 0.125rem 0.25rem rgba(25, 135, 84, 0.25);
}

.export-section .btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 0.125rem 0.25rem rgba(220, 53, 69, 0.25);
}

/* Иконки */
.export-section i {
    font-size: 0.875rem;
}

/* Адаптивность для мобильных устройств */
@media (max-width: 768px) {
    .dropdown-menu {
        min-width: 250px !important;
        font-size: 0.875rem;
    }
    
    .export-section .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .export-section .section-description {
        font-size: 0.7rem;
    }
}

/* Улучшенный внешний вид кнопки экспорта */
.btn-group .btn.dropdown-toggle {
    font-weight: 500;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 123, 255, 0.25);
    transition: all 0.15s ease-in-out;
}

.btn-group .btn.dropdown-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 123, 255, 0.3);
}
</style>

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h1 class="h3 mb-2 mb-md-0">{{ $estimate->name }}</h1>
        <div class="mt-2 mt-md-0 d-flex gap-2">
            <!-- Кнопки экспорта -->
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-download me-1"></i>Экспорт
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 280px;">
                    <!-- Заголовок -->
                    <li>
                        <h6 class="dropdown-header text-center mb-2">
                            <i class="fas fa-download me-2"></i>Экспорт сметы
                        </h6>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    
                    <!-- Полная версия -->
                    <li>
                        <div class="px-3 py-2 export-section">
                            <div class="d-flex align-items-center mb-2 section-title">
                                <i class="fas fa-table me-2 text-primary"></i>
                                <strong class="text-primary">Полная версия</strong>
                                <small class="text-muted ms-auto section-description">(все данные)</small>
                            </div>
                            <div class="d-flex gap-2 btn-group">
                                <a class="btn btn-outline-success btn-sm flex-fill" href="{{ route('partner.estimates.export', $estimate->id) }}">
                                    <i class="fas fa-file-excel me-1"></i>Excel
                                </a>
                                <a class="btn btn-outline-danger btn-sm flex-fill" href="{{ route('partner.estimates.exportPdf', $estimate->id) }}">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </a>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    
                    <!-- Для заказчика -->
                    <li>
                        <div class="px-3 py-2 export-section">
                            <div class="d-flex align-items-center mb-2 section-title">
                                <i class="fas fa-user me-2 text-info"></i>
                                <strong class="text-info">Для заказчика</strong>
                                <small class="text-muted ms-auto section-description">(клиентские цены)</small>
                            </div>
                            <div class="d-flex gap-2 btn-group">
                                <a class="btn btn-outline-success btn-sm flex-fill" href="{{ route('partner.estimates.exportClient', $estimate->id) }}">
                                    <i class="fas fa-file-excel me-1"></i>Excel
                                </a>
                                <a class="btn btn-outline-danger btn-sm flex-fill" href="{{ route('partner.estimates.exportPdfClient', $estimate->id) }}">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </a>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    
                    <!-- Для мастера -->
                    <li>
                        <div class="px-3 py-2 export-section">
                            <div class="d-flex align-items-center mb-2 section-title">
                                <i class="fas fa-hard-hat me-2 text-warning"></i>
                                <strong class="text-warning">Для мастера</strong>
                                <small class="text-muted ms-auto section-description">(базовые цены)</small>
                            </div>
                            <div class="d-flex gap-2 btn-group">
                                <a class="btn btn-outline-success btn-sm flex-fill" href="{{ route('partner.estimates.exportContractor', $estimate->id) }}">
                                    <i class="fas fa-file-excel me-1"></i>Excel
                                </a>
                                <a class="btn btn-outline-danger btn-sm flex-fill" href="{{ route('partner.estimates.exportPdfContractor', $estimate->id) }}">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            
            <a href="{{ route('partner.estimates.edit', $estimate) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Редактировать
            </a>
            <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>К списку
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <!-- Карточка с основной информацией -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i> Основная информация
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Название сметы</label>
                        <p>{{ $estimate->name }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Объект</label>
                        @if($estimate->project)
                            <p>
                                <a href="{{ route('partner.projects.show', $estimate->project) }}" class="text-decoration-none">
                                    {{ $estimate->project->full_address }}
                                </a><br>
                                <small class="text-muted">Заказчик: {{ $estimate->project->client_name }}</small>
                            </p>
                        @else
                            <p class="text-muted">Не привязана к объекту</p>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Тип сметы</label>
                        <p>
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
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Статус</label>
                        <p>
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
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Итоговая сумма</label>
                        <p class="h4">{{ number_format($estimate->total_amount, 2, '.', ' ') }} ₽</p>
                    </div>
                    
                    @if($estimate->description)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Примечания</label>
                        <p>{{ $estimate->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
            
          
        </div>
        
        <div class="col-md-8">
            <!-- История изменений -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i> История изменений
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Создание сметы</h5>
                                <small>{{ $estimate->created_at->format('d.m.Y H:i') }}</small>
                            </div>
                            <p class="mb-1">Смета была создана пользователем {{ $estimate->user->name ?? 'Неизвестно' }}</p>
                        </div>
                        
                        @if($estimate->file_updated_at && $estimate->file_updated_at != $estimate->created_at)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Обновление файла</h5>
                                <small>{{ $estimate->file_updated_at->format('d.m.Y H:i') }}</small>
                            </div>
                            <p class="mb-1">Файл сметы был обновлен</p>
                        </div>
                        @endif
                        
                        @if($estimate->updated_at != $estimate->created_at)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Обновление информации</h5>
                                <small>{{ $estimate->updated_at->format('d.m.Y H:i') }}</small>
                            </div>
                            <p class="mb-1">Информация о смете была обновлена</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Элементы сметы (если они есть) -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-1"></i> Содержание сметы</span>
                    <span class="badge bg-primary">{{ $estimate->items->count() }} позиций</span>
                </div>
                <div class="card-body p-0">
                    @if($estimate->items->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" width="5%">#</th>
                                        <th scope="col" width="40%">Наименование</th>
                                        <th scope="col" width="10%">Ед. изм.</th>
                                        <th scope="col" width="10%">Кол-во</th>
                                        <th scope="col" width="15%">Цена</th>
                                        <th scope="col" width="20%">Стоимость</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($estimate->items as $item)
                                        <tr @if($item->is_section_header) class="table-light" @endif>
                                            <td>{{ $item->position_number ?? '' }}</td>
                                            <td class="@if($item->is_section_header) fw-bold @endif">{{ $item->name }}</td>
                                            <td>{{ $item->unit }}</td>
                                            <td class="text-center">{{ $item->is_section_header ? '' : $item->quantity }}</td>
                                            <td class="text-end">{{ $item->is_section_header ? '' : number_format($item->price, 2, '.', ' ') }}</td>
                                            <td class="text-end fw-bold">{{ $item->is_section_header ? '' : number_format($item->client_cost, 2, '.', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-primary">
                                        <td colspan="5" class="text-end fw-bold">ИТОГО:</td>
                                        <td class="text-end fw-bold">{{ number_format($estimate->total_amount, 2, '.', ' ') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="mb-3">В этой смете пока нет элементов.</p>
                            <a href="{{ route('partner.estimates.edit', $estimate) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Редактировать смету
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
