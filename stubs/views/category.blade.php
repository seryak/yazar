@extends('layout')

@section('main')
    <h1>{{ $category->meta->title }}</h1>

    @foreach($pages as $page)
        <p><a href="/{{ $page->url }}">{{ $page->meta->title }}</a></p>
    @endforeach

    <x-paginator :paginator="$paginator" />
@endsection
