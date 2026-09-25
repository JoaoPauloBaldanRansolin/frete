# Envio e Cálculo de Frete

Página única (`index.php`) para digitar a mercadoria e o CEP de destino,
escolher um meio de frete (Correios, JadLog ou Motoboy) — ou cadastrar uma
transportadora nova na hora — e calcular o valor do frete + código de
rastreio, salvando tudo no banco.

## Requisitos

- PHP 8.0+ com a extensão `pdo_pgsql` habilitada
- PostgreSQL 12+

## Como rodar

1. Crie o banco de dados (uma vez só):
   ```bash
   createdb frete
   ```
   (ou, sem o utilitário `createdb`: `psql -U postgres -c "CREATE DATABASE frete;"`)

2. Rode o schema para criar as tabelas dentro do banco `frete`:
   ```bash
   psql -U postgres -d frete -f sql/schema.sql
   ```

3. Ajuste as credenciais em `config/database.php` se necessário, ou exporte
   variáveis de ambiente antes de subir o servidor (todas opcionais, os
   valores abaixo são o padrão):
   ```bash
   export DB_HOST=localhost
   export DB_PORT=5432
   export DB_NAME=frete
   export DB_USER=postgres
   export DB_PASS=
   ```

4. Suba um servidor PHP local a partir da pasta do projeto:
   ```bash
   php -S localhost:8000
   ```

5. Acesse `http://localhost:8000` no navegador.

## Estrutura

```
frete-app/
├── sql/schema.sql               → cria as tabelas transportadoras e pedidos (PostgreSQL)
├── config/database.php          → conexão PDO com o PostgreSQL
├── classes/
│   ├── TabelaFrete.php           → interface: exige calcularFrete($distanciaEmKm)
│   ├── GeradorEtiqueta.php       → trait: gerarCodigoRastreio() (ex: BR123456789)
│   ├── Correios.php              → R$ 15 fixos + R$ 1 por KM
│   ├── Motoboy.php                → R$ 2 por KM (sem fixo)
│   ├── Transportadora.php        → classe genérica: taxa fixa + valor por KM
│   └── Pedido.php                → injeta a TabelaFrete e pede o cálculo
├── helpers/distancia.php        → simula a distância a partir do CEP (mesmo CEP = mesma distância)
├── index.php                    → tela (Bootstrap): inputs, dropdown, cadastro de transportadora
├── calcular.php                 → endpoint: calcula o frete, gera o rastreio e salva o pedido
└── cadastrar_transportadora.php → endpoint: salva uma transportadora nova no banco
```

## Como as classes se encaixam

- `Correios` e `Motoboy` são classes fixas, cada uma com sua própria fórmula
  (`calcularFrete`) e ambas usam a trait `GeradorEtiqueta` para gerar o
  código de rastreio.
- `Transportadora` é a classe genérica (nome + taxa fixa + valor por KM),
  também com `GeradorEtiqueta`. A **JadLog** é a instância "padrão" dela,
  semeada pelo `schema.sql`. Toda transportadora cadastrada pela tela
  ("+ Cadastrar outro meio de frete") vira uma linha na tabela
  `transportadoras` e passa a ser, ela também, uma instância de
  `Transportadora` — por isso aparece no dropdown como
  "Nome (transportadora)", igual à JadLog.
- `Pedido::finalizarPedido($distancia, TabelaFrete $metodoEntrega)` só
  recebe a distância e o objeto de frete (interface `TabelaFrete`), pede o
  cálculo e devolve o valor — ele não sabe nem precisa saber qual
  transportadora está por trás.
- `calcular.php` é quem decide, a partir da opção escolhida no dropdown,
  qual classe instanciar (`Correios`, `Motoboy` ou `Transportadora` com os
  dados vindos do banco), chama `Pedido`, gera o código de rastreio e
  grava tudo na tabela `pedidos`.

## Cálculo de distância

Não há integração com uma API externa de geolocalização. A distância é
gerada de forma determinística a partir dos dígitos do CEP (o mesmo CEP
sempre retorna a mesma distância) e entra na fórmula de cada transportadora
— então o preço final sempre respeita os parâmetros dela (fixo + R$/km).
Para usar dados reais, troque `helpers/distancia.php` por uma chamada a um
serviço de distância de verdade (ex: ViaCEP + Google Distance Matrix /
OpenRouteService).

## Fluxo de uso

1. Preencha **Mercadoria** e **CEP**.
2. Escolha o **meio de frete** no dropdown — a condição de cobrança daquela
   opção aparece logo abaixo.
3. Se quiser usar uma transportadora que ainda não está na lista, clique em
   **"+ Cadastrar outro meio de frete"**, preencha nome / taxa fixa / valor
   por KM e clique em **Salvar** — ela é gravada no banco e já aparece
   selecionada no dropdown, pronta para uso.
4. Clique em **Calcular frete**: o valor do frete e o código de rastreio
   aparecem na tela, e o pedido (mercadoria, CEP, transportadora, distância,
   valor e código) é salvo na tabela `pedidos`.
