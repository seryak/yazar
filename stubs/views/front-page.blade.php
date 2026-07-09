@extends('layout')

@section('main')
    <h1>Recent posts</h1>

    @foreach($items as $page)
        <p><a href="{{ $page->slug }}">{{ $page->meta->title }}</a></p>
    @endforeach

    <x-paginator :paginator="$paginator" />
@endsection
