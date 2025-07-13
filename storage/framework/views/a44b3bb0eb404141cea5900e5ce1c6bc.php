<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo e(asset('/img/logo.svg')); ?>" alt="" class="" width="40" height="40" style="object-fit: cover;">
    </div>

    <div class="sidebar-profile py-3 px-4 mb-3">
        <?php if(auth()->guard()->check()): ?>
            <div class="d-flex align-items-center">
                <div class="profile-icon">
                    <img src="<?php echo e(Auth::user()->getAvatarUrl()); ?>" alt="<?php echo e(Auth::user()->name); ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                </div>
                <div class="ms-3">
                    <div class="fw-bold"><?php echo e(Auth::user()->name); ?></div>
                    <div><span class="badge bg-secondary"><?php echo e(ucfirst(Auth::user()->role)); ?></span></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo e(Request::is('/') ? 'active' : ''); ?>">
            <a href="<?php echo e(url('/')); ?>"><i class="fas fa-home me-2"></i>Главная</a>
        </li>

        <?php if(auth()->guard()->check()): ?>
            <li class="<?php echo e(Request::is('profile') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('profile.index')); ?>"><i class="fas fa-user me-2"></i>Мой профиль</a>
            </li>

            <?php if(Auth::user()->isAdmin()): ?>
            <!-- Меню администратора -->
            <li class="sidebar-header mt-3 mb-2">Администрирование</li>
            <li class="<?php echo e(Request::is('admin') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('admin.dashboard')); ?>"><i class="fas fa-tachometer-alt me-2"></i>Панель управления</a>
            </li>
            <li>
                <a href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-users me-2"></i>Пользователи
                </a>
                <ul class="collapse list-unstyled" id="userSubmenu">
                    <li>
                        <a href="<?php echo e(route('admin.users.index')); ?>"><i class="fas fa-list me-2"></i>Все пользователи</a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if(Auth::user()->isPartner() || Auth::user()->isAdmin()): ?>
            <!-- Меню партнера -->
            <li class="sidebar-header mt-3 mb-2">Партнёрский раздел</li>
            <li class="<?php echo e(Request::is('partner') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('partner.dashboard')); ?>"><i class="fas fa-tachometer-alt me-2"></i>Панель партнёра</a>
            </li>
            <li class="<?php echo e(Request::is('partner/projects*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('partner.projects.index')); ?>"><i class="fas fa-building me-2"></i>Объекты</a>
            </li>
            <li class="<?php echo e(Request::is('partner/estimates*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('partner.estimates.index')); ?>"><i class="fas fa-file-invoice me-2"></i>Сметы                     <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem; vertical-align: super;">BETA</span></a>
            </li>
            <li class="<?php echo e(Request::is('partner/employees*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('partner.employees.index')); ?>"><i class="fas fa-users me-2"></i>Сотрудники</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->is('partner/calculator*') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('partner.calculator.index')); ?>">
                    <i class="fas fa-calculator me-2"></i>
                    Калькулятор 
                    <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem; vertical-align: super;">BETA</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(Auth::user()->isEstimator() || Auth::user()->isAdmin()): ?>
            <!-- Меню сметчика -->
            <li class="sidebar-header mt-3 mb-2">Сметный раздел</li>
            <li class="<?php echo e(Request::is('estimator') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('estimator.dashboard')); ?>"><i class="fas fa-tachometer-alt me-2"></i>Панель сметчика</a>
            </li>
            <li class="<?php echo e(Request::is('estimator/estimates*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('estimator.estimates.index')); ?>"><i class="fas fa-file-invoice me-2"></i>Все сметы                     <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem; vertical-align: super;">BETA</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->is('estimator/calculator*') ? 'active' : ''); ?>" 
                   href="<?php echo e(route('estimator.calculator.index')); ?>">
                    <i class="fas fa-calculator me-2"></i>
                    Калькулятор 
                    <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem; vertical-align: super;">BETA</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(Auth::user()->isClient()|| Auth::user()->isAdmin()): ?>
            <!-- Меню клиента -->
            <li class="sidebar-header mt-3 mb-2">Клиентский раздел</li>
            <li class="<?php echo e(Request::is('client/projects*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('client.projects.index')); ?>"><i class="fas fa-building me-2"></i>Мои объекты</a>
            </li>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Общие пункты меню -->
        <li class="sidebar-header mt-3 mb-2">Информация</li>
        <li>
            <a href="https://ediz.ru/"><i class="fas fa-info-circle me-2"></i>О компании</a>
        </li>
        <li>
            <a href="https://ediz.ru/contacts"><i class="fas fa-phone me-2"></i>Контакты</a>
        </li>
        <li>
            <a href="#"><i class="fas fa-question-circle me-2"></i>Помощь</a>
        </li>

        <?php if(auth()->guard()->check()): ?>
        <li class="mt-3">
            <a href="<?php echo e(route('logout')); ?>" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt me-2"></i><?php echo e(__('Выйти')); ?>

            </a>
            <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" class="d-none">
                <?php echo csrf_field(); ?>
            </form>
        </li>
        <?php else: ?>
        <li>
            <a href="<?php echo e(route('login')); ?>"><i class="fas fa-sign-in-alt me-2"></i><?php echo e(__('Войти')); ?></a>
        </li>
        <li>
            <a href="<?php echo e(route('register')); ?>"><i class="fas fa-user-plus me-2"></i><?php echo e(__('Регистрация')); ?></a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php /**PATH C:\OSPanel\domains\remont\resources\views/layouts/partials/sidebar.blade.php ENDPATH**/ ?>