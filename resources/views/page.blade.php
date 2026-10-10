@extends('layouts.app')
@section('title', $page->meta_title ?: $page->title)
@section('description', $page->meta_description)
@section('content')
<div class="page legal-page"><div class="post-breadcrumb"><a href="{{ route('index') }}">Home</a></div><h1 class="legal-page__title">{{ $page->title }}</h1><div class="legal-page__body">{!! $page->content !!}</div></div>
@endsection
