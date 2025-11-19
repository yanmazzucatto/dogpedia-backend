<?php
// router.php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Se o arquivo existir (ex: imagens, css), entrega ele direto
if (file_exists(__DIR__ . $path) && $path !== '/') {
    return false;
}

// Caso contrário, manda tudo para o index.php processar a API
require 'index.php';