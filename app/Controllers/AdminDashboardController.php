<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\EmpresaModel;
use Config\Database;
use Dompdf\Dompdf;
use Dompdf\Options;



class AdminDashboardController extends Controller
{

    public function __construct($route_params = [])
    {
        parent::__construct($route_params); 
        
        $this->requireLogin();
        
        if ($_SESSION['user_cargo'] !== 'administrador') {
            header('Location: /acesso-negado');
            exit;
        }
    }

    public function index()
    {
        $this->loadView('dashboard/admin', ['pageTitle' => 'Dashboard Administrador']);
    }

    public function gerenciarCardapio()
    {
        $pdo = Database::getConnection();

        $empresaModel = new EmpresaModel($pdo);

        $empresa = $empresaModel->buscarPorId($_SESSION['empresa_id']);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];

        $empresa['link_publico'] = $scheme . '://' . $host . '/cardapio/' . $empresa['id'];
                $empresa['link_pdf'] = '/cardapio/' . $empresa['id'] . '/pdf';

        $data['empresa'] = $empresa;

        $this->loadView('admin/cardapio', $data);
    }
    public function gerarQrCodePdf()
    {
        // Lê JSON
        $input = json_decode(file_get_contents('php://input'), true);

        $qrBase64 = $input['qr'] ?? null;
        $titulo = $input['titulo'] ?? 'Escaneie o QR code e confira nosso cardápio!';
        $companyName = $input['companyName'] ?? ($_SESSION['empresa_nome'] ?? '');

        if (!$qrBase64 || strpos($qrBase64, 'data:image') !== 0) {
            http_response_code(400);
            echo json_encode(['error' => 'QR Code inválido']);
            return;
        }

        // Extrai apenas o conteúdo base64 (remove data:image/png;base64,)
        if (preg_match('/^data:image\/(png|jpeg|jpg);base64,(.*)$/', $qrBase64, $matches)) {
            $imgType = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $imgData = base64_decode($matches[2]);
            if ($imgData === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Base64 inválido']);
                return;
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de imagem não suportado']);
            return;
        }

        // Salva arquivo temporário (garantir pasta /tmp ou storage/temp acessível)
        $tmpDir = sys_get_temp_dir(); // ou use um path do seu projeto (garanta permissão)
        $tmpFile = tempnam($tmpDir, 'qrcode_') . '.' . $imgType;
        file_put_contents($tmpFile, $imgData);

        // Monta HTML referenciando o arquivo temporário (uso file://)
        $html = '
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, Helvetica, sans-serif; text-align: center; padding: 40px; color: #222; }
                .company { font-size: 26px; font-weight: 700; margin-bottom: 8px; }
                .title { font-size: 18px; margin-bottom: 18px; color:#444; }
                .qr { margin-top: 20px; }
                .footer { margin-top: 24px; font-size: 12px; color:#666; }
                img { width: 250px; height: 250px; }
            </style>
        </head>
        <body>
            <div class="company">'.htmlspecialchars($companyName).'</div>
            <div class="title">'.htmlspecialchars($titulo).'</div>
            <div class="qr">
                <img src="file://'.addslashes($tmpFile).'" alt="QR Code">
            </div>
            <div class="footer">Escaneie o QR Code para acessar o cardápio digital.</div>
        </body>
        </html>';

        // Configurações do Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', '/'); // permitir file:// (ajuste se necessário)
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');

        // Limpa qualquer buffer que possa corromper o PDF
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            $dompdf->render();
        } catch (\Exception $e) {
            // Remover arquivo tmp antes de retornar
            @unlink($tmpFile);
            error_log('Dompdf erro: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return;
        }

        $pdfOutput = $dompdf->output();

        // Envia cabeçalhos corretos (força download)
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($pdfOutput));
        header('Content-Disposition: attachment; filename="'.preg_replace('/\s+/', '_', ($companyName ?: 'qrcode_cardapio')).'.pdf"');

        echo $pdfOutput;

        // Remove tmp
        @unlink($tmpFile);
        exit;
    }
}