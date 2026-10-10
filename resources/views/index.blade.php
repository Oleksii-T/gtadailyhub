@extends('layouts.app')
@section('title', $page?->meta_title ?: 'GTA VI News — Your connection to Vice City')
@section('description', $page?->meta_description ?: 'Independent GTA VI news, trailer coverage, character stories, and dispatches from Vice City and beyond.')
@section('meta-image', asset('images/gta-vi/poster.webp'))
@section('content')
<div class="page home-page">
    <section class="editorial-intro" aria-labelledby="home-title">
        <div class="eyebrow"><span class="live-dot" aria-hidden="true"></span> Vice City & beyond <span class="eyebrow-rule"></span> Independent GTA VI coverage</div>
        <div class="intro-row"><h1 id="home-title">THE CITY NEVER SLEEPS.<br><span class="sunset-text">NEITHER DO WE.</span></h1><p>The stories. The streets. The next chapter.<br>Your daily connection to the world of<br class="desktop-break"> Grand Theft Auto VI.</p></div>
    </section>
    <section class="lead-section" aria-label="Featured story">
        @php $featureImage = $featuredPost?->thumbnail(); @endphp
        <a class="lead-story" href="{{ $featuredPost ? route('posts.show', $featuredPost) : 'https://www.rockstargames.com/VI' }}" @unless($featuredPost) target="_blank" rel="noopener noreferrer" @endunless>
            <img class="lead-image" src="{{ $featureImage?->url ?: asset('images/gta-vi/poster.webp') }}" alt="{{ $featureImage?->alt ?: ($featuredPost?->title ?: 'Official GTA VI artwork depicting the people and places of Leonida') }}" fetchpriority="high" width="1600" height="900">
            <span class="lead-shade" aria-hidden="true"></span>
            <div class="lead-topline"><span class="story-badge">In the spotlight</span><span class="issue-label">THE VICE CITY DISPATCH / 001</span></div>
            <div class="lead-body">
                <span class="eyebrow">{{ $featuredPost?->tags->first()?->name ?: 'The next chapter' }}</span>
                <h2>{{ $featuredPost?->title ?: 'All roads lead to Vice City.' }}</h2>
                <p>{{ $featuredPost ? Str::limit(strip_tags($featuredPost->sub_title ?: $featuredPost->meta_description), 160) : "Meet Jason and Lucia. Explore the world of Leonida. Start with Rockstar's official look at Grand Theft Auto VI." }}</p>
                <div class="lead-bottom"><span class="story-meta">{{ $featuredPost?->published_at?->format('M j, Y') ?: 'Explore the official GTA VI website' }} @if($featuredPost)<span class="meta-dot">·</span> GTA VI News @endif</span><span class="round-arrow" aria-hidden="true">↗</span></div>
            </div>
        </a>
        <div class="feature-caption"><span><span class="live-dot" aria-hidden="true"></span> All eyes on the next chapter</span><span>Vice City, Leonida ↗</span></div>
    </section>
    <div class="news-layout" id="latest-news">
        <section class="latest-section" aria-labelledby="latest-title">
            <div class="section-heading"><h2 id="latest-title"><span class="section-number">01 /</span> Latest dispatches</h2><a class="text-link" href="{{ route('categories.show', $newsCategory) }}">All news ↗</a></div>
            <div class="posts-grid home-news-grid"><x-post-cards :posts="$latestPosts->skip(1)" />
                @if ($latestPosts->count() <= 1)<div class="empty-state"><span class="eyebrow">The news desk</span><h3>The next story starts here.</h3><p>New dispatches will appear as they're published. In the meantime, explore the official footage below.</p></div>@endif
            </div>
        </section>
        <aside class="news-sidebar" aria-label="Topics and official coverage">
            <div class="section-heading"><h2><span class="section-number">02 /</span> On the radar</h2></div>
            <div class="topic-list">@forelse ($topTags->take(6) as $topic)<a href="{{ route('categories.show.tag', ['category' => $newsCategory->slug, 'tagSlug' => $topic->slug]) }}"><span class="topic-number">{{ sprintf('%02d', $loop->iteration) }}</span><span>{{ $topic->name }}</span><span class="topic-arrow" aria-hidden="true">↗</span></a>@empty<p class="muted-text">Topics appear here as stories are tagged.</p>@endforelse</div>
            <a class="leonida-card" href="https://www.rockstargames.com/VI" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/gta-vi/leonida.webp') }}" alt="A glimpse of Leonida from the official GTA VI website" loading="lazy" width="1600" height="900"><div><span class="eyebrow">People & places</span><h3>ONLY IN<br><span>LEONIDA.</span></h3><p>Get to know the goodtime state.</p><span class="text-link">Explore Rockstar's world ↗</span></div></a>
        </aside>
    </div>
    <section class="trailer-section" aria-labelledby="trailer-title">
        <div class="section-heading"><h2 id="trailer-title"><span class="section-number">03 /</span> Straight from the source</h2><span class="section-note">Rockstar Games · Official footage</span></div>
        <a class="trailer-feature" href="https://www.rockstargames.com/VI" target="_blank" rel="noopener noreferrer"><div class="trailer-media"><img src="{{ asset('images/gta-vi/featured.webp') }}" alt="Official GTA VI promotional footage artwork" loading="lazy" width="1600" height="900"><span class="play-button" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 5 11 7-11 7Z"/></svg></span><span class="media-label">Official GTA VI videos</span></div><div class="trailer-copy"><span class="eyebrow">Welcome to the goodtime state</span><h3>SUNSHINE.<br>BAD DECISIONS.<br><span class="sunset-text">GRAND THEFT AUTO.</span></h3><p>Every frame has a story. Watch the official videos and take a closer look at life in Leonida.</p><span class="text-link">Watch on Rockstar Games ↗</span></div></a>
    </section>
    @if (config('app.digest_testing') && $digests->isNotEmpty())<section class="digest-section"><div class="section-heading"><h2>Digests</h2></div><div class="posts-grid"><x-post-cards :posts="$digests" /></div></section>@endif
</div>
@endsection
