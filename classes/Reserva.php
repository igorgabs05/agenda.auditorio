<?php
// classes/Reserva.php - Classe para gerenciar reservas

class Reserva {
    private $conn;
    private $table_name = "reservas";

    public $id;
    public $usuario_id;
    public $data_reserva;
    public $hora_inicio;
    public $hora_fim;
    public $motivo;
    public $status;
    public $observacoes;
    public $data_solicitacao;
    public $data_aprovacao;
    public $aprovado_por;

    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Verificar se o dia está bloqueado
    public function verificarDiaBloqueado($data) {
        $query = "SELECT bloqueado FROM dias_bloqueados WHERE data = :data";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':data', $data);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return (bool)$row['bloqueado'];
            }
            
            // Se não tem customização, verificar comportamento padrão
            $timestamp = strtotime($data);
            $diaSemana = date('w', $timestamp);
            
            // Domingo = 0
            if($diaSemana === 0) {
                return true; // Domingos bloqueados por padrão
            }
            
            // Verificar feriados fixos
            $dia = date('j', $timestamp);
            $mes = date('n', $timestamp);
            
            $feriadosFixos = [
                [1, 1],   // Ano Novo
                [4, 21],  // Tiradentes
                [5, 1],   // Dia do Trabalho
                [9, 7],   // Independência
                [10, 12], // Nossa Senhora Aparecida
                [11, 2],  // Finados
                [11, 15], // Proclamação da República
                [12, 25]  // Natal
            ];
            
            foreach($feriadosFixos as $feriado) {
                if($mes == $feriado[0] && $dia == $feriado[1]) {
                    return true; // Feriado bloqueado por padrão
                }
            }
            
            return false; // Dia disponível
            
        } catch(PDOException $e) {
            error_log("Reserva::verificarDiaBloqueado() - Erro: " . $e->getMessage());
            // Em caso de erro, assumir bloqueado para segurança
            return true;
        }
    }

    // Criar nova reserva
    public function criar() {
        // Validar dados obrigatórios
        if (empty($this->usuario_id) || empty($this->data_reserva) || 
            empty($this->hora_inicio) || empty($this->hora_fim) || 
            empty($this->motivo) || empty($this->status)) {
            error_log("Reserva::criar() - Dados obrigatórios não fornecidos");
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET usuario_id=:usuario_id, data_reserva=:data_reserva, 
                      hora_inicio=:hora_inicio, hora_fim=:hora_fim, 
                      motivo=:motivo, status=:status, observacoes=:observacoes";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":usuario_id", $this->usuario_id);
            $stmt->bindParam(":data_reserva", $this->data_reserva);
            $stmt->bindParam(":hora_inicio", $this->hora_inicio);
            $stmt->bindParam(":hora_fim", $this->hora_fim);
            $stmt->bindParam(":motivo", $this->motivo);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":observacoes", $this->observacoes);
            
            if($stmt->execute()) {
                $reserva_id = $this->conn->lastInsertId();
                error_log("Reserva::criar() - Reserva criada com sucesso. ID: " . $reserva_id);
                return $reserva_id;
            } else {
                error_log("Reserva::criar() - Erro ao executar query: " . print_r($stmt->errorInfo(), true));
                return false;
            }
        } catch(PDOException $e) {
            error_log("Reserva::criar() - Erro PDO: " . $e->getMessage());
            return false;
        }
    }

    // Verificar conflitos de horário
    public function verificarConflito($data, $hora_inicio, $hora_fim, $excluir_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE data_reserva = :data 
                  AND status = 'APROVADO'
                  AND ((hora_inicio < :hora_fim AND hora_fim > :hora_inicio))";
        
        if($excluir_id) {
            $query .= " AND id != :excluir_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':hora_inicio', $hora_inicio);
        $stmt->bindParam(':hora_fim', $hora_fim);
        
        if($excluir_id) {
            $stmt->bindParam(':excluir_id', $excluir_id);
        }
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Listar reservas por usuário
    public function listarPorUsuario($usuario_id) {
        $query = "SELECT r.*, u.nome as usuario_nome 
                  FROM " . $this->table_name . " r
                  LEFT JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.usuario_id = :usuario_id
                  ORDER BY r.data_reserva DESC, r.hora_inicio DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Listar todas as reservas (para admin)
    public function listarTodas() {
        $query = "SELECT r.*, u.nome as usuario_nome 
                  FROM " . $this->table_name . " r
                  LEFT JOIN usuarios u ON r.usuario_id = u.id
                  ORDER BY r.data_reserva DESC, r.hora_inicio DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Listar reservas pendentes
    public function listarPendentes() {
        $query = "SELECT r.*, u.nome as usuario_nome 
                  FROM " . $this->table_name . " r
                  LEFT JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.status = 'PENDENTE'
                  ORDER BY r.data_solicitacao ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Buscar reserva por ID
    public function buscarPorId($id) {
        $query = "SELECT r.*, u.nome as usuario_nome 
                  FROM " . $this->table_name . " r
                  LEFT JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->usuario_id = $row['usuario_id'];
            $this->data_reserva = $row['data_reserva'];
            $this->hora_inicio = $row['hora_inicio'];
            $this->hora_fim = $row['hora_fim'];
            $this->motivo = $row['motivo'];
            $this->status = $row['status'];
            $this->observacoes = $row['observacoes'];
            $this->data_solicitacao = $row['data_solicitacao'];
            $this->data_aprovacao = $row['data_aprovacao'];
            $this->aprovado_por = $row['aprovado_por'];
            return true;
        }
        return false;
    }

    // Atualizar status da reserva
    public function atualizarStatus($id, $status, $aprovado_por = null, $observacoes = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, data_aprovacao = NOW()";
        
        if($aprovado_por) {
            $query .= ", aprovado_por = :aprovado_por";
        }
        
        if($observacoes) {
            $query .= ", observacoes = :observacoes";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if($aprovado_por) {
            $stmt->bindParam(':aprovado_por', $aprovado_por);
        }
        
        if($observacoes) {
            $stmt->bindParam(':observacoes', $observacoes);
        }
        
        return $stmt->execute();
    }

    // Buscar reservas para calendário
    public function buscarParaCalendario($data_inicio = null, $data_fim = null) {
        $query = "SELECT r.*, u.nome as usuario_nome 
                  FROM " . $this->table_name . " r
                  LEFT JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.status IN ('APROVADO', 'PENDENTE')";
        
        if($data_inicio && $data_fim) {
            $query .= " AND r.data_reserva BETWEEN :data_inicio AND :data_fim";
        }
        
        $query .= " ORDER BY r.data_reserva, r.hora_inicio";
        
        $stmt = $this->conn->prepare($query);
        
        if($data_inicio && $data_fim) {
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim', $data_fim);
        }
        
        $stmt->execute();
        
        return $stmt;
    }
}
?>
