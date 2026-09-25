<?php
trait GeradorEtiqueta
{
    public function gerarCodigoRastreio(): string
    {
        $numero = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        return 'BR' . $numero;
    }
}
