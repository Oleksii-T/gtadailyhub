@extends('layouts.app')
@section('title', 'This road is closed. — GTA VI News')
@section('meta-robots', 'noindex, follow')
@section('content')
<div class="page error-page"><span class="eyebrow">403 / GTA VI News</span><h1 class="cat-title">This road is closed.</h1><p>You don’t have access to this page.</p><a class="text-link" href="{{ route('index') }}">Back to Vice City ↗</a></div>
@endsection
