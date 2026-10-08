<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('ui.dashboard')) | ExamPractice</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ menuOpen: false }" class="app-body">
    <div class="mobile-overlay" x-show="menuOpen" x-cloak @click="menuOpen=false"></div>
    <aside class="sidebar" :class="{ 'is-open': menuOpen }">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-symbol"><i data-lucide="book-open-check"></i></span><span>ExamPractice<small>SYSTEM</small></span></a>
        <button class="icon-button mobile-close" @click="menuOpen=false" aria-label="{{ __('ui.close') }}"><i data-lucide="x"></i></button>
        <nav class="side-nav" aria-label="{{ __('ui.menu') }}">
            <p class="nav-label">{{ auth()->user()->isAdmin() ? __('ui.management') : __('ui.learning') }}</p>
            <a @class(['nav-item','selected'=>request()->routeIs('dashboard')]) href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard"></i>{{ __('ui.dashboard') }}</a>
            @if(auth()->user()->isAdmin())
                @foreach(['programs'=>'library-big','modules'=>'layers','packages'=>'clipboard-list','questions'=>'circle-help','materials'=>'book-open-text'] as $kind=>$icon)
                    <a @class(['nav-item','selected'=>request()->route('kind')===$kind]) href="{{ route('admin.content.index',$kind) }}"><i data-lucide="{{ $icon }}"></i>{{ __('ui.'.$kind) }}</a>
                @endforeach
                <a @class(['nav-item','selected'=>request()->routeIs('admin.import*')]) href="{{ route('admin.import') }}"><i data-lucide="upload"></i>{{ __('ui.import') }}</a>
                <p class="nav-label">{{ __('ui.students') }}</p>
                @foreach(['students'=>'users','results'=>'chart-no-axes-combined','devices'=>'monitor-smartphone'] as $page=>$icon)
                    <a @class(['nav-item','selected'=>request()->routeIs('admin.'.$page.'*')]) href="{{ route('admin.'.$page) }}"><i data-lucide="{{ $icon }}"></i>{{ __('ui.'.$page) }}</a>
                @endforeach
            @else
                @foreach(['programs'=>'library-big','materials'=>'book-open-text','history'=>'history'] as $page=>$icon)
                    <a @class(['nav-item','selected'=>request()->routeIs($page.'*')]) href="{{ route($page) }}"><i data-lucide="{{ $icon }}"></i>{{ __('ui.'.$page) }}</a>
                @endforeach
            @endif
            <p class="nav-label">{{ __('ui.account') }}</p>
            <a @class(['nav-item','selected'=>request()->routeIs('profile*')]) href="{{ route('profile') }}"><i data-lucide="user-round"></i>{{ __('ui.profile') }}</a>
        </nav>
        <div class="sidebar-bottom"><div class="account-chip"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ __('ui.'.(auth()->user()->isAdmin()?'admin':'student')) }}</small></div></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-item logout-button"><i data-lucide="log-out"></i>{{ __('ui.logout') }}</button></form></div>
    </aside>
    <div class="app-main">
        <header class="topbar">
            <div class="topbar-left"><button class="icon-button mobile-toggle" @click="menuOpen=true" aria-label="{{ __('ui.menu') }}"><i data-lucide="menu"></i></button><span class="breadcrumb">{{ __('ui.'.(auth()->user()->isAdmin()?'admin':'student')) }}<span>/</span><strong>@yield('title',__('ui.dashboard'))</strong></span></div>
            <div class="topbar-right"><form action="{{ route('locale') }}" method="POST" class="language-form">@csrf<i data-lucide="globe"></i><select name="locale" aria-label="{{ __('ui.language') }}" onchange="this.form.submit()">@foreach(['en'=>'English','ja'=>'日本語','id'=>'Indonesia'] as $key=>$label)<option value="{{ $key }}" @selected(app()->getLocale()===$key)>{{ $label }}</option>@endforeach</select></form><a class="avatar small" href="{{ route('profile') }}" aria-label="{{ __('ui.profile') }}">{{ mb_substr(auth()->user()->name,0,1) }}</a></div>
        </header>
        <main class="main-content @yield('main-class')">
            @if(session('success'))<div class="alert success" role="status"><i data-lucide="circle-check"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert error" role="alert"><div><strong>{{ __('ui.validation_error') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
            @yield('content')
        </main>
        <footer class="app-footer"><span>ExamPractice System</span><span>{{ now()->year }}</span></footer>
    </div>
    @stack('scripts')
</body>
</html>
