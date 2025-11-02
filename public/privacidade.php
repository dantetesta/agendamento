<?php
/**
 * Política de Privacidade - Agenda Master
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 31/10/2025 09:28
 */

require_once __DIR__ . '/../core/Helpers.php';
$theme = require __DIR__ . '/../config/theme.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - <?= $theme['app_name'] ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <header class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="<?= $theme['logo']['icon'] ?> text-blue-600 mr-2"></i>
                    <?= $theme['app_name'] ?>
                </h1>
                <a href="/" class="text-blue-600 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </header>
    
    <main class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white rounded-lg shadow-sm p-8">
            
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Política de Privacidade</h2>
            
            <div class="prose prose-blue max-w-none space-y-6 text-gray-700">
                
                <p class="text-sm text-gray-500">Última atualização: <?= date('d/m/Y') ?></p>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">1. Informações que Coletamos</h3>
                    <p>Ao usar o <?= $theme['app_name'] ?>, podemos coletar: Nome, e-mail, telefone, dados de agendamentos, horários, clientes, endereço IP, navegador e cookies.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">2. Como Usamos suas Informações</h3>
                    <p>Utilizamos suas informações para fornecer o serviço, processar agendamentos, enviar notificações e melhorar a experiência do usuário.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">3. Segurança dos Dados</h3>
                    <p>Implementamos criptografia de senhas, conexões seguras HTTPS, acesso restrito e backups regulares.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">4. Seus Direitos (LGPD)</h3>
                    <p>Você tem direito a acessar, corrigir, excluir seus dados e revogar seu consentimento conforme a LGPD.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">5. Contato</h3>
                    <p>E-mail: <a href="mailto:contato@dantetesta.com.br" class="text-blue-600 hover:underline">contato@dantetesta.com.br</a></p>
                </section>
                
            </div>
            
        </div>
    </main>
    
    <footer class="bg-gray-900 text-white py-8 mt-12">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p>&copy; <?= date('Y') ?> <?= $theme['app_name'] ?> - Todos os direitos reservados</p>
            <p class="mt-2 text-sm text-gray-400">
                Por <a href="https://dantetesta.com.br" target="_blank" class="text-blue-400 hover:underline">Dante Testa</a>
            </p>
        </div>
    </footer>
    
</body>
</html>
