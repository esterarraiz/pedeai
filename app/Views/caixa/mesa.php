<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Conta Mesa <?= htmlspecialchars($data['mesa']['numero']) ?></title>
<link rel="stylesheet" href="/css/caixa.css">
<script>
function fecharConta(mesa_id){
    if(confirm('Deseja realmente fechar a conta e liberar a mesa?')){
        fetch('/caixa/mesa/fechar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({mesa_id})
        }).then(res => res.json()).then(data=>{
            alert(data.message);
            if(data.success) location.href='/dashboard/caixa';
        });
    }
}
</script>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-logo"><img src="/images/pedeai-logo.png" alt="Logo"></div>
        <ul class="sidebar-nav">
            <li><a href="/dashboard/caixa" class="active"><i class="fa-solid fa-cash-register"></i><span>Caixa</span></a></li>
            <li><a href="/logout"><i class="fa-solid fa-sign-out-alt"></i><span>Sair</span></a></li>
        </ul>
        <div class="sidebar-user">
            <h4><?= htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário') ?></h4>
            <span>Caixa</span>
        </div>
    </aside>
    <main class="main-content">
        <h1>Conta Mesa <?= str_pad($data['mesa']['numero'], 2, '0', STR_PAD_LEFT) ?></h1>
        <?php if($data['pedido']): ?>
            <table class="pedido-table">
                <thead>
                    <tr><th>Item</th><th>Qtd</th><th>Preço Unit.</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach($data['pedido']['itens'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome']) ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total</strong></td>
                        <td><strong>R$ <?= number_format($data['pedido']['total'], 2, ',', '.') ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            <button class="btn btn-fechar" onclick="fecharConta(<?= $data['mesa']['id'] ?>)">Fechar Conta</button>
        <?php else: ?>
            <p>Não há pedidos nesta mesa.</p>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
