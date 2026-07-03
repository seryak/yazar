@extends('layout')

@section('main')
    <h1>{{ $page->meta->title }}</h1>

    {!! $page->html_content !!}
@endsection
