<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>{{ config("app.name", "Laravel") }}</title>

        {{-- Fonts & CSS --}}
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
        
        @vite(["resources/css/app.css", "resources/js/app.js"])
    </head>
     <body style="font-family: 'Poppins', sans-serif; background-color: #f8f9fa;">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-6 col-lg-4">
                    <div class="text-center mb-4">
                        <img src="{{ asset('images/TDS-logo-icon.png') }}" alt="Logo Aplikasi" style="width: 150px;">
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            {{-- [PERBAIKAN] Menggunakan @yield untuk konten --}}
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scripts --}}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        @stack("scripts")
    </body>
</html>