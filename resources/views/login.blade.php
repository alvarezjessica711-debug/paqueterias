<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Valle de San Remo</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 24px; min-height: 100vh; box-sizing: border-box; background-image: linear-gradient(rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2)), url('https://www.construespacios.com/wp-content/uploads/2025/01/WhatsApp-Image-2024-03-13-at-2.06.15-AM-2.jpg'); background-size: cover; background-position: center; background-attachment: fixed; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        header { text-align: center; margin-bottom: 25px; }
        header h1 { color: white; font-size: clamp(2rem, 8vw, 2.8rem); margin: 0; text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.6); }
        header p, footer { color: white; text-shadow: 1px 2px 5px rgba(0, 0, 0, 0.5); }
        main { background-color: rgba(255, 255, 255, 0.65); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); padding: 40px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.4); width: min(100%, 400px); box-sizing: border-box; text-align: center; }
        h2 { color: #1a5276; margin-bottom: 30px; font-size: 1.8rem; }
        label { display: block; text-align: left; margin-bottom: 8px; color: #2c3e50; font-weight: 600; font-size: 0.95rem; }
        input { width: 100%; padding: 14px; margin-bottom: 25px; border: 1px solid rgba(0, 0, 0, 0.1); background: rgba(255, 255, 255, 0.8); border-radius: 12px; box-sizing: border-box; font-size: 15px; color: #2c3e50; }
        input:focus { outline: none; border-color: #2980b9; box-shadow: 0 0 8px rgba(41, 128, 185, 0.3); }
        button { width: 100%; background: linear-gradient(135deg, #2980b9 0%, #3498db 100%); color: white; padding: 16px; border: none; border-radius: 12px; font-size: 17px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(41, 128, 185, 0.3); margin-bottom: 15px; }
        button:hover { background: linear-gradient(135deg, #1b4f72 0%, #2980b9 100%); transform: translateY(-2px); }
        .link-secundario { color: #1a5276; text-decoration: none; font-size: 0.9rem; }
        footer { margin-top: 30px; text-align: center; font-size: 0.85rem; }
        @media (max-width: 480px) { body { padding: 16px; } main { padding: 28px 22px; } }
    </style>
</head>
<body>
    <header><h1>Valle de San Remo</h1><p>Control de Correspondencia y Paquetes</p></header>
    <main>
        <h2>Iniciar sesión</h2>
        <form action="{{ route('login.store') }}" method="post">
            @csrf
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Ej: vigilante@valledesanremo.test" required autofocus>
            @error('email')
                <p style="color: #b91c1c; font-size: 0.9rem; margin: -15px 0 18px; text-align: left;">{{ $message }}</p>
            @enderror
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" placeholder="••••••••••••" required>
            <button type="submit">Iniciar sesión</button>
            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; font-weight: 400;"><input name="remember" type="checkbox" style="width: auto; margin: 0;"> Recordarme</label>
        </form>
    </main>
</body>
</html>
