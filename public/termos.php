<?php
/**
 * Termos de Uso - Agenda Master
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
    <title>Termos de Uso - <?= $theme['app_name'] ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
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
    
    <!-- Content -->
    <main class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white rounded-lg shadow-sm p-8">
            
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Termos de Uso</h2>
            
            <div class="prose prose-blue max-w-none space-y-6 text-gray-700">
                
                <p class="text-sm text-gray-500">Última atualização: <?= date('d/m/Y') ?></p>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">1. Aceitação dos Termos</h3>
                    <p>Ao acessar e usar o <?= $theme['app_name'] ?>, você concorda em cumprir e estar vinculado aos seguintes termos e condições de uso.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">2. Descrição do Serviço</h3>
                    <p>O <?= $theme['app_name'] ?> é um sistema de agendamento online que permite aos professores e profissionais gerenciar seus horários, clientes e compromissos de forma eficiente.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">3. Cadastro e Conta</h3>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Você deve fornecer informações precisas e completas durante o cadastro</li>
                        <li>Você é responsável por manter a confidencialidade de sua senha</li>
                        <li>Você é responsável por todas as atividades que ocorrem em sua conta</li>
                        <li>Você deve notificar imediatamente sobre qualquer uso não autorizado</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">4. Uso Aceitável</h3>
                    <p>Você concorda em NÃO:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Usar o serviço para qualquer finalidade ilegal</li>
                        <li>Tentar obter acesso não autorizado ao sistema</li>
                        <li>Interferir ou interromper o serviço</li>
                        <li>Copiar, modificar ou distribuir o conteúdo sem autorização</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">5. Propriedade Intelectual</h3>
                    <p>Todo o conteúdo, recursos e funcionalidades do <?= $theme['app_name'] ?> são de propriedade exclusiva e estão protegidos por leis de direitos autorais.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">6. Limitação de Responsabilidade</h3>
                    <p>O <?= $theme['app_name'] ?> é fornecido "como está". Não garantimos que o serviço será ininterrupto ou livre de erros.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">7. Modificações</h3>
                    <p>Reservamos o direito de modificar estes termos a qualquer momento. As alterações entrarão em vigor imediatamente após a publicação.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">8. Cancelamento</h3>
                    <p>Você pode cancelar sua conta a qualquer momento através das configurações. Reservamos o direito de suspender ou encerrar contas que violem estes termos.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-bold text-gray-900 mt-8 mb-4">9. Contato</h3>
                    <p>Para questões sobre estes termos, entre em contato através do e-mail: <a href="mailto:contato@dantetesta.com.br" class="text-blue-600 hover:underline">contato@dantetesta.com.br</a></p>
                </section>
                
            </div>
            
        </div>
    </main>
    
    <!-- Footer -->
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
