<?php
/**
 * Definição de Rotas do Sistema
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:40
 */

require_once __DIR__ . '/core/Router.php';

// ============================================
// ROTAS PÚBLICAS (sem autenticação)
// ============================================

// Landing Page (SaaS)
Router::get('/', 'public/landing.php');
Router::get('/home', 'public/landing.php');

// Páginas Legais
Router::get('/termos', 'public/termos.php');
Router::get('/termos-de-uso', 'public/termos.php');
Router::get('/privacidade', 'public/privacidade.php');
Router::get('/politica-de-privacidade', 'public/privacidade.php');

// Planos e Preços
Router::get('/planos', 'public/planos.php');
Router::get('/pricing', 'public/planos.php');

// Autenticação
Router::any('/login', 'public/login.php');
Router::any('/login-debug', 'public/login_debug.php');
Router::any('/login-simples', 'public/login_simples.php');
Router::any('/test-login', 'public/test_login.php');
Router::any('/registro', 'public/registro.php');
Router::any('/cadastro', 'public/registro.php');
Router::any('/esqueci-senha', 'public/reset_senha.php');
Router::any('/reset-senha', 'public/reset_senha.php');
Router::any('/reset-password', 'public/reset_senha.php');

// Instalador
Router::any('/install', 'public/install.php');
Router::any('/instalar', 'public/install.php');

// ============================================
// ROTAS AUTENTICADAS (requerem login)
// ============================================

// Dashboard
Router::get('/dashboard', 'public/dashboard.php');
Router::get('/painel', 'public/dashboard.php');

// Agendamentos
Router::any('/agendamentos', 'public/agendamentos.php');
Router::get('/agendamentos/novo', 'public/agendamentos.php');
Router::get('/agendamentos/editar/:id', 'public/agendamentos.php');

// Agenda (Disponibilidade)
Router::any('/agenda', 'public/agenda.php');
Router::any('/disponibilidade', 'public/agenda.php');
Router::any('/minha-agenda', 'public/agenda.php');

// Perfil
Router::any('/perfil', 'public/perfil.php');
Router::any('/meu-perfil', 'public/perfil.php');
Router::any('/profile', 'public/perfil.php');
Router::post('/deletar_conta', 'public/deletar_conta.php');

// Clientes
Router::get('/clientes', 'public/clientes.php');
Router::post('/clientes', 'public/clientes.php');
Router::any('/clientes/novo', 'public/cliente_form.php');
Router::any('/clientes/editar/:id', 'public/cliente_form.php');
Router::get('/clientes/ver/:id', 'public/cliente_detalhes.php');

// Tags
Router::any('/tags', 'public/tags.php');

// Configurações
Router::any('/configuracoes', 'public/perfil.php');
Router::any('/settings', 'public/perfil.php');

// Admin
Router::any('/admin/desbloquear', 'public/admin_desbloquear.php');

// Plano e Upgrade
Router::get('/meu-plano', 'public/plano.php');
Router::get('/upgrade', 'public/upgrade.php');
Router::post('/upgrade/checkout', 'public/checkout.php');

// Logout
Router::any('/logout', 'public/logout.php');
Router::any('/sair', 'public/logout.php');

// ============================================
// API (JSON)
// ============================================
// API (JSON)
Router::get('/api/eventos', 'public/api/eventos.php');
Router::post('/api/agendamento', 'public/api/agendamento.php');
Router::get('/api/clientes/buscar', 'public/api/clientes_buscar.php');


// ============================================
// PROCESSA ROTA
// ============================================

Router::dispatch();
