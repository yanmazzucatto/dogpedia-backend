<?php
require_once 'config.php';
class SupabaseClient {
    private $baseUrl;
    private $defaultHeaders; 

    public function __construct() {
        $this->baseUrl = SUPABASE_URL . '/rest/v1/';
        $this->defaultHeaders = SUPABASE_HEADERS;
    }

    public function auth($action, $data) {
        // MUDA A BASE URL: De '/rest/v1/' para '/auth/v1/'
        // Isso é crucial porque a API de autenticação fica em um endpoint diferente
        $authUrl = str_replace('/rest/v1/', '/auth/v1/', $this->baseUrl);
        
        $endpoint = '';
        $payload = [];
        
        if ($action === 'register') {
            $endpoint = 'signup';
            $payload = [
                'email' => $data['email'],
                'password' => $data['password'],
                // Aqui enviamos os metadados. O Trigger do Passo 1 vai ler 
                // exatamente este campo 'username' dentro de 'data'
                'data' => [
                    'username' => $data['name'] ?? explode('@', $data['email'])[0]
                ]
            ];
        } elseif ($action === 'login') {
            $endpoint = 'token?grant_type=password';
            $payload = [
                'email' => $data['email'],
                'password' => $data['password']
            ];
        }

        // Inicializa o cURL para a URL de autenticação correta
        $ch = curl_init($authUrl . $endpoint);
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->defaultHeaders);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return ['error' => 'Erro interno (cURL): ' . $curl_error, 'code' => 500];
        }

        $decoded = json_decode($response, true);

        // --- MUDANÇA PRINCIPAL AQUI ---
        // Se der erro (código >= 400), retornamos o erro exato do Supabase.
        if ($http_code >= 400) {
            // O Supabase às vezes manda 'msg', às vezes 'error_description', às vezes 'message'
            $errorMsg = $decoded['msg'] ?? $decoded['error_description'] ?? $decoded['message'] ?? 'Erro desconhecido na autenticação';
            return [
                'error' => $errorMsg,
                'code' => $http_code
            ];
        }

        // Se for sucesso, retornamos a resposta crua (raw) do Supabase.
        // O Supabase retorna { access_token, user, refresh_token, ... }
        // Antes, seu código estava criando um array novo e esquecendo o token.
        return [
            'data' => $decoded,
            'code' => 200
        ];
    }

    // --- MÉTODOS DE BANCO DE DADOS (REST) ---
    // Estes métodos continuam iguais ao original, servindo para GET, POST, etc.
    // Mantive aqui para o arquivo ficar completo.

    private function request($method, $table, $data = [], $query = '', $extraHeaders = []) {
        $url = $this->baseUrl . $table . $query;
        $ch = curl_init($url);

        $headers = array_merge($this->defaultHeaders, $extraHeaders); 
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if (!empty($data)) {
            $json_data = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return ['error' => 'cURL Error: ' . $error, 'code' => 500];
        }

        $decoded_response = json_decode($response, true);

        if ($http_code >= 400) {
            $message = $decoded_response['message'] ?? $decoded_response['msg'] ?? 'Unknown Supabase API error'; 
            return ['error' => 'API Error: ' . $message, 'code' => $http_code];
        }

        return ['data' => $decoded_response, 'code' => $http_code];
    }

    public function get($table, $filter = '') {
        $query = '?select=*';
        if (!empty($filter)) {
            $query .= '&' . $filter;
        }
        return $this->request('GET', $table, [], $query);
    }

    public function post($table, $data) {
        $extraHeaders = ['Prefer: return=representation'];
        return $this->request('POST', $table, $data, '', $extraHeaders);
    }

    public function put($table, $data, $filter) {
        if (empty($filter)) {
            return ['error' => 'Filter is required for PUT operation.', 'code' => 400];
        }
        $extraHeaders = ['Prefer: return=representation'];
        return $this->request('PATCH', $table, $data, '?' . $filter, $extraHeaders); 
    }

    public function delete($table, $filter) {
        if (empty($filter)) {
            return ['error' => 'Filter is required for DELETE operation.', 'code' => 400];
        }
        $extraHeaders = ['Prefer: return=representation'];
        return $this->request('DELETE', $table, [], '?' . $filter, $extraHeaders);
    }
}