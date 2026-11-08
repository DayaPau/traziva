<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · Traziva</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center">
<form method="POST" action="{{ url('/login') }}" class="bg-white rounded-xl shadow p-8 w-full max-w-sm space-y-4">
    @csrf
    <h1 class="text-center text-agro-primary">Traziva</h1>
    <p class="text-center text-sm">Trazabilidad agrícola · Amanecer Biocentro Académico</p>
    @if($errors->any())<div class="p-2 rounded bg-red-100 text-red-900 text-sm">{{ $errors->first() }}</div>@endif
    <input name="email" type="email" value="{{ old('email') }}" placeholder="Correo" required class="w-full border rounded px-3 py-2">
    <input name="password" type="password" placeholder="Contraseña" required class="w-full border rounded px-3 py-2">
    <button class="btn-primary w-full">Ingresar</button>
</form>
</body>
</html>
