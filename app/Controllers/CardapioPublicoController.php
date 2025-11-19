<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CardapioModel;
use App\Models\EmpresaModel; // Precisará de um Model simples para buscar o nome da empresa
use Config\Database;
use Dompdf\Dompdf; // Importa a biblioteca de PDF

class CardapioPublicoController extends Controller
{
    /**
     * Exibe a página pública do cardápio.
     */
    public function index($params)
    {
        $empresa_id = $params['id'] ?? null;
        if (!$empresa_id) {
            $this->loadView('error/404');
            return;
        }

        $pdo = Database::getConnection();
        $cardapioModel = new CardapioModel($pdo);
        $empresaModel = new EmpresaModel($pdo); // Assume que este Model existe

        $empresa = $empresaModel->buscarPorId($empresa_id);
        $cardapio = $cardapioModel->buscarItensAgrupados($empresa_id);

        if (!$empresa) {
            $this->loadView('error/404');
            return;
        }

        // Carrega uma view especial 'cardapio_publico' (sem sidebar)
        $this->loadView('cliente/cardapio', [
            'empresa' => $empresa,
            'cardapio' => $cardapio
        ]);
    }

    /**
     * Gera e envia o PDF do cardápio para o navegador.
     */
    public function gerarPDF($params)
    {
        $empresa_id = $params['id'] ?? null;
        if (!$empresa_id) {
            $this->loadView('error/404');
            return;
        }

        $pdo = Database::getConnection();
        $cardapioModel = new CardapioModel($pdo);
        $empresaModel = new EmpresaModel($pdo);

        $empresa = $empresaModel->buscarPorId($empresa_id);
        $cardapio = $cardapioModel->buscarItensAgrupados($empresa_id);

        if (!$empresa) {
            $this->loadView('error/404');
            return;
        }

        // --- Geração do HTML para o PDF ---
        $html = "<h1>Cardápio - " . htmlspecialchars($empresa['nome_empresa']) . "</h1>";
        $html .= "<style> body { font-family: sans-serif; } h2 { background-color: #f4f4f4; padding: 10px; } ul { list-style: none; padding-left: 0; } li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #ccc; } li span:first-child { font-weight: bold; } </style>";

        foreach ($cardapio as $categoria => $itens) {
            $html .= "<h2>" . htmlspecialchars($categoria) . "</h2><ul>";
            foreach ($itens as $item) {
                $html .= "<li>
                            <span>" . htmlspecialchars($item['nome']) . "</span>
                            <span>R$ " . number_format($item['preco'], 2, ',', '.') . "</span>
                          </li>";
                if ($item['descricao']) {
                     $html .= "<li style='border: none; padding-top: 0; color: #555;'><small>" . htmlspecialchars($item['descricao']) . "</small></li>";
                }
            }
            $html .= "</ul>";
        }
        // --- Fim da Geração do HTML ---

        // Configura o Dompdf
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Envia o PDF para o navegador (force download)
        $dompdf->stream("cardapio_" . $empresa['id'] . ".pdf", ["Attachment" => true]);
    }
}