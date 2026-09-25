<?php

require_once __DIR__ . '/TabelaFrete.php';

class Pedido
{
    public function finalizarPedido(float $distancia, TabelaFrete $metodoEntrega): float
    {
        return $metodoEntrega->calcularFrete($distancia);
    }
}
