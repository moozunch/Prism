<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'PRISM') }} - Sistem Manajemen Proyek</title>

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class', // <--- UBAH JADI 'class' BUKAN 'media'
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Instrument Sans', 'sans-serif'],
                        },
                        colors: {
                            primary: {
                                500: '#3b82f6', 
                                600: '#2563eb',
                            },
                            gray: {
                                900: '#111827',
                                950: '#030712', 
                            }
                        }
                    }
                }
            }
        </script>

        <!-- Script untuk membaca theme dari Filament (Local Storage) -->
        <script>
            if (
                localStorage.getItem('theme') === 'dark' || 
                (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
            ) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="antialiased bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100 transition-colors duration-300">
        <div class="min-h-screen flex flex-col items-center">
            
            <!-- Header -->
            <header class="w-full py-6 px-6 sm:px-10 flex justify-between items-center border-b border-gray-200 dark:border-white/10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-md sticky top-0 z-50">
                <div class="flex items-center space-x-2">
                    <svg class="w-8 h-8 text-primary-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 8H19M5 8C3.89543 8 3 7.10457 3 6C3 4.89543 3.89543 4 5 4H19C20.1046 4 21 4.89543 21 6C21 7.10457 20.1046 8 19 8M5 8V18C5 19.1046 5.89543 20 7 20H17C18.1046 20 19 19.1046 19 18V8M10 12H14"></path>
                    </svg>
                    <span class="text-xl font-bold tracking-widest">{{ config('app.name', 'PRISM') }}</span>
                </div>
                
                <div>
                    @auth
                        <a href="{{ url('/admin') }}" class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-500 px-4 py-2 rounded-lg text-sm font-semibold transition bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700">Masuk Dashboard</a>
                    @else
                        <a href="{{ url('/admin/login') }}" class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-500 px-4 py-2 rounded-lg text-sm font-semibold transition bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700">Log in</a>
                    @endauth
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 flex flex-col items-center justify-center w-full px-6 sm:px-10 py-16">
                <div class="max-w-4xl w-full">
                    <div class="text-center mb-16">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6 tracking-tight">
                            Manajemen Proyek <br/>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-500 to-primary-600">Lebih Terstruktur</span>
                        </h1>
                        <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                            Tingkatkan produktivitas tim Anda dengan {{ config('app.name', 'PRISM') }}. Pantau tugas, kelola divisi, dan visualisasikan progres dalam satu ekosistem cerdas.
                        </p>
                    </div>

                    <!-- Cards Feature mirip Widget Filament -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
                        
                        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/10 hover:border-primary-500 dark:hover:border-primary-500 transition-colors duration-300">
                            <div class="w-12 h-12 bg-primary-50 dark:bg-primary-500/10 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold dark:text-white mb-2">Manajemen Tugas</h2>
                            <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">Kelola tiket tugas, delegasikan ke anggota tim, dan pantau status penyelesaiannya secara real-time.</p>
                        </div>

                        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/10 hover:border-primary-500 dark:hover:border-primary-500 transition-colors duration-300">
                            <div class="w-12 h-12 bg-primary-50 dark:bg-primary-500/10 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 20H22V18C22 16.3431 20.6569 15 19 15C18.0444 15 17.1931 15.4468 16.6438 16.1429M17 20H7M17 20V18C17 17.3438 16.8736 16.717 16.6438 16.1429M7 20H2V18C2 16.3431 3.34315 15 5 15C5.95561 15 6.80686 15.4468 7.35625 16.1429M7 20V18C7 17.3438 7.12642 16.717 7.35625 16.1429M7.35625 16.1429C8.0935 14.301 9.89482 13 12 13C14.1052 13 15.9065 14.301 16.6438 16.1429M15 7C15 8.65685 13.6569 10 12 10C10.3431 10 9 8.65685 9 7C9 5.34315 10.3431 4 12 4C13.6569 4 15 5.34315 15 7ZM21 10C21 11.1046 20.1046 12 19 12C17.8954 12 17 11.1046 17 10C17 8.89543 17.8954 8 19 8C20.1046 8 21 8.89543 21 10ZM7 10C7 11.1046 6.10457 12 5 12C3.89543 12 3 11.1046 3 10C3 8.89543 3.89543 8 5 8C6.10457 8 7 8.89543 7 10Z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold dark:text-white mb-2">Kolaborasi Antar Divisi</h2>
                            <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">Sistem terintegrasi dengan akses Role-based (Filament Shield) untuk memastikan keamanan data tiap divisi.</p>
                        </div>

                        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/10 hover:border-primary-500 dark:hover:border-primary-500 transition-colors duration-300">
                            <div class="w-12 h-12 bg-primary-50 dark:bg-primary-500/10 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-primary-600 dark:text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 19V13C9 11.8954 8.10457 11 7 11H5C3.89543 11 3 11.8954 3 13V19C3 20.1046 3.89543 21 5 21H7C8.10457 21 9 20.1046 9 19ZM9 19V9C9 7.89543 9.89543 7 11 7H13C14.1046 7 15 7.89543 15 9V19M9 19C9 20.1046 9.89543 21 11 21H13C14.1046 21 15 20.1046 15 19M15 19V5C15 3.89543 15.8954 3 17 3H19C20.1046 3 21 3.89543 21 5V19C21 20.1046 20.1046 21 19 21H17C15.8954 21 15 20.1046 15 19Z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold dark:text-white mb-2">Visualisasi Gantt Chart</h2>
                            <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">Pantau timeline pengerjaan dengan grafik yang interaktif agar tidak ada deadline yang terlewatkan.</p>
                        </div>
                    </div>

                    <div class="flex flex-col items-center">
                        <a href="/admin" class="bg-primary-600 text-white font-semibold py-3 px-8 rounded-xl shadow-lg hover:bg-primary-500 hover:-translate-y-0.5 hover:shadow-primary-500/25 transition-all duration-300 text-lg">
                            Buka Workspace Anda
                        </a>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="w-full py-8 px-6 sm:px-10 border-t border-gray-200 dark:border-white/10 mt-auto">
                <div class="max-w-4xl mx-auto text-center">
                    <p class="text-gray-500 dark:text-gray-400 text-sm">© {{ date('Y') }} {{ config('app.name', 'PRISM') }}. Hak Cipta Dilindungi.</p>
                </div>
            </footer>
        </div>
    </body>
</html>