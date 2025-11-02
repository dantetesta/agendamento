<?php
/**
 * Configurações de Tema e Identidade Visual
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:40
 */

return [
    // Nome do Sistema
    'app_name' => 'Agenda Master',
    'app_name_short' => 'Agenda Master',
    
    // Cores do Tema (Tailwind CSS)
    'colors' => [
        // Cor primária (menu, botões principais)
        'primary' => [
            'from' => 'from-blue-900',  // Gradiente início
            'to' => 'to-blue-800',      // Gradiente fim
            'bg' => 'bg-blue-600',      // Fundo sólido
            'hover' => 'hover:bg-blue-700',
            'text' => 'text-blue-600',
            'border' => 'border-blue-600',
        ],
        
        // Cor secundária (destaques, badges)
        'secondary' => [
            'bg' => 'bg-indigo-600',
            'hover' => 'hover:bg-indigo-700',
            'text' => 'text-indigo-600',
        ],
        
        // Cor de sucesso
        'success' => [
            'bg' => 'bg-green-600',
            'hover' => 'hover:bg-green-700',
            'text' => 'text-green-600',
        ],
        
        // Cor de erro
        'danger' => [
            'bg' => 'bg-red-600',
            'hover' => 'hover:bg-red-700',
            'text' => 'text-red-600',
        ],
        
        // Cor de aviso
        'warning' => [
            'bg' => 'bg-yellow-600',
            'hover' => 'hover:bg-yellow-700',
            'text' => 'text-yellow-600',
        ],
    ],
    
    // Logo e Favicon
    'logo' => [
        'icon' => 'fas fa-calendar-check',
        'favicon' => '/public/assets/favicon.ico',
    ],
    
    // Configurações visuais
    'ui' => [
        'sidebar_width' => 'w-64',
        'border_radius' => 'rounded-lg',
        'shadow' => 'shadow-lg',
    ],
];
