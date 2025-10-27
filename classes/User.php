<?php
// classes/User.php - Classe para gerenciar usuários

class User {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nome;
    public $email;
    public $senha;
    public $tipo;
    public $data_cadastro;
    public $ativo;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Cadastrar novo usuário
    public function cadastrar() {
        try {
            error_log("User::cadastrar() - Iniciando cadastro");
            error_log("User::cadastrar() - Nome: {$this->nome}, Email: {$this->email}, Tipo: {$this->tipo}");
            
            $query = "INSERT INTO " . $this->table_name . " 
                      SET nome=:nome, email=:email, senha=:senha, tipo=:tipo";
            
            $stmt = $this->conn->prepare($query);
            
            $senha_hash = password_hash($this->senha, PASSWORD_DEFAULT);
            error_log("User::cadastrar() - Senha criptografada");
            
            $stmt->bindParam(":nome", $this->nome);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":senha", $senha_hash);
            $stmt->bindParam(":tipo", $this->tipo);
            
            if($stmt->execute()) {
                error_log("User::cadastrar() - Usuário cadastrado com sucesso");
                return true;
            }
            
            error_log("User::cadastrar() - Falha ao executar query: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch(PDOException $e) {
            error_log("User::cadastrar() - Erro PDO: " . $e->getMessage());
            throw $e;
        }
    }

    // Verificar se email já existe
    public function emailExiste() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Login do usuário
    public function login($email, $senha) {
        $query = "SELECT id, nome, email, senha, tipo, ativo 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND ativo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($senha, $row['senha'])) {
                $this->id = $row['id'];
                $this->nome = $row['nome'];
                $this->email = $row['email'];
                $this->tipo = $row['tipo'];
                return true;
            }
        }
        return false;
    }

    // Buscar usuário por ID
    public function buscarPorId($id) {
        $query = "SELECT id, nome, email, tipo, data_cadastro 
                  FROM " . $this->table_name . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->email = $row['email'];
            $this->tipo = $row['tipo'];
            $this->data_cadastro = $row['data_cadastro'];
            return true;
        }
        return false;
    }

    // Listar todos os usuários (para admin)
    public function listarUsuarios() {
        $query = "SELECT id, nome, email, tipo, data_cadastro, ativo 
                  FROM " . $this->table_name . " 
                  ORDER BY nome ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}
?>
