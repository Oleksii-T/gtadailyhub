<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <title>@yield('title', (isset($page) ? $page->meta_title : null) ?: config('app.name'))</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="theme-color" content="#111117">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preload" href="{{ asset('fonts/barlow-condensed-bold.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="{{ asset('fonts/dm-sans.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
    @hasSection('meta-image')
        <meta property="og:image" content="@yield('meta-image')">
    @endif
    <meta property="og:title" content="@yield('title', (isset($page) ? $page->meta_title : null) ?: config('app.name'))">
    <meta property="og:description" content="@yield('description', isset($page) ? $page->meta_description : '')">
    <meta name="description" content="@yield('description', isset($page) ? $page->meta_description : '')">
    <meta name="robots" content="@yield('meta-robots', 'index, follow, max-image-preview:large')">
    <meta property="og:url" content="{{ rtrim(config('app.url'), '/') . request()->getRequestUri() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="@yield('meta-type', 'website')">
    <meta property="og:locale" content="en_US">
    @yield('meta')
    <link rel="canonical" href="@yield('meta-canonical', rtrim(config('app.url'), '/') . request()->getRequestUri())">
    @yield('styles')
</head>

<body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <span id="page-top"></span>
    @admin
        @php
            $editUrl = match (Route::currentRouteName()) {
                'games.show' => route('admin.games.edit', $game),
                'posts.show' => route('admin.posts.edit', $post ?? 1),
                'categories.show' => route('admin.categories.edit', $category),
                'authors.show' => route('admin.authors.edit', $author),
                default => null
            };
        @endphp
        <div class="admin-header">
            <div class="admin-header-item">
                <a href="{{route('admin.index')}}">
                    {{config('app.name')}} Admin
                </a>
            </div>
            @if ($editUrl)
                <div class="admin-header-item">
                    <a href="{{$editUrl}}">Edit</a>
                </div>
            @endif
        </div>
    @endadmin

    <x-header />

    <main id="main-content" tabindex="-1">
        @yield('content')
    </main>

    <x-footer />

    @yield('popups')
</body>

<script src="{{asset('js/jquery.min.js')}}"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script src="{{asset('js/lazysizes.min.js')}}"></script>
<script src="{{asset('js/custom.js')}}"></script>
<script src="{{asset('js/site.js')}}" defer></script>

<script>
    window.Laravel = {!!$LaravelDataForJS!!};
</script>

@if (config('services.google_analytics.measurement_id') && !app()->environment('local'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', @json(config('services.google_analytics.measurement_id')));
    </script>
@endif

@yield('scripts')

</html>
