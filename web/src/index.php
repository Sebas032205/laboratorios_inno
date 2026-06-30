<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Laboratorio</title>
    <link rel="stylesheet" href="frontend/css/estilo.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f4f7f6; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 320px; }
        .registro-link { display: block; margin-top: 10px; font-size: 14px; text-decoration: none; color: #0056b3; }
        .registro-link:hover { text-decoration: underline; }
        .alert-error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-top: 15px; font-size: 14px; text-align: center; }
        .alert-exito { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-top: 15px; font-size: 14px; text-align: center; }
        .enlaces-footer { margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Acceso al Sistema</h2>
        
        <form action="backend/login.php" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required placeholder="admin@laboratorio.com">
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required placeholder="********">
            </div>
            <button type="submit" class="btn-primary">Iniciar Sesión</button>
        </form>

        <div id="contenedor-alerta"></div>

        <script>
            const urlParams = new URLSearchParams(window.location.search);
            const divAlerta = document.getElementById('contenedor-alerta');
            
            // Si PHP nos regresa con un error
            if (urlParams.has('error')) {
                divAlerta.className = 'alert-error';
                divAlerta.textContent = decodeURIComponent(urlParams.get('error'));
            }
            // Si PHP nos regresa con un mensaje positivo (ej: sesión cerrada con éxito)
            if (urlParams.has('mensaje')) {
                divAlerta.className = 'alert-exito';
                divAlerta.textContent = decodeURIComponent(urlParams.get('mensaje'));
            }
        </script>
        
        <div class="enlaces-footer">
            <a href="frontend/registro.html" class="registro-link">¿No tienes cuenta? Regístrate aquí</a>
        </div>
    </div>
</body>
</html>