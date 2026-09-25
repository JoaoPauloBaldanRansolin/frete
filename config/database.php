<?php

function conectarBanco(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host   = getenv('DB_HOST') ?: 'localhost';
    $porta  = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'frete';
    $usuario = getenv('DB_USER') ?: 'postgres';
    $senha   = getenv('DB_PASS') ?: '';

    $dsn = "pgsql:host={$host};port={$porta};dbname={$dbname}";

    try {
        $pdo = new PDO($dsn, $usuario, $senha, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode([
            'erro' => 'falha de conexão' . $e->getMessage(),
        ]));
    }
}
