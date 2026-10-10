@extends('layouts.app')

@section('title', ($game ? "Latest News on $game->name" : (($tag ? ($tag->name.' ') : '') . $category->meta_title)) . ($currentPage != 1 ? " - Page $currentPage" : ''))
@section('description', ($game ? "Read the most authoritative and fresh news about the $game->name!" : $category->meta_description) . ($currentPage != 1 ? " | Page $currentPage" : ''))
@section('meta-image', $category->meta_thumbnail()?->url)
@if ($currentPage != 1)
    @section('meta-canonical', $category->paginationLink(1, ['game']))
@endif

@section('content')
    <div class="page">
        <div class="cat-header">
            <h1 class="cat-title">
                {{ $game ? "Latest News on $game->name" : $category->name }}
                @if ($tag)
                    > {{ $tag->name }}
                @endif
            </h1>
        </div>

        <form class="archive-search" action="{{ $tagSlug ? route('categories.show.tag', ['category' => $category->slug, 'tagSlug' => $tagSlug]) : route('categories.show', $category) }}" method="get" role="search">
            <label class="sr-only" for="archive-search">Search articles</label>
            <input id="archive-search" type="search" name="search" value="{{ request('search') }}" placeholder="Search the dispatches…" maxlength="150">
            @if(request('game'))<input type="hidden" name="game" value="{{ request('game') }}">@endif
            <button type="submit">Search ↗</button>
        </form>
        @if(request('search'))<p class="search-results-label">{{ $posts->total() }} {{ Str::plural('result', $posts->total()) }} for “{{ request('search') }}”</p>@endif

        {{-- Tag filters --}}
        @if ($topTags->isNotEmpty())
            <nav class="tag-filter-row" aria-label="Filter by topic">
                <a class="tag-filter{{ !$tagSlug ? ' active' : '' }}"
                   href="{{ route('categories.show', $category) . (request()->only('search', 'game') ? '?' . http_build_query(request()->only('search', 'game')) : '') }}">All</a>
                @foreach ($topTags->take(15) as $tag)
                    <a class="tag-filter{{ $tagSlug == $tag->slug ? ' active' : '' }}"
                       href="{{ route('categories.show.tag', ['category' => $category->slug, 'tagSlug' => $tag->slug]) . (request()->only('search', 'game') ? '?' . http_build_query(request()->only('search', 'game')) : '') }}">
                        {{ $tag->name }}
                    </a>
                @endforeach
            </nav>
        @endif

        {{-- Posts grid --}}
        <div class="posts-grid pagination-content" id="posts-container">
            @if($posts->isEmpty())<div class="empty-state"><span class="eyebrow">The news desk</span><h3>No dispatches found.</h3><p>Try another search or explore all the latest stories.</p><a class="text-link" href="{{ route('categories.show', $category) }}">Browse all news ↗</a></div>@endif
            @include('components.post-cards-with-pages', [
                'posts' => $posts,
                'model' => $category,
                'includeQueryParams' => ['game', 'search'],
            ])
        </div>
    </div>
@endsection
