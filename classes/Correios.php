<?php

require_once __DIR__ . '/TabelaFrete.php';
require_once __DIR__ . '/GeradorEtiqueta.php';

class Correios implements TabelaFrete
{
    use GeradorEtiqueta;

    private $taxaFixa = 15.00;
    private $valorPorKm = 1.00;

    public function calcularFrete(float $distanciaEmKm): float
    {
        return $this->taxaFixa + ($this->valorPorKm * $distanciaEmKm);
    }

    public function getNome(): string
    {
        return 'Correios';
    }

    public function getDescricao(): string
    {
        return 'Correios cobra R$ 15,00 fixos + R$ 1,00 por KM.';
    }
}
