@extends('layout')

@section('main')
    @foreach($items as $page)
        <div class="h5"><a href="/{{ $page->url }}">{{ $page->meta->title }}</a></div>
        @if($page->meta->cover_image)
            <div class="cover">
                <img src="{{ $page->meta->cover_image }}" alt="{{ $page->meta->title }}">
            </div>
        @endif
    @endforeach

    <x-paginator :paginator="$paginator" />
@endsection
