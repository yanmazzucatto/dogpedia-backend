<?php
require_once 'config.php';

class SupabaseClient {
    private $baseUrl;
    private $headers;

    public function __construct() {
        $this->baseUrl = SUPABASE_URL . '/rest/v1/';
        $this->headers = SUPABASE_HEADERS;
    }

    /**
     * Executa uma requisição HTTP para a API do Supabase.
     *
     * @param string $method O método HTTP (GET, POST, PUT, DELETE).
     * @param string $table O nome da tabela.
     * @param array $data Os dados a serem enviados (para POST/PUT).
     * @param string $query A string de query para filtros (ex: '?id=eq.1').
     * @return array O resultado da requisição.
     */
    private function request($method, $table, $data = [], $query = '') {
        $url = $this->baseUrl . $table . $query;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

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
            return ['error' => 'API Error: ' . ($decoded_response['message'] ?? 'Unknown error'), 'code' => $http_code];
        }

        return ['data' => $decoded_response, 'code' => $http_code];
    }

    // --- Métodos CRUD ---

    /**
     * READ: Busca todos os registros ou um registro específico.
     *
     * @param string $table O nome da tabela.
     * @param string $filter A string de filtro do PostgREST (ex: 'id=eq.1').
     * @return array
     */
    public function get($table, $filter = '') {
        $query = '?select=*';
        if (!empty($filter)) {
            $query .= '&' . $filter;
        }
        return $this->request('GET', $table, [], $query);
    }

    /**
     * CREATE: Insere um novo registro.
     *
     * @param string $table O nome da tabela.
     * @param array $data Os dados a serem inseridos.
     * @return array
     */
    public function post($table, $data) {
        // Adiciona preferência para retornar o registro criado
        $headers = $this->headers;
        $headers[] = 'Prefer: return=representation';
        $this->headers = $headers;
        $result = $this->request('POST', $table, $data);
        // Restaura os headers
        array_pop($this->headers);
        return $result;
    }

    /**
     * UPDATE: Atualiza um registro existente.
     *
     * @param string $table O nome da tabela.
     * @param array $data Os dados a serem atualizados.
     * @param string $filter O filtro para identificar o registro (ex: 'id=eq.1').
     * @return array
     */
    public function put($table, $data, $filter) {
        if (empty($filter)) {
            return ['error' => 'Filter is required for PUT operation.', 'code' => 400];
        }
        // Adiciona preferência para retornar o registro atualizado
        $headers = $this->headers;
        $headers[] = 'Prefer: return=representation';
        $this->headers = $headers;
        $result = $this->request('PATCH', $table, $data, '?' . $filter); // Supabase usa PATCH para UPDATE
        // Restaura os headers
        array_pop($this->headers);
        return $result;
    }

    /**
     * DELETE: Remove um registro.
     *
     * @param string $table O nome da tabela.
     * @param string $filter O filtro para identificar o registro (ex: 'id=eq.1').
     * @return array
     */
    public function delete($table, $filter) {
        if (empty($filter)) {
            return ['error' => 'Filter is required for DELETE operation.', 'code' => 400];
        }
        return $this->request('DELETE', $table, [], '?' . $filter);
    }
}
