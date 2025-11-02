<?php
/**
 * Configurações da Aplicação - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

return [
    'name' => 'Agenda Master',
    'version' => '1.0.0',
    'url' => 'https://danteflix.com.br',
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    
    // Configurações de sessão
    'session' => [
        'name' => 'agenda_professor_session',
        'lifetime' => 7200, // 2 horas
        'path' => '/',
        'secure' => false, // true para HTTPS
        'httponly' => true
    ],
    
    // Configurações de upload
    'upload' => [
        'max_size' => 2097152, // 2MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'path' => __DIR__ . '/../public/uploads/users/'
    ],
    
    // Configurações de segurança
    'security' => [
        'password_min_length' => 6,
        'token_expiry' => 3600 // 1 hora para tokens de reset
    ]
];
