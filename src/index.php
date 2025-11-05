<?php
require_once 'SupabaseClient.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite CORS para desenvolvimento
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apikey');

// Responde a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', trim($uri, '/'));

// O primeiro segmento após o diretório base será o nome da tabela
// Exemplo: /dogpedia_backend/src/breeds -> 'breeds'
// Ajuste o índice se o seu servidor web tiver um caminho base diferente.
$table_name = $uri_segments[count($uri_segments) - 1];

// Lista de tabelas suportadas
$supported_tables = ['breeds', 'categories', 'posts', 'comments'];

if (!in_array($table_name, $supported_tables)) {
    http_response_code(404);
    echo json_encode(['error' => 'Tabela não encontrada ou não suportada.']);
    exit();
}

$client = new SupabaseClient();
$response = [];

try {
    switch ($method) {
        case 'GET':
            // Filtro pode vir como query string (ex: ?id=eq.1)
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            $response = $client->get($table_name, $filter);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                throw new Exception('Dados de entrada inválidos ou vazios.', 400);
            }
            $response = $client->post($table_name, $data);
            break;

        case 'PUT':
        case 'PATCH':
            // O filtro (ex: id=eq.1) deve ser fornecido na query string
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            if (empty($filter)) {
                throw new Exception('Filtro de atualização (query string) é obrigatório.', 400);
            }
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                throw new Exception('Dados de entrada inválidos ou vazios.', 400);
            }
            $response = $client->put($table_name, $data, $filter);
            break;

        case 'DELETE':
            // O filtro (ex: id=eq.1) deve ser fornecido na query string
            $filter = $_SERVER['QUERY_STRING'] ?? '';
            if (empty($filter)) {
                throw new Exception('Filtro de exclusão (query string) é obrigatório.', 400);
            }
            $response = $client->delete($table_name, $filter);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido.']);
            exit();
    }

    // Define o código de status HTTP com base na resposta do cliente Supabase
    http_response_code($response['code'] ?? 200);

    if (isset($response['error'])) {
        echo json_encode(['error' => $response['error']]);
    } else {
        echo json_encode($response['data'] ?? []);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}
