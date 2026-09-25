<?php

require_once __DIR__ . '/TabelaFrete.php';
require_once __DIR__ . '/GeradorEtiqueta.php';

class Transportadora implements TabelaFrete
{
    use GeradorEtiqueta;

    private $nome;
    private $taxaFixa;
    private $valorPorKm;

    public function __construct(string $nome, float $taxaFixa, float $valorPorKm)
    {
        $this->nome = $nome;
        $this->taxaFixa = $taxaFixa;
        $this->valorPorKm = $valorPorKm;
    }

    public function calcularFrete(float $distanciaEmKm): float
    {
        return $this->taxaFixa + ($this->valorPorKm * $distanciaEmKm);
    }

    public function getNome(): string
    {
        return $this->nome . ' (transportadora)';
    }

    public function getDescricao(): string
    {
        return sprintf(
            '%s (transportadora) cobra R$ %s fixos + R$ %s por KM.',
            $this->nome,
            number_format($this->taxaFixa, 2, ',', '.'),
            number_format($this->valorPorKm, 2, ',', '.')
        );
    }
}
