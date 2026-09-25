CREATE TABLE IF NOT EXISTS transportadoras (
    id            SERIAL PRIMARY KEY,
    nome          VARCHAR(100)  NOT NULL UNIQUE,
    taxa_fixa     NUMERIC(10,2) NOT NULL DEFAULT 0,
    valor_por_km  NUMERIC(10,2) NOT NULL DEFAULT 0,
    padrao        BOOLEAN       NOT NULL DEFAULT FALSE,
    criado_em     TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS pedidos (
    id                  SERIAL PRIMARY KEY,
    mercadoria          VARCHAR(200)  NOT NULL,
    cep                 VARCHAR(9)    NOT NULL,
    transportadora_nome VARCHAR(120)  NOT NULL,
    distancia_km        NUMERIC(8,2)  NOT NULL,
    valor_frete         NUMERIC(10,2) NOT NULL,
    codigo_rastreio     VARCHAR(20)   NOT NULL,
    criado_em           TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_pedidos_cep  ON pedidos(cep);
CREATE INDEX IF NOT EXISTS idx_pedidos_data ON pedidos(criado_em DESC);

INSERT INTO transportadoras (nome, taxa_fixa, valor_por_km, padrao) VALUES
    ('JadLog', 30.00, 0.10, TRUE)
ON CONFLICT (nome) DO NOTHING;
