<!DOCTYPE html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ __('ui.sign_in') }} | ExamPractice</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="login-page">
    <header class="login-header"><a class="brand" href="{{ route('login') }}"><span class="brand-symbol"><i data-lucide="book-open-check"></i></span><span>ExamPractice<small>SYSTEM</small></span></a><form method="POST" action="{{ route('locale') }}" class="language-form">@csrf<i data-lucide="globe"></i><select name="locale" aria-label="{{ __('ui.language') }}" onchange="this.form.submit()">@foreach(['en'=>'English','ja'=>'日本語','id'=>'Indonesia'] as $key=>$label)<option value="{{ $key }}" @selected(app()->getLocale()===$key)>{{ $label }}</option>@endforeach</select></form></header>
    <main class="login-main"><div class="login-form-area"><div class="login-emblem"><i data-lucide="book-open-check"></i></div><h1>{{ __('ui.login_title') }}</h1><p class="muted">{{ __('ui.login_subtitle') }}</p>
    @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
    <form action="{{ route('login') }}" method="POST" class="stack-form" x-data="{show:false}">@csrf
        <label>{{ __('ui.username') }}<input name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="STU-001"></label>
        <label>{{ __('ui.password') }}<div class="password-field"><input :type="show?'text':'password'" type="password" name="password" required autocomplete="current-password"><button class="icon-button" type="button" @click="show=!show" aria-label="{{ __('ui.show_password') }}"><i data-lucide="eye"></i></button></div></label>
        <button class="button primary full" type="submit">{{ __('ui.sign_in') }}<i data-lucide="arrow-right"></i></button>
    </form><p class="login-help">{{ __('ui.login_help') }}</p></div>
    <div class="login-photo"><img src="{{ asset('images/japanese.jpg') }}" alt="Kyoto, Japan"><div class="photo-caption"><span>EXAMPRACTICE SYSTEM</span><strong>{{ __('ui.dashboard_subtitle') }}</strong></div></div></main>
    <footer class="login-footer">ExamPractice System &copy; {{ now()->year }}</footer>
</body></html>
