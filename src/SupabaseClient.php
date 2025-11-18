<?php
// SupabaseClient.php

require_once 'config.php';

class SupabaseClient {
    private $baseUrl;
    // Removendo $headers do estado do objeto para evitar vazamentos/conflitos entre requisições
    private $defaultHeaders; 

    public function __construct() {
        $this->baseUrl = SUPABASE_URL . '/rest/v1/';
        $this->defaultHeaders = SUPABASE_HEADERS;
    }

    /**
     * Executa uma requisição HTTP para a API do Supabase.
     */
    private function request($method, $table, $data = [], $query = '', $extraHeaders = []) {
        $url = $this->baseUrl . $table . $query;
        $ch = curl_init($url);

        // Combina headers padrões com headers extra (como Prefer)
        $headers = array_merge($this->defaultHeaders, $extraHeaders); 
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Timeout para evitar que a requisição trave indefinidamente
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 

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
            // Garante que a mensagem de erro do Supabase seja propagada
            $message = $decoded_response['message'] ?? $decoded_response['msg'] ?? 'Unknown Supabase API error'; 
            return ['error' => 'API Error: ' . $message, 'code' => $http_code];
        }

        return ['data' => $decoded_response, 'code' => $http_code];
    }

    // --- Métodos CRUD ---

    public function get($table, $filter = '') {
        $query = '?select=*';
        if (!empty($filter)) {
            // Adiciona a cláusula WHERE para filtros mais complexos, se necessário.
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
        // Supabase usa PATCH para atualizações
        return $this->request('PATCH', $table, $data, '?' . $filter, $extraHeaders); 
    }

    public function delete($table, $filter) {
        if (empty($filter)) {
            return ['error' => 'Filter is required for DELETE operation.', 'code' => 400];
        }
        return $this->request('DELETE', $table, [], '?' . $filter);
    }
}