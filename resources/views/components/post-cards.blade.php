@foreach ($posts as $post)
    @php $cardImage = $post->thumbnail(); @endphp
    <a class="post-list-card" href="{{ route('posts.show', $post) }}">
        <div class="post-list-card__thumb"><img src="{{ $cardImage?->url ?: asset('images/gta-vi/poster.webp') }}" alt="{{ $cardImage?->alt ?: $post->title }}" loading="lazy" width="800" height="450"><span class="card-arrow" aria-hidden="true">↗</span></div>
        <div class="post-list-card__body"><p class="card-topic">{{ $post->tags->first()?->name ?: $post->category?->name ?: 'GTA VI' }}</p><h2 class="post-list-card__title">{{ $post->title }}</h2><p class="post-list-card__meta"><time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('M j, Y') }}</time><span class="meta-dot">·</span> GTA VI News</p></div>
    </a>
@endforeach
