<?php

require_once __DIR__ . '/TabelaFrete.php';
require_once __DIR__ . '/GeradorEtiqueta.php';

class Motoboy implements TabelaFrete
{
    use GeradorEtiqueta;

    private $valorPorKm = 2.00;

    public function calcularFrete(float $distanciaEmKm): float
    {
        return $this->valorPorKm * $distanciaEmKm;
    }

    public function getNome(): string
    {
        return 'Motoboy';
    }

    public function getDescricao(): string
    {
        return 'Motoboy cobra R$ 2,00 por KM.';
    }
}
