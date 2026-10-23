<!doctype html>
@php
    $primaryColor = $settings->colors->primary_color ?? null;
    $primaryRgb = $primaryColor && preg_match('/^#([0-9a-f]{6})$/i', $primaryColor) ? implode(',', sscanf($primaryColor, '#%02x%02x%02x')) : null;
    $themeDefault = in_array($settings->theme->mode_default ?? 'auto', ['light', 'dark']) ? $settings->theme->mode_default : null;
@endphp
<html lang="{{ getLang() }}" dir="ltr" data-bs-navbar-position="vertical" @if ($themeDefault) data-bs-theme="{{ $themeDefault }}" @endif>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="theme-color" content="{{ $settings->colors->primary_color }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ pageTitle($__env) }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset($settings->media->favicon) }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/toastr/toastr.css') }}">
    @stack('styles_libs')
    <link rel="stylesheet" href="{{ asset('assets/css/user-panel.css') }}">
    @if ($primaryRgb)
        <style>
            :root {
                --tblr-primary: {{ $primaryColor }};
                --tblr-primary-rgb: {{ $primaryRgb }};
            }
        </style>
    @endif
    @stack('styles')
</head>

<body>
    <script src="{{ asset('assets/vendor/libs/tabler/js/tabler-theme.min.js') }}"></script>
    <div class="page">
        @include('frontend.user.includes.sidebar')
        <div class="page-wrapper">
            @include('frontend.user.includes.navbar')
            @hasSection('page-title')
                <div class="page-header d-print-none">
                    <div class="container-xl">
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                @hasSection('page-pretitle')
                                    <div class="page-pretitle">@yield('page-pretitle')</div>
                                @endif
                                <h2 class="page-title">@yield('page-title')</h2>
                            </div>
                            @hasSection('page-actions')
                                <div class="col-auto ms-auto d-print-none">
                                    <div class="btn-list">@yield('page-actions')</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            <div class="page-body">
                <div class="container-xl">
                    @include('frontend.user.includes.subscription-alerts')
                    @yield('content')
                </div>
            </div>
            @include('frontend.user.includes.footer')
        </div>
    </div>
    @include('frontend.configurations.config')
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/tabler/js/tabler.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/clipboard/clipboard.min.js') }}"></script>
    @stack('scripts_libs')
    <script src="{{ asset('assets/js/user-panel.js') }}"></script>
    @stack('scripts')
    @include('frontend.configurations.widgets')
    @toastr_render
</body>

</html>
