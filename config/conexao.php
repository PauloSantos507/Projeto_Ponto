<?php
// Habilita o registro de erros em um arquivo específico
ini_set('log_errors', 1);

// Define o caminho do arquivo de log (na raiz do seu projeto)
ini_set('error_log', __DIR__ . '/../erro.log');

// Opcional: Desabilita a exibição de erros na tela (bom para produção)
ini_set('display_errors', 0);
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