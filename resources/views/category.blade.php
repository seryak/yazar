<?php /** @var \App\Models\Yazar\Category $category */ ?>
@extends('layout')

@section('main')
<main role="main" class="flex-auto w-full container max-w-4xl mx-auto py-16 px-6">
    <h1 class="text-4xl">{{$category->title}}</h1>

    <div class="text-2xl border-b border-indigo-200 mb-6 pb-6">
        <p>{{$category->description}}</p>
    </div>

    <?php /** @var \App\Models\Yazar\Page $page */ ?>
    @foreach($category->getItems() as $page)
        <div class="flex flex-col mb-4">
            <p class="m-0">
                <a href="{{$page->slug}}" class="text-2xl text-gray-900 font-semibold">
                    {{ $page->title }} <span class="text-gray-700 font-medium text-base"> - {{ $page->createdAt->format('d.m.Y') }}</span>
                </a>
            </p>
        </div>
    @endforeach
</main>
@endsection
