<?php
/**
 * Logout - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::logout();

setFlash('success', 'Você saiu com sucesso. Até logo!');
redirect('/');
