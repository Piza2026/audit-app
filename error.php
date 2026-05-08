<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        .box { max-width: 500px; margin: auto; }
        h1 { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Error <?php echo http_response_code(); ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="dashboard.php">Volver</a>
    </div>
</body>
</html>