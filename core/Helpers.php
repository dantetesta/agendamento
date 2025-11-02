<?php
/**
 * Funções Helper - Utilitários Globais
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 * 
 * Funções auxiliares para uso em toda aplicação
 */

/**
 * Sanitiza string para prevenir XSS
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida e-mail
 */
function validEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gera token aleatório seguro
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Formata data para exibição
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === null) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime(trim($date));
    return $timestamp ? date($format, $timestamp) : '';
}

/**
 * Formata data e hora para exibição
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Retorna URL base da aplicação
 */
function baseUrl($path = '') {
    $config = require __DIR__ . '/../config/app.php';
    $url = rtrim($config['url'], '/');
    
    if (!empty($path)) {
        $path = ltrim($path, '/');
        return $url . '/' . $path;
    }
    
    return $url;
}

/**
 * Retorna URL de asset
 */
function asset($path) {
    return baseUrl('public/assets/' . ltrim($path, '/'));
}

/**
 * Redireciona para URL (amigável ou completa)
 * Uso: redirect('/dashboard') ou redirect('https://site.com')
 */
function redirect($path, $code = 302) {
    // Se já é URL completa, usa direto
    if (strpos($path, 'http') === 0) {
        header('Location: ' . $path, true, $code);
    } else {
        // Senão, gera URL amigável
        header('Location: ' . url($path), true, $code);
    }
    exit;
}

/**
 * Retorna mensagem flash da sessão
 */
function flash($key = null) {
    Auth::startSession();
    
    if ($key === null) {
        return $_SESSION['flash'] ?? [];
    }
    
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    
    return null;
}

/**
 * Define mensagem flash
 */
function setFlash($key, $message) {
    Auth::startSession();
    $_SESSION['flash'][$key] = $message;
}

/**
 * Verifica se existe mensagem flash
 */
function hasFlash($key) {
    Auth::startSession();
    return isset($_SESSION['flash'][$key]);
}

/**
 * Retorna e remove mensagem flash
 */
function getFlash($key) {
    Auth::startSession();
    
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    
    return null;
}

/**
 * Retorna valor antigo de input (após erro de validação)
 */
function old($key, $default = '') {
    Auth::startSession();
    
    if (isset($_SESSION['old'][$key])) {
        $value = $_SESSION['old'][$key];
        unset($_SESSION['old'][$key]);
        return $value;
    }
    
    return $default;
}

/**
 * Armazena valores antigos de input
 */
function setOld($data) {
    Auth::startSession();
    $_SESSION['old'] = $data;
}

/**
 * Valida senha (mínimo 6 caracteres)
 */
function validPassword($password) {
    $config = require __DIR__ . '/../config/app.php';
    return strlen($password) >= $config['security']['password_min_length'];
}

/**
 * Gera hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifica senha contra hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Retorna extensão de arquivo
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Gera nome único para arquivo
 */
function generateUniqueFilename($originalName) {
    $extension = getFileExtension($originalName);
    return uniqid('upload_', true) . '.' . $extension;
}

/**
 * Valida upload de imagem
 */
function validImageUpload($file) {
    $config = require __DIR__ . '/../config/app.php';
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Nenhum arquivo foi enviado.'];
    }
    
    if ($file['size'] > $config['upload']['max_size']) {
        return ['success' => false, 'error' => 'Arquivo muito grande. Máximo: 2MB.'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $config['upload']['allowed_types'])) {
        return ['success' => false, 'error' => 'Tipo de arquivo não permitido. Use apenas imagens.'];
    }
    
    return ['success' => true];
}

/**
 * Retorna JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Debug helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Converte dia da semana para número
 */
function diaSemanaToNumber($dia) {
    $dias = [
        'domingo' => 0,
        'segunda' => 1,
        'terca' => 2,
        'quarta' => 3,
        'quinta' => 4,
        'sexta' => 5,
        'sabado' => 6
    ];
    
    return $dias[strtolower($dia)] ?? null;
}

/**
 * Converte número para dia da semana
 */
function numberToDiaSemana($number) {
    $dias = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado'
    ];
    
    return $dias[$number] ?? '';
}

/**
 * Gera URL amigável
 * Uso: url('/dashboard') retorna 'https://seusite.com/dashboard'
 */
function url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = ltrim($path, '/');
    
    return $protocol . '://' . $host . '/' . $path;
}

/**
 * Gera URL de upload
 * Uso: upload('users/foto.jpg') retorna '/public/uploads/users/foto.jpg'
 */
function upload($path) {
    return '/public/uploads/' . ltrim($path, '/');
}