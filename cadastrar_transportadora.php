<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';

$dados = json_decode(file_get_contents('php://input'), true);

$nome = trim($dados['nome'] ?? '');
$taxaFixa = filter_var($dados['taxa_fixa'] ?? null, FILTER_VALIDATE_FLOAT);
$valorPorKm = filter_var($dados['valor_por_km'] ?? null, FILTER_VALIDATE_FLOAT);

if ($nome === '' || $taxaFixa === false || $valorPorKm === false || $taxaFixa < 0 || $valorPorKm < 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'Preencha nome, taxa fixa e valor por KM corretamente.']);
    exit;
}

$pdo = conectarBanco();

try {
    // RETURNING id: no PostgreSQL, PDO::lastInsertId() sem o nome exato da
    // sequence não é confiável. RETURNING é a forma correta de recuperar o
    // id recém-gerado logo após o INSERT.
    $stmt = $pdo->prepare(
        'INSERT INTO transportadoras (nome, taxa_fixa, valor_por_km, padrao)
         VALUES (:nome, :taxa, :valorKm, FALSE)
         RETURNING id'
    );
    $stmt->execute([
        ':nome' => $nome,
        ':taxa' => $taxaFixa,
        ':valorKm' => $valorPorKm,
    ]);

    $id = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    // Código 23505 = violação de UNIQUE (nome já cadastrado)
    if ($e->getCode() === '23505') {
        http_response_code(409);
        echo json_encode(['erro' => 'Já existe uma transportadora cadastrada com esse nome.']);
        exit;
    }

    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar a transportadora: ' . $e->getMessage()]);
    exit;
}

echo json_encode([
    'id' => $id,
    'nome' => $nome,
    'taxa_fixa' => $taxaFixa,
    'valor_por_km' => $valorPorKm,
]);
