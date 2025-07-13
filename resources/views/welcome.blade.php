@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Добро пожаловать') }}</div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <h2>Личный кабинет по ремонту</h2>
                        <p class="lead">Сервис управления ремонтно-строительными проектами</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Войти</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">Зарегистрироваться</a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h4>Наши преимущества:</h4>
                        <ul class="list-group mt-3">
                            <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Удобное управление проектами</li>
                            <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Автоматическое создание смет</li>
                            <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Управление строительными бригадами</li>
                            <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Генерация документов</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
