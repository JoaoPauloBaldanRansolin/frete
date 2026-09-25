<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/TabelaFrete.php';
require_once __DIR__ . '/classes/GeradorEtiqueta.php';
require_once __DIR__ . '/classes/Correios.php';
require_once __DIR__ . '/classes/Motoboy.php';
require_once __DIR__ . '/classes/Transportadora.php';

$pdo = conectarBanco();
$transportadoras = $pdo->query('SELECT * FROM transportadoras ORDER BY padrao DESC, nome ASC')->fetchAll();

$correios = new Correios();
$motoboy = new Motoboy();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Envio e Cálculo de Frete</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
  <div class="container py-5" style="max-width: 720px;">
    <h1 class="mb-4 h3">Envio e Cálculo de Frete</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <form id="form-frete">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Mercadoria</label>
              <input type="text" class="form-control" id="mercadoria" placeholder="O que será enviado" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">CEP</label>
              <input type="text" class="form-control" id="cep" placeholder="00000-000" required>
            </div>
          </div>

          <div class="row g-3 mt-1 align-items-end">
            <div class="col-md-8">
              <label class="form-label">Meio de frete</label>
              <select class="form-select" id="frete">
                <option value="correios">Correios</option>
                <option value="motoboy">Motoboy</option>
                <?php foreach ($transportadoras as $t): ?>
                  <option value="transportadora:<?= (int) $t['id'] ?>">
                    <?= htmlspecialchars($t['nome']) ?> (transportadora)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <button type="button" class="btn btn-outline-secondary w-100"
                data-bs-toggle="collapse" data-bs-target="#novaTransportadora">
                + Cadastrar outro meio de frete
              </button>
            </div>
          </div>

          <div class="collapse mt-3" id="novaTransportadora">
            <div class="card card-body bg-white">
              <div class="row g-3">
                <div class="col-md-5">
                  <label class="form-label">Nome da transportadora</label>
                  <input type="text" class="form-control" id="nova-nome">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Taxa fixa (R$)</label>
                  <input type="number" step="0.01" min="0" class="form-control" id="nova-taxa">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Valor por KM (R$)</label>
                  <input type="number" step="0.01" min="0" class="form-control" id="nova-valorkm">
                </div>
                <div class="col-md-1 d-grid">
                  <label class="form-label d-block">&nbsp;</label>
                  <button type="button" class="btn btn-success" id="salvar-transportadora">Salvar</button>
                </div>
              </div>
              <div id="msg-nova-transportadora" class="mt-2 small"></div>
            </div>
          </div>

          <div class="mt-4" id="cartoes-frete">
            <div class="alert alert-info frete-info" data-frete="correios">
              <?= htmlspecialchars($correios->getDescricao()) ?>
            </div>
            <div class="alert alert-info frete-info d-none" data-frete="motoboy">
              <?= htmlspecialchars($motoboy->getDescricao()) ?>
            </div>
            <?php foreach ($transportadoras as $t):
              $obj = new Transportadora($t['nome'], (float) $t['taxa_fixa'], (float) $t['valor_por_km']);
            ?>
              <div class="alert alert-info frete-info d-none" data-frete="transportadora:<?= (int) $t['id'] ?>">
                <?= htmlspecialchars($obj->getDescricao()) ?>
              </div>
            <?php endforeach; ?>
          </div>

          <button type="submit" class="btn btn-primary mt-3">Calcular frete</button>
        </form>

        <div id="resultado" class="mt-4 d-none">
          <div class="alert alert-success">
            <h5 class="alert-heading">Pedido registrado!</h5>
            <p class="mb-1" id="res-texto"></p>
            <p class="mb-0"><strong>Código de rastreio:</strong> <span id="res-codigo"></span></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const freteSelect = document.getElementById('frete');

    function mostrarInfoFrete(valor) {
      document.querySelectorAll('.frete-info').forEach(el => {
        el.classList.toggle('d-none', el.dataset.frete !== valor);
      });
    }

    freteSelect.addEventListener('change', () => mostrarInfoFrete(freteSelect.value));
    mostrarInfoFrete(freteSelect.value);

    document.getElementById('salvar-transportadora').addEventListener('click', async () => {
      const nome = document.getElementById('nova-nome').value.trim();
      const taxa = parseFloat(document.getElementById('nova-taxa').value);
      const valorKm = parseFloat(document.getElementById('nova-valorkm').value);
      const msg = document.getElementById('msg-nova-transportadora');

      if (!nome || isNaN(taxa) || isNaN(valorKm)) {
        msg.className = 'mt-2 small text-danger';
        msg.textContent = 'Preencha todos os campos corretamente.';
        return;
      }

      let resp, data;
      try {
        resp = await fetch('cadastrar_transportadora.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            nome,
            taxa_fixa: taxa,
            valor_por_km: valorKm
          })
        });
        data = await resp.json();
      } catch (err) {
        msg.className = 'mt-2 small text-danger';
        msg.textContent = 'Não foi possível conectar ao servidor.';
        return;
      }

      if (!resp.ok) {
        msg.className = 'mt-2 small text-danger';
        msg.textContent = data.erro || 'Erro ao salvar.';
        return;
      }

      const valorFrete = `transportadora:${data.id}`;

      const option = document.createElement('option');
      option.value = valorFrete;
      option.textContent = `${data.nome} (transportadora)`;
      freteSelect.appendChild(option);

      const info = document.createElement('div');
      info.className = 'alert alert-info frete-info d-none';
      info.dataset.frete = valorFrete;
      info.textContent =
        `${data.nome} (transportadora) cobra R$ ${data.taxa_fixa.toFixed(2).replace('.', ',')} fixos + ` +
        `R$ ${data.valor_por_km.toFixed(2).replace('.', ',')} por KM.`;
      document.getElementById('cartoes-frete').appendChild(info);

      freteSelect.value = valorFrete;
      mostrarInfoFrete(valorFrete);

      document.getElementById('nova-nome').value = '';
      document.getElementById('nova-taxa').value = '';
      document.getElementById('nova-valorkm').value = '';
      msg.className = 'mt-2 small text-success';
      msg.textContent = 'Transportadora cadastrada com sucesso! Já foi selecionada acima.';
      bootstrap.Collapse.getOrCreateInstance(document.getElementById('novaTransportadora')).hide();
    });

    document.getElementById('form-frete').addEventListener('submit', async (e) => {
      e.preventDefault();
      const mercadoria = document.getElementById('mercadoria').value.trim();
      const cep = document.getElementById('cep').value.trim();
      const frete = freteSelect.value;

      const resultado = document.getElementById('resultado');

      let resp, data;
      try {
        resp = await fetch('calcular.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            mercadoria,
            cep,
            frete
          })
        });
        data = await resp.json();
      } catch (err) {
        resultado.className = 'mt-4';
        resultado.innerHTML = `<div class="alert alert-danger">Não foi possível conectar ao servidor.</div>`;
        return;
      }

      if (!resp.ok) {
        resultado.className = 'mt-4';
        resultado.innerHTML = `<div class="alert alert-danger">${data.erro}</div>`;
        return;
      }

      document.getElementById('res-texto').textContent =
        `${data.mercadoria} via ${data.transportadora} até o CEP ${data.cep}: ` +
        `— valor do frete: R$ ${data.valor.toFixed(2).replace('.', ',')}`;
      document.getElementById('res-codigo').textContent = data.codigo;
      resultado.className = 'mt-4';
      resultado.classList.remove('d-none');
    });
  </script>
</body>

</html>