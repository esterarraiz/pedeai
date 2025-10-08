<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dashboard-admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <?php include_once __DIR__ . '/../partials/sidebar_admin.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Dashboard</h1>
                <a href="/dashboard/admin/cardapio" class="btn btn-success">
                    <i class="fas fa-book-open"></i> Editar Cardápio
                </a>
            </header>

            <section class="metrics-grid">
                <div class="metric-card faturamento">
                    <h3>Faturamento do dia</h3>
                    <div class="value">
                        R$ 1.275,75 <span class="icon fas fa-dollar-sign"></span>
                    </div>
                </div>

                <div class="metric-card pedidos">
                    <h3>Pedidos em andamento</h3>
                    <div class="value">
                        8
                    </div>
                </div>

                <div class="metric-card mesas">
                    <h3>Mesas Ocupadas</h3>
                    <div class="value">
                        20/60
                    </div>
                </div>
            </section>

            <section class="recent-orders-section">
                <h2>Pedidos em Andamento</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mesa</th>
                            <th>Garçom</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>01</td>
                            <td>João Silva</td>
                            <td>R$ 192,00</td>
                            <td><span class="badge status-pago">Pago</span></td>
                            <td><a href="#" class="action-link">Ver Detalhes</a></td>
                        </tr>
                        <tr>
                            <td>02</td>
                            <td>João Silva</td>
                            <td>R$ 192,00</td>
                            <td><span class="badge status-servido">Servido</span></td>
                            <td><a href="#" class="action-link">Ver Detalhes</a></td>
                        </tr>
                        <tr>
                            <td>05</td>
                            <td>Maria A Santos</td>
                            <td>R$ 192,00</td>
                            <td><span class="badge status-cozinha">Na cozinha</span></td>
                            <td><a href="#" class="action-link">Ver Detalhes</a></td>
                        </tr>
                        <tr>
                            <td>09</td>
                            <td>João Silva</td>
                            <td>R$ 192,00</td>
                            <td><span class="badge status-pago">Pago</span></td>
                            <td><a href="#" class="action-link">Ver Detalhes</a></td>
                        </tr>
                        </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>