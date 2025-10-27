<?php
// classes/Notificacao.php - Classe para gerenciar notificações

class Notificacao {
    private $conn;
    private $table_name = "notificacoes";

    public $id;
    public $usuario_id;
    public $tipo;
    public $titulo;
    public $mensagem;
    public $reserva_id;
    public $lida;
    public $data_criacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar notificação
    public function criar() {
        // Validar dados obrigatórios
        if (empty($this->usuario_id) || empty($this->tipo) || empty($this->titulo) || empty($this->mensagem)) {
            error_log("Notificacao::criar() - Dados obrigatórios não fornecidos");
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET usuario_id=:usuario_id, tipo=:tipo, titulo=:titulo, 
                      mensagem=:mensagem, reserva_id=:reserva_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":usuario_id", $this->usuario_id);
            $stmt->bindParam(":tipo", $this->tipo);
            $stmt->bindParam(":titulo", $this->titulo);
            $stmt->bindParam(":mensagem", $this->mensagem);
            $stmt->bindParam(":reserva_id", $this->reserva_id);
            
            if($stmt->execute()) {
                error_log("Notificacao::criar() - Notificação criada com sucesso");
                return true;
            } else {
                error_log("Notificacao::criar() - Erro ao executar query: " . print_r($stmt->errorInfo(), true));
                return false;
            }
        } catch(PDOException $e) {
            error_log("Notificacao::criar() - Erro PDO: " . $e->getMessage());
            return false;
        }
    }

    // Listar notificações por usuário
    public function listarPorUsuario($usuario_id, $limit = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id
                  ORDER BY data_criacao DESC";
        
        if($limit) {
            $query .= " LIMIT " . $limit;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Marcar notificação como lida
    public function marcarComoLida($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET lida = 1 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    // Contar notificações não lidas
    public function contarNaoLidas($usuario_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id AND lida = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Notificar nova solicitação para admins
    public function notificarNovaSolicitacao($reserva_id, $usuario_nome) {
        try {
            $query = "SELECT id FROM usuarios WHERE tipo = 'admin' AND ativo = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($admins)) {
                error_log("Notificacao::notificarNovaSolicitacao() - Nenhum admin encontrado");
                return false;
            }
            
            $notificacoes_criadas = 0;
            foreach($admins as $admin) {
                $this->usuario_id = $admin['id'];
                $this->tipo = 'nova_solicitacao';
                $this->titulo = 'Nova Solicitação de Reserva';
                $this->mensagem = "O usuário {$usuario_nome} solicitou uma nova reserva de auditório.";
                $this->reserva_id = $reserva_id;
                
                if ($this->criar()) {
                    $notificacoes_criadas++;
                }
            }
            
            error_log("Notificacao::notificarNovaSolicitacao() - {$notificacoes_criadas} notificações criadas para " . count($admins) . " admins");
            return $notificacoes_criadas > 0;
            
        } catch(Exception $e) {
            error_log("Notificacao::notificarNovaSolicitacao() - Erro: " . $e->getMessage());
            return false;
        }
    }

    // Notificar usuário sobre aprovação/recusa
    public function notificarUsuario($usuario_id, $tipo, $titulo, $mensagem, $reserva_id) {
        $this->usuario_id = $usuario_id;
        $this->tipo = $tipo;
        $this->titulo = $titulo;
        $this->mensagem = $mensagem;
        $this->reserva_id = $reserva_id;
        
        return $this->criar();
    }
}
?>
