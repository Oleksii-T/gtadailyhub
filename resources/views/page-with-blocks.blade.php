@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('description', $page->meta_description)

@section('content')
    <div class="page legal-page">
        <h1 class="legal-page__title">{{ $page->title }}</h1>
        <div class="legal-page__body">
            @if ($blocks->isNotEmpty())
                <x-content-blocks :blocks="$blocks" type="1" />
            @elseif ($page->content)
                {!! $page->content !!}
            @elseif ($page->link === 'about-us')
                <p>GTA VI News is an independent fan publication following the next chapter of Grand Theft Auto. From the streets of Vice City to the wider world of Leonida, we follow the stories, people, and places that make this world worth exploring.</p>
                <section class="cblock"><h2>Closer to the source.</h2><p>Our starting point is Rockstar's official footage, announcements, and artwork. We link to primary sources and distinguish confirmed information from speculation.</p><p>This publication is unaffiliated with Rockstar Games or Take-Two Interactive. Grand Theft Auto and its promotional artwork belong to their respective owners.</p></section>
                <p><a href="{{ route('contact-us') }}">Get in touch</a> or explore the <a href="https://www.rockstargames.com/VI" target="_blank" rel="noopener noreferrer">official GTA VI website</a>.</p>
            @endif
        </div>
    </div>
@endsection
