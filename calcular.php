<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';

$dados = json_decode(file_get_contents('php://input'), true);

$mercadoria = trim($dados['mercadoria'] ?? '');
$cep = trim($dados['cep'] ?? '');
$frete = trim($dados['frete'] ?? '');

if ($mercadoria === '' || $cep === '' || $frete === '') {
    http_response_code(422);

    echo json_encode([
        'erro' => 'Preencha a mercadoria, o CEP e selecione o meio de frete.'
    ]);

    exit;
}

try {

    $valorFrete = random_int(2000, 50000) / 100;

    $codigoRastreio = 'BR' . str_pad(
        (string) random_int(0, 999999999),
        9,
        '0',
        STR_PAD_LEFT
    );

    if ($frete === 'correios') {

        $nomeTransportadora = 'Correios';
    } elseif ($frete === 'motoboy') {

        $nomeTransportadora = 'Motoboy';
    } elseif (strpos($frete, 'transportadora:') === 0) {

        $id = (int) explode(':', $frete)[1];

        $pdo = conectarBanco();

        $stmt = $pdo->prepare(
            'SELECT nome FROM transportadoras WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $transportadora = $stmt->fetch();

        if (!$transportadora) {
            http_response_code(404);

            echo json_encode([
                'erro' => 'Transportadora não encontrada.'
            ]);

            exit;
        }

        $nomeTransportadora = $transportadora['nome'];
    } else {

        $nomeTransportadora = 'Frete';
    }

    echo json_encode([
        'mercadoria' => $mercadoria,
        'cep' => $cep,
        'transportadora' => $nomeTransportadora,
        'valor' => round($valorFrete, 2),
        'codigo' => $codigoRastreio
    ]);
} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'erro' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
