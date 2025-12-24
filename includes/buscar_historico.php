<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

//inicialização da variável que vai guardar os dados da busca

$busca_registros = [];
if(isset($_SESSION['ultimo_email'])) {
    $sql = "SELECT hora_registro, tipo_batida FROM registros_ponto 
            WHERE id_usuario = (SELECT id FROM usuarios WHERE email = :email)
            AND data_registro = CURDATE() ORDER BY hora_registro ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $_SESSION['ultimo_email']]);
    $busca_registros = $stmt->fetchAll();
}







