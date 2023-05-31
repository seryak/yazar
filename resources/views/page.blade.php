@extends('layout')

@section('main')
    <main role="main" class="flex-auto w-full container max-w-4xl mx-auto py-16 px-6">
        <h1 class="leading-none mb-2">{{$page->title}} @if(isset($page->category)) {{ $page->category->title }} @endif</h1>
        <p class="text-gray-700 text-xl md:mt-0">{{$page->createdAt->format('d-m-Y')}}</p>

        <a
            href="/categories/array-and-hashmap"
            title="View posts in array-and-hashmap"
            class="inline-block bg-gray-300 hover:bg-indigo-200 leading-loose tracking-wide text-gray-800 uppercase text-xs font-semibold rounded mr-4 px-3 pt-px"
        >array-and-hashmap</a>

        <div class="border-b border-indigo-200 mb-10 pb-4">
            {!! $page->htmlContent !!}
        </div>

        <nav class="flex justify-between text-sm md:text-base">
            <div>
                <a href="https://one-problem-a-day.netlify.app/problems/find-good-days-to-rob-the-bank" title="Older Post: Find good days to rob the bank">
                    &LeftArrow; Find good days to rob the bank
                </a>
            </div>

            <div>
                <a href="https://one-problem-a-day.netlify.app/problems/maximum-binary-string-after-change" title="Newer Post: Maximum binary string after change">
                    Maximum binary string after change &RightArrow;
                </a>
            </div>
        </nav>
    </main>
@endsection
