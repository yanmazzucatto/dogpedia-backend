<?php
// test_auth.php - Arquivo temporário para testar autenticação

require_once 'SupabaseClient.php';

header('Content-Type: application/json');

// Teste 1: Registro
echo "=== TESTE DE REGISTRO ===\n";
$client = new SupabaseClient();
$registerData = [
    'email' => 'teste@exemplo.com',
    'password' => 'senha123456',
    'name' => 'Usuario Teste'
];

$registerResult = $client->auth('register', $registerData);
echo json_encode($registerResult, JSON_PRETTY_PRINT) . "\n\n";

// Teste 2: Login
echo "=== TESTE DE LOGIN ===\n";
$loginData = [
    'email' => 'teste@exemplo.com',
    'password' => 'senha123456'
];

$loginResult = $client->auth('login', $loginData);
echo json_encode($loginResult, JSON_PRETTY_PRINT) . "\n\n";

// Teste 3: Verificar estrutura de resposta
if (isset($loginResult['data']['user']['id'])) {
    echo "✓ user.id encontrado: " . $loginResult['data']['user']['id'] . "\n";
} else {
    echo "✗ user.id NÃO encontrado na resposta\n";
    echo "Estrutura recebida: " . json_encode($loginResult, JSON_PRETTY_PRINT) . "\n";
}