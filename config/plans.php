<?php
/**
 * Configurações de Planos e Limites (SaaS)
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:40
 */

return [
    // Modo SaaS ativado
    'saas_mode' => true,
    
    // Planos disponíveis
    'plans' => [
        'free' => [
            'name' => 'Gratuito',
            'slug' => 'free',
            'price' => 0,
            'currency' => 'BRL',
            'billing_period' => 'mensal',
            'trial_days' => 0,
            
            // Limites
            'limits' => [
                'agendamentos_ativos' => 10,
                'agendamentos_mes' => 10,
                'clientes' => 20,
                'disponibilidades' => 5,
                'upload_size_mb' => 2,
            ],
            
            // Recursos
            'features' => [
                'dashboard' => true,
                'calendario' => true,
                'agendamentos' => true,
                'notificacoes_email' => false,
                'relatorios' => false,
                'api_access' => false,
                'suporte' => 'comunidade',
            ],
            
            'description' => 'Ideal para testar o sistema',
            'cta' => 'Começar Grátis',
        ],
        
        'pro' => [
            'name' => 'Profissional',
            'slug' => 'pro',
            'price' => 10.00,
            'currency' => 'BRL',
            'billing_period' => 'mensal',
            'trial_days' => 7,
            
            // Limites
            'limits' => [
                'agendamentos_ativos' => -1, // -1 = ilimitado
                'agendamentos_mes' => -1,
                'clientes' => -1,
                'disponibilidades' => -1,
                'upload_size_mb' => 10,
            ],
            
            // Recursos
            'features' => [
                'dashboard' => true,
                'calendario' => true,
                'agendamentos' => true,
                'notificacoes_email' => true,
                'relatorios' => true,
                'api_access' => true,
                'suporte' => 'prioritario',
            ],
            
            'description' => 'Para professores que precisam de mais',
            'cta' => 'Assinar Agora',
            'popular' => true,
        ],
    ],
    
    // Plano padrão para novos usuários
    'default_plan' => 'free',
    
    // Mensagens de limite atingido
    'messages' => [
        'limit_reached' => 'Você atingiu o limite do plano {plan}. Faça upgrade para continuar.',
        'upgrade_required' => 'Este recurso está disponível apenas no plano Profissional.',
    ],
    
    // Gateway de pagamento (futuro)
    'payment' => [
        'gateway' => 'stripe', // stripe, pagseguro, mercadopago
        'test_mode' => true,
    ],
];
