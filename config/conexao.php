<?php
// Habilita o registro de erros
ini_set('log_errors', 1);

// Define o caminho do arquivo de log na raiz do projeto
// __DIR__ garante que o caminho seja absoluto e correto
ini_set('error_log', __DIR__ . '/../erro_sistema.log');

// Desativa a exibição de erros diretamente na tela para o usuário (Segurança)
ini_set('display_errors', 0);
error_reporting(E_ALL);
// variaveis do ambiente

    $host = 'localhost';
    $db = 'sistema_ponto';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    // Data source name - configuracao para a conexao

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE   => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES     => false,
    ];

    try {
        $pdo = new PDO($dsn,$user, $pass, $options);
    }
    catch (\PDOException $e) {
        throw new \PDOException($e ->getMessage(), (int)$e -> getCode());
    }
?>