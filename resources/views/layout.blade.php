<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="description" content="Solve one problem per day">

    <meta property="og:title" content="Shuffle the array | One problem a day"/>
    <meta property="og:type" content="article" />
    <meta property="og:url" content="https://one-problem-a-day.netlify.app/problems/shuffle-the-array"/>
    <meta property="og:description" content="Solve one problem per day" />

    <title>Shuffle the array | One problem a day</title>
    <link rel="icon" href="/assets/images/favicon.ico">

    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,300i,400,400i,600,700,700i,800,800i" rel="stylesheet">
    @vite('resources/js/app.js')
</head>

<body class="flex flex-col justify-between min-h-screen bg-gray-100 text-gray-800 leading-normal font-sans">
<header class="flex items-center shadow bg-white border-b h-24 py-4" role="banner">
    <div class="container flex items-center max-w-8xl mx-auto px-4 lg:px-8">
        <div class="flex items-center">
            <a href="/" title="One problem a day home" class="inline-flex items-center">
                <h1 class="text-lg md:text-2xl text-indigo-500 font-semibold hover:text-indigo-700 my-0">Yazar</h1>
            </a>
        </div>

        <div id="vue-search" class="flex flex-1 justify-end items-center">
            <search></search>

            <nav class="hidden lg:flex items-center justify-end text-lg">
                <a title="One problem a day - Problems" href="/problems"
                   class="ml-6 text-gray-700 hover:text-indigo-600 ">
                    Problems
                </a>
                <a title="One problem a day - About" href="/about"
                   class="ml-6 text-gray-700 hover:text-indigo-600 ">
                    About
                </a>
            </nav>

            <button class="flex justify-center items-center bg-indigo-500 border border-indigo-500 h-10 px-5 rounded-full lg:hidden focus:outline-none"
                    onclick="navMenu.toggle()"
            >
                <svg id="js-nav-menu-show" xmlns="http://www.w3.org/2000/svg"
                     class="fill-current text-white h-9 w-4" viewBox="0 0 32 32"
                >
                    <path d="M4,10h24c1.104,0,2-0.896,2-2s-0.896-2-2-2H4C2.896,6,2,6.896,2,8S2.896,10,4,10z M28,14H4c-1.104,0-2,0.896-2,2  s0.896,2,2,2h24c1.104,0,2-0.896,2-2S29.104,14,28,14z M28,22H4c-1.104,0-2,0.896-2,2s0.896,2,2,2h24c1.104,0,2-0.896,2-2  S29.104,22,28,22z"/>
                </svg>

                <svg id="js-nav-menu-hide" xmlns="http://www.w3.org/2000/svg"
                     class="hidden fill-current text-white h-9 w-4" viewBox="0 0 36 30"
                >
                    <polygon points="32.8,4.4 28.6,0.2 18,10.8 7.4,0.2 3.2,4.4 13.8,15 3.2,25.6 7.4,29.8 18,19.2 28.6,29.8 32.8,25.6 22.2,15 "/>
                </svg>
            </button>

        </div>
    </div>
</header>

<nav id="js-nav-menu" class="w-auto px-2 pt-6 pb-2 bg-gray-200 shadow hidden lg:hidden">
    <ul class="my-0 list-none">
        <li class="pl-4 block">
            <a
                title="One problem a day"
                href="/"
                class="block mt-0 mb-4 text-sm no-underline text-gray-800 hover:text-indigo-500"
            >Home</a>
        </li>
        <li class="pl-4 block">
            <a
                title="One problem a day Problems"
                href="/problems"
                class="block mt-0 mb-4 text-sm no-underline text-gray-800 hover:text-indigo-500"
            >Problems</a>
        </li>
        <li class="pl-4 block">
            <a
                title="One problem a day About"
                href="/about"
                class="block mt-0 mb-4 text-sm no-underline text-gray-800 hover:text-indigo-500"
            >About</a>
        </li>
    </ul>
</nav>

@yield('main')

<footer class="bg-white text-center text-sm mt-12 py-4" role="contentinfo">
    <ul class="flex flex-col md:flex-row justify-center list-none">
        <li class="md:mr-2">
            &copy; <a href="/"> Yazar</a> 2023.
        </li>
    </ul>
</footer>

<script>
    const navMenu = {
        toggle() {
            const menu = document.getElementById('js-nav-menu');
            menu.classList.toggle('hidden');
            menu.classList.toggle('lg:block');
            document.getElementById('js-nav-menu-hide').classList.toggle('hidden');
            document.getElementById('js-nav-menu-show').classList.toggle('hidden');
        },
    }
</script>
</body>
</html>
