<?php namespace App\Models; 

use PDO; 
use Exception; 

class Mesa { 
    private PDO $pdo; 

    public function __construct(PDO $pdo) { 
        $this->pdo = $pdo; 
    } 

    // ... (buscarTodasPorEmpresa, atualizarStatus, buscarPorId - sem alterações) ...
    public function buscarTodasPorEmpresa(int $empresa_id): array { 
        $sql = "SELECT id, numero, status FROM mesas WHERE empresa_id = ? ORDER BY numero ASC"; 
        $stmt = $this->pdo->prepare($sql); 
        $stmt->execute([$empresa_id]); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    } 
    public function atualizarStatus(int $id, string $novoStatus): bool
    {
        try {
            $novoStatus = strtolower($novoStatus);
            $sql = "UPDATE mesas SET status = :novoStatus WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':novoStatus', $novoStatus, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erro PDO ao atualizar status da mesa: " . $e->getMessage());
            throw new Exception("Erro de banco de dados ao tentar atualizar a mesa.");
        }
    } 
    public function buscarPorId(int $id) { 
        $sql = "SELECT * FROM mesas WHERE id = ?"; 
        $stmt = $this->pdo->prepare($sql); 
        $stmt->execute([$id]); 
        return $stmt->fetch(\PDO::FETCH_ASSOC); 
    } 
    // FIM dos métodos sem alteração


    public function criar(array $dados): bool
    {
        $sql = "INSERT INTO mesas (empresa_id, numero, status) 
                VALUES (:empresa_id, :numero, :status)";
        
        $stmt = $this->pdo->prepare($sql);
        
        $stmt->bindValue(':empresa_id', $dados['empresa_id'], PDO::PARAM_INT);
        $stmt->bindValue(':numero', $dados['numero'], PDO::PARAM_INT);
        $stmt->bindValue(':status', $dados['status'], PDO::PARAM_STR);
        
        // Se isto falhar, o Controller vai pegar o erro real.
        return $stmt->execute();
    }

    /**
     * Busca o maior número de mesa de uma empresa.
     */
    public function buscarUltimoNumero(int $empresa_id): int
    {
        $sql = "SELECT MAX(numero) as max_num FROM mesas WHERE empresa_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Se não houver mesas, retorna 0.
        return (int) ($resultado['max_num'] ?? 0);
    }
    
    /**
     * (CORRIGIDO) Exclui uma mesa pelo ID.
     * Também removemos o try-catch para deixar o Controller capturar o erro.
     */
    public function excluir(int $id): bool
    {
        $sql = "DELETE FROM mesas WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Atualiza o status de uma mesa para 'disponivel' após o pagamento.
     * CORRIGIDO: Removida a verificação de status anterior (IN ('ocupada', ...)).
     * Agora, força a mesa a ficar 'disponivel' desde que o ID e empresa_id batam.
     */
    public function liberarMesa(int $mesa_id, int $empresa_id): bool
    {
        // --- ALTERAÇÃO AQUI ---
        // Removida a cláusula 'AND status IN (...)'.
        // Se estamos chamando liberarMesa, ela DEVE ser liberada,
        // independentemente do status anterior.
        $sql = "UPDATE mesas 
                SET status = 'disponivel'
                WHERE id = :mesa_id 
                  AND empresa_id = :empresa_id";


        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
            // Retorna true se a mesa foi encontrada e atualizada
            return $stmt->rowCount() > 0; 
        } catch (\PDOException $e) {
            error_log("Erro no Model (PDO) ao liberar mesa: " . $e->getMessage());
            return false; // Retorna false em caso de erro
        }
    }

    // ... (buscarMesasOcupadasOuPagamento, buscarMesasComContaAberta - sem alterações) ...
    public function buscarMesasOcupadasOuPagamento(int $empresa_id): array
    {
        $sql = "SELECT id, numero, status FROM mesas 
                WHERE empresa_id = ? AND status IN ('ocupada', 'aguardando_pagamento') 
                ORDER BY numero ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function buscarMesasComContaAberta(int $empresa_id): array
    {
        $sql = "
            SELECT id, numero, status 
            FROM mesas 
            WHERE empresa_id = ? 
              AND status IN ('ocupada', 'aguardando_pagamento') 
            ORDER BY numero ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

