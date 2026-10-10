<header class="site-header">
    <div class="topbar">
        <a class="brand" href="{{ route('index') }}" aria-label="GTA VI News home"><span class="brand-mark" aria-hidden="true">VI</span><span class="brand-name">GTA VI<span>NEWS</span></span></a>
        <button class="menu-toggle" type="button" aria-controls="primary-nav" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button>
        <nav class="primary-nav" id="primary-nav" aria-label="Primary navigation">
            <a class="nav-link{{ Route::currentRouteName() === 'index' ? ' active' : '' }}" href="{{ route('index') }}" @if(Route::currentRouteName() === 'index') aria-current="page" @endif>Home</a>
            @foreach ($headerCategories as $cat)
                <a class="nav-link{{ request()->segment(1) === $cat->slug && !request()->route('tagSlug') ? ' active' : '' }}" href="{{ route('categories.show', $cat) }}">{{ $cat->slug === 'news' ? 'Latest news' : $cat->name }}</a>
            @endforeach
            @foreach ($topTags->take(3) as $navTag)
                <a class="nav-link{{ request()->route('tagSlug') === $navTag->slug ? ' active' : '' }}" href="{{ route('categories.show.tag', ['category' => 'news', 'tagSlug' => $navTag->slug]) }}">{{ $navTag->name }}</a>
            @endforeach
            <a class="nav-link{{ Route::currentRouteName() === 'about-us' ? ' active' : '' }}" href="{{ route('about-us') }}">About</a>
        </nav>
        <details class="header-search">
            <summary aria-label="Open article search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/></svg></summary>
            <form class="search-form" action="{{ route('categories.show', ['category' => 'news']) }}" method="get" role="search"><label for="header-search-input">Search the news</label><div class="search-input-row"><input id="header-search-input" type="search" name="search" value="{{ request('search') }}" placeholder="Trailers, Vice City, Lucia…" required maxlength="150"><button type="submit">Search <span aria-hidden="true">↗</span></button></div></form>
        </details>
        <span class="topbar-note"><span class="live-dot" aria-hidden="true"></span> Live from Leonida</span>
    </div>
</header>
