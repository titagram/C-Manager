<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - C-Manager Demo</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        /* Override manuali per bypassare build */
        .form-input-sm,
        .form-select-sm {
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            font-size: 0.875rem !important;
            /* text-sm leggermente più leggibile di xs */
            height: 2rem !important;
        }

        /* Nascondi spinner numerici */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="min-h-screen bg-background">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        <!-- Mobile Overlay -->
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
            class="fixed inset-0 bg-foreground/50 z-40 lg:hidden"></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-sidebar flex flex-col transform transition-transform duration-300 ease-in-out">
            <!-- Logo -->
            <div class="p-4 lg:p-6 border-b border-sidebar-border">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-sidebar-primary flex items-center justify-center">
                            <svg class="w-6 h-6 text-sidebar-primary-foreground" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.115 5.19l.319 1.913A6 6 0 008.11 10.36L9.75 12l-.387.775c-.217.433-.132.956.21 1.298l1.348 1.348c.21.21.329.497.329.795v1.089c0 .426.24.815.622 1.006l.153.076c.433.217.956.132 1.298-.21l.723-.723a8.7 8.7 0 002.288-4.042 1.087 1.087 0 00-.358-1.099l-1.33-1.108c-.251-.21-.582-.299-.905-.245l-1.17.195a1.125 1.125 0 01-.98-.314l-.295-.295a1.125 1.125 0 010-1.591l.13-.132a1.125 1.125 0 011.3-.21l.603.302a.809.809 0 001.086-1.086L14.25 7.5l1.256-.837a4.5 4.5 0 001.528-1.732l.146-.292M6.115 5.19A9 9 0 1017.18 4.64M6.115 5.19A8.965 8.965 0 0112 3c1.929 0 3.716.607 5.18 1.64" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="font-semibold text-sidebar-foreground text-sm">C-Manager</h1>
                            <p class="text-xs text-sidebar-foreground/60">Portfolio Demo</p>
                        </div>
                    </div>
                    <button @click="sidebarOpen = false"
                        class="lg:hidden p-2 hover:bg-sidebar-accent rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-sidebar-foreground" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4">
                @php
                    $isAdmin = auth()->user()?->isAdmin() ?? false;
                @endphp
                <ul class="space-y-1">
                    @if($isAdmin)
                        <li>
                            <a href="{{ route('dashboard') }}"
                                class="nav-item {{ request()->routeIs('dashboard') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{ route('magazzino.index') }}"
                            class="nav-item {{ request()->routeIs('magazzino.*') ? 'nav-item-active' : '' }}">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span>Magazzino</span>
                        </a>
                    </li>
                    @if($isAdmin)
                        <li>
                            <a href="{{ route('fitok.index') }}"
                                class="nav-item {{ request()->routeIs('fitok.*') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <span>Registro FITOK</span>
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{ route('lotti.index') }}"
                            class="nav-item {{ request()->routeIs('lotti.*') ? 'nav-item-active' : '' }}">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3" />
                            </svg>
                            <span>Lotti Produzione</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('bom.index') }}"
                            class="nav-item {{ request()->routeIs('bom.*') ? 'nav-item-active' : '' }}">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            <span>Distinte Base</span>
                        </a>
                    </li>
                    @if($isAdmin)
                        <!-- Vendite Section -->
                        <li class="pt-4 mt-4 border-t border-sidebar-border">
                            <div
                                class="px-3 mb-2 text-xs font-semibold text-sidebar-foreground/50 uppercase tracking-wider">
                                Vendite
                            </div>
                        </li>
                        <li>
                            <a href="{{ route('preventivi.index') }}"
                                class="nav-item {{ request()->routeIs('preventivi.*') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z" />
                                </svg>
                                <span>Preventivi</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ordini.index') }}"
                                class="nav-item {{ request()->routeIs('ordini.*') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                                </svg>
                                <span>Ordini</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('fornitori.index') }}"
                                class="nav-item {{ request()->routeIs('fornitori.*') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                </svg>
                                <span>Fornitori</span>
                            </a>
                        </li>

                        <!-- Anagrafica Section -->
                        <li class="pt-4 mt-4 border-t border-sidebar-border">
                            <div
                                class="px-3 mb-2 text-xs font-semibold text-sidebar-foreground/50 uppercase tracking-wider">
                                Anagrafica
                            </div>
                        </li>
                        <li>
                            <a href="{{ route('clienti.index') }}"
                                class="nav-item {{ request()->routeIs('clienti.*') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                <span>Clienti</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('prodotti.index') }}"
                                class="nav-item {{ request()->routeIs('prodotti.*') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                </svg>
                                <span>Materiali</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('costruzioni.index') }}"
                                class="nav-item {{ request()->routeIs('costruzioni.*') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                <span>Costruzioni</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings.production') }}"
                                class="nav-item {{ request()->routeIs('settings.*') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.282c.066.395.336.726.707.87a8.995 8.995 0 011.93 1.11c.326.238.754.286 1.124.126l1.2-.513a1.125 1.125 0 011.454.505l1.296 2.244a1.125 1.125 0 01-.214 1.431l-.987.987a1.125 1.125 0 00-.26 1.17c.08.25.146.505.197.764.06.305.24.57.5.733l1.052.657c.468.293.662.882.461 1.394l-.857 2.18a1.125 1.125 0 01-1.294.686l-1.295-.287a1.125 1.125 0 00-1.074.337 9.057 9.057 0 01-1.421 1.108 1.125 1.125 0 00-.505.86l-.11 1.323a1.125 1.125 0 01-1.12 1.03h-2.592a1.125 1.125 0 01-1.12-1.03l-.11-1.323a1.125 1.125 0 00-.504-.86 9.057 9.057 0 01-1.422-1.108 1.125 1.125 0 00-1.073-.337l-1.296.287a1.125 1.125 0 01-1.294-.686l-.857-2.18a1.125 1.125 0 01.462-1.394l1.05-.657a1.125 1.125 0 00.502-.733 8.955 8.955 0 01.198-.764 1.125 1.125 0 00-.26-1.17l-.987-.987a1.125 1.125 0 01-.214-1.431L2.49 7.32a1.125 1.125 0 011.454-.505l1.2.513a1.125 1.125 0 001.124-.126 8.995 8.995 0 011.93-1.11c.37-.144.64-.475.707-.87l.213-1.282z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" />
                                </svg>
                                <span>Settings Produzione</span>
                            </a>
                        </li>

                        <!-- Help Section -->
                        <li class="pt-4 mt-4 border-t border-sidebar-border">
                            <a href="{{ route('istruzioni') }}"
                                class="nav-item {{ request()->routeIs('istruzioni') ? 'nav-item-active' : '' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                </svg>
                                <span>Istruzioni</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </nav>

            <!-- User Info -->
            @auth
            <div class="p-4 border-t border-sidebar-border">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-sidebar-accent flex items-center justify-center">
                        <span class="text-xs font-medium text-sidebar-foreground">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-sidebar-foreground truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-sidebar-foreground/60">{{ auth()->user()->role->label() }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 hover:bg-sidebar-accent rounded-lg transition-colors"
                            title="Logout">
                            <svg class="w-4 h-4 text-sidebar-foreground/60" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @endauth

            <!-- Footer -->
            <div class="p-4 border-t border-sidebar-border">
                <p class="text-xs text-sidebar-foreground/50 text-center">
                    Gestionale v1.0
                </p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 overflow-auto">
            <!-- Mobile Header -->
            <header
                class="lg:hidden sticky top-0 z-30 bg-background border-b border-border p-4 flex items-center gap-4">
                <button @click="sidebarOpen = true" class="p-2 hover:bg-muted rounded-lg transition-colors">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.115 5.19l.319 1.913A6 6 0 008.11 10.36L9.75 12l-.387.775c-.217.433-.132.956.21 1.298l1.348 1.348c.21.21.329.497.329.795v1.089c0 .426.24.815.622 1.006l.153.076c.433.217.956.132 1.298-.21l.723-.723a8.7 8.7 0 002.288-4.042 1.087 1.087 0 00-.358-1.099l-1.33-1.108c-.251-.21-.582-.299-.905-.245l-1.17.195a1.125 1.125 0 01-.98-.314l-.295-.295a1.125 1.125 0 010-1.591l.13-.132a1.125 1.125 0 011.3-.21l.603.302a.809.809 0 001.086-1.086L14.25 7.5l1.256-.837a4.5 4.5 0 001.528-1.732l.146-.292M6.115 5.19A9 9 0 1017.18 4.64M6.115 5.19A8.965 8.965 0 0112 3c1.929 0 3.716.607 5.18 1.64" />
                    </svg>
                    <span class="font-semibold text-sm">C-Manager Demo</span>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-4 md:p-6 lg:p-8 animate-fade-in">
                <!-- Flash Messages -->
                <!-- Toast Notifications -->
                <x-toast />

                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>

</html>
