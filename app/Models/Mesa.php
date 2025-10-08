<?php namespace App\Models; 

use PDO; 
use Exception; 

class Mesa { 
    private PDO $pdo; 

    public function __construct(PDO $pdo) { 
        $this->pdo = $pdo; 
    } 

    /** 
     * Busca todas as mesas de uma empresa específica, ordenadas pelo número.
     * 
     * @param int $empresa_id O ID da empresa.
     * @return array A lista de mesas ou um array vazio se não houver.
     */ 
    public function buscarTodasPorEmpresa(int $empresa_id): array { 
        $sql = "SELECT id, numero, status FROM mesas WHERE empresa_id = ? ORDER BY numero ASC"; 
        $stmt = $this->pdo->prepare($sql); 
        $stmt->execute([$empresa_id]); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    } 

    /** 
     * Atualiza o status de uma mesa específica no banco de dados.
     * 
     * @param int $id O ID da mesa a ser atualizada.
     * @param string $novoStatus O novo status para a mesa (ex: 'Livre', 'Ocupada').
     * @return bool Retorna true em caso de sucesso.
     * @throws Exception Lança exceção se falhar.
     */ 
    public function atualizarStatus(int $id, string $novoStatus): bool { 
        try { 
            $novoStatus = strtolower($novoStatus); 
            $sql = "UPDATE mesas SET status = :novoStatus WHERE id = :id"; 
            $stmt = $this->pdo->prepare($sql); 
            $stmt->bindValue(':novoStatus', $novoStatus, PDO::PARAM_STR); 
            $stmt->bindValue(':id', $id, PDO::PARAM_INT); 
            $executou = $stmt->execute(); 

            // Verifica se alguma linha foi alterada 
            if ($stmt->rowCount() === 0) { 
                throw new Exception("Nenhuma mesa foi atualizada. Verifique se o ID existe."); 
            } 
            return $executou; 
        } catch (\PDOException $e) { 
            throw new Exception("Erro PDO ao atualizar status da mesa: " . $e->getMessage()); 
        } 
    } 

    public function buscarPorId(int $id) { 
        $sql = "SELECT * FROM mesas WHERE id = ?"; 
        $stmt = $this->pdo->prepare($sql); 
        $stmt->execute([$id]); 
        return $stmt->fetch(\PDO::FETCH_ASSOC); 
    } 


    public function liberarMesa(int $mesa_id): bool
    {
        $sql = "UPDATE mesas SET status_mesa = 'disponivel' WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$mesa_id]);
    }
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
