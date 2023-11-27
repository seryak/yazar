<?php
/** @var \App\Models\Yazar\PageDocument $page */ ?>
@extends('layout')

@section('main')
    <main role="main" class="flex-auto w-full container max-w-4xl mx-auto py-16 px-6">
        <h1 class="leading-none mb-2 text-5xl font-bold">{{$page->title}} @if(isset($page->category))
                {{ $page->category->title }}
            @endif</h1>
        <p class="text-gray-700 text-xl md:mt-0 mb-6">{{$page->createdAt->format('d-m-Y')}}</p>

        @if(isset($page->category))
            <a
                href="/{{ $page->category->slug }}"
                title="{{ $page->category->title }}"
                class="inline-block bg-gray-300 hover:bg-indigo-200 leading-loose tracking-wide text-gray-800 uppercase text-xs font-semibold rounded mr-4 mb-6 px-3 pt-px"
            >{{ $page->category->title }}</a>
        @endif

        <div class="border-b border-indigo-200 mb-10 pb-4">
            {!! $page->htmlContent !!}
        </div>

        <nav class="flex justify-between text-sm md:text-base">
            @if($page->previousPage)
                <div>
                    <a href="{{ $page->previousPage->slug }}"
                       title="{{ $page->previousPage->title }}"> {{ $page->previousPage->title }} </a>
                </div>
            @endif

            @if($page->nextPage)
                <div>
                    <a href="{{ $page->nextPage->slug }}"
                       title="{{ $page->nextPage->title }}"> {{ $page->nextPage->title }} </a>
                </div>
            @endif
        </nav>
    </main>
@endsection
