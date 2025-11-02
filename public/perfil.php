<?php
/**
 * Página de Perfil - Agenda do Professor Inteligente
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../app/Models/Professor.php';

Auth::requireAuth();

$user = Auth::user();
$professorModel = new Professor();
$professor = $professorModel->findById(Auth::id());

$errors = [];

// Processa formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Atualizar dados pessoais
    if (isset($_POST['atualizar_dados'])) {
        $nome = sanitize($_POST['nome'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $timezone = sanitize($_POST['timezone'] ?? 'America/Sao_Paulo');
        
        if (empty($nome)) {
            $errors[] = 'O nome é obrigatório.';
        }
        
        if (empty($email)) {
            $errors[] = 'O e-mail é obrigatório.';
        } elseif (!validEmail($email)) {
            $errors[] = 'E-mail inválido.';
        }
        
        // Verifica se e-mail já existe
        if (empty($errors) && $email !== $professor['email']) {
            if ($professorModel->emailExists($email, Auth::id())) {
                $errors[] = 'Este e-mail já está em uso.';
            }
        }
        
        if (empty($errors)) {
            $professorModel->update(Auth::id(), [
                'nome' => $nome,
                'email' => $email,
                'timezone' => $timezone
            ]);
            
            // Atualiza sessão
            $_SESSION['professor_nome'] = $nome;
            $_SESSION['professor_email'] = $email;
            $_SESSION['professor_timezone'] = $timezone;
            
            // Define timezone do PHP
            date_default_timezone_set($timezone);
            
            setFlash('success', 'Dados atualizados com sucesso!');
            redirect('/perfil');
        }
    }
    
    // Alterar senha
    if (isset($_POST['alterar_senha'])) {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($senhaAtual)) {
            $errors[] = 'Digite sua senha atual.';
        } elseif (!verifyPassword($senhaAtual, $professor['senha_hash'])) {
            $errors[] = 'Senha atual incorreta.';
        }
        
        if (empty($novaSenha)) {
            $errors[] = 'A nova senha é obrigatória.';
        } elseif (!validPassword($novaSenha)) {
            $errors[] = 'A nova senha deve ter no mínimo 6 caracteres.';
        }
        
        if ($novaSenha !== $confirmarSenha) {
            $errors[] = 'As senhas não coincidem.';
        }
        
        if (empty($errors)) {
            $professorModel->updatePassword(Auth::id(), hashPassword($novaSenha));
            
            setFlash('success', 'Senha alterada com sucesso!');
            redirect('/perfil');
        }
    }
    
    // Upload de foto com crop (AJAX)
    if (isset($_POST['upload_foto_crop'])) {
        header('Content-Type: application/json');
        
        if (isset($_FILES['foto_crop']) && $_FILES['foto_crop']['error'] === UPLOAD_ERR_OK) {
            
            try {
                $config = require __DIR__ . '/../config/app.php';
                $uploadDir = $config['upload']['path'];
                
                // Cria diretório se não existir
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Remove foto antiga
                if ($professor['foto']) {
                    $oldFile = $uploadDir . $professor['foto'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                
                // Lê imagem do upload
                $imagemTemp = $_FILES['foto_crop']['tmp_name'];
                $imagem = imagecreatefromstring(file_get_contents($imagemTemp));
                
                if (!$imagem) {
                    throw new Exception('Erro ao processar imagem');
                }
                
                // Redimensiona para 300x300 (garantia)
                $largura = imagesx($imagem);
                $altura = imagesy($imagem);
                
                $novaImagem = imagecreatetruecolor(300, 300);
                
                // Preserva transparência
                imagealphablending($novaImagem, false);
                imagesavealpha($novaImagem, true);
                
                // Redimensiona com alta qualidade
                imagecopyresampled(
                    $novaImagem, $imagem,
                    0, 0, 0, 0,
                    300, 300, $largura, $altura
                );
                
                // Gera nome único
                $novoNome = uniqid('perfil_', true) . '.webp';
                $destino = $uploadDir . $novoNome;
                
                // Converte para WebP com qualidade 90
                if (!imagewebp($novaImagem, $destino, 90)) {
                    throw new Exception('Erro ao salvar imagem WebP');
                }
                
                // Libera memória
                imagedestroy($imagem);
                imagedestroy($novaImagem);
                
                // Atualiza banco de dados
                $professorModel->updateFoto(Auth::id(), $novoNome);
                
                // Atualiza sessão
                $_SESSION['professor_foto'] = $novoNome;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Foto atualizada com sucesso!',
                    'foto' => $novoNome
                ]);
                exit;
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                exit;
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Nenhuma imagem foi enviada'
            ]);
            exit;
        }
    }
    
    // Upload de foto (método antigo - manter para compatibilidade)
    if (isset($_POST['upload_foto'])) {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            
            $validation = validImageUpload($_FILES['foto']);
            
            if (!$validation['success']) {
                $errors[] = $validation['error'];
            } else {
                $config = require __DIR__ . '/../config/app.php';
                $uploadDir = $config['upload']['path'];
                
                // Cria diretório se não existir
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Remove foto antiga
                if ($professor['foto']) {
                    $oldFile = $uploadDir . $professor['foto'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                
                // Salva nova foto
                $novoNome = generateUniqueFilename($_FILES['foto']['name']);
                $destino = $uploadDir . $novoNome;
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $professorModel->updateFoto(Auth::id(), $novoNome);
                    
                    // Atualiza sessão
                    $_SESSION['professor_foto'] = $novoNome;
                    
                    setFlash('success', 'Foto atualizada com sucesso!');
                    redirect('/perfil');
                } else {
                    $errors[] = 'Erro ao fazer upload da foto.';
                }
            }
        } else {
            $errors[] = 'Nenhuma foto foi enviada.';
        }
    }
    
    // Remover foto
    if (isset($_POST['remover_foto'])) {
        if ($professor['foto']) {
            $config = require __DIR__ . '/../config/app.php';
            $fotoPath = $config['upload']['path'] . $professor['foto'];
            
            if (file_exists($fotoPath)) {
                unlink($fotoPath);
            }
            
            $professorModel->updateFoto(Auth::id(), null);
            $_SESSION['professor_foto'] = null;
            
            setFlash('success', 'Foto removida com sucesso!');
            redirect('/perfil');
        }
    }
}

// Recarrega dados do professor
$professor = $professorModel->findById(Auth::id());
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Agenda do Professor Inteligente</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Cropper.js para crop de imagem -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    
    <style>
        .sidebar { transition: transform 0.3s ease-in-out; }
        @media (max-width: 768px) {
            .sidebar.hidden { transform: translateX(-100%); }
        }
        
        #preview {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <?php include __DIR__ . '/../app/Views/partials/sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto">
            
            <!-- Header -->
            <header class="bg-white shadow-sm sticky top-0 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="md:hidden text-gray-600">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                            Meu Perfil
                        </h2>
                    </div>
                </div>
            </header>
            
            <!-- Conteúdo -->
            <div class="p-6 space-y-6">
                
                <!-- Mensagens -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <p class="text-red-700"><?= sanitize($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($msg = flash('success')): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                            <p class="text-green-700"><?= sanitize($msg) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Foto de Perfil -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">
                            <i class="fas fa-camera mr-2 text-blue-600"></i>
                            Foto de Perfil
                        </h3>
                        
                        <div class="text-center">
                            <?php if ($professor['foto']): ?>
                                <img id="currentPhoto" 
                                     src="<?= upload('users/' . sanitize($professor['foto'])) ?>" 
                                     alt="Foto de perfil" 
                                     class="w-40 h-40 rounded-full object-cover mx-auto mb-4 border-4 border-blue-100 shadow-lg">
                            <?php else: ?>
                                <div class="w-40 h-40 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4 text-4xl text-blue-600 font-bold shadow-lg">
                                    <?= strtoupper(substr($professor['nome'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" id="inputFoto" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="hidden">
                            
                            <button type="button" onclick="document.getElementById('inputFoto').click()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition mb-3">
                                <i class="fas fa-camera mr-2"></i>
                                <?= $professor['foto'] ? 'Alterar Foto' : 'Adicionar Foto' ?>
                            </button>
                            
                            <?php if ($professor['foto']): ?>
                                <form method="POST" class="mt-2" onsubmit="return confirm('Tem certeza que deseja remover a foto?')">
                                    <button type="submit" name="remover_foto"
                                            class="text-red-600 hover:text-red-700 text-sm font-medium">
                                        <i class="fas fa-trash mr-1"></i>
                                        Remover Foto
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <p class="text-xs text-gray-500 mt-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                Formatos aceitos: JPG, PNG, GIF, WebP<br>
                                Tamanho final: 300x300px WebP
                            </p>
                        </div>
                    </div>
                    
                    <!-- Dados Pessoais -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Informações Básicas -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-6">
                                <i class="fas fa-user mr-2 text-blue-600"></i>
                                Informações Básicas
                            </h3>
                            
                            <form method="POST" class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-1"></i>
                                        Nome Completo *
                                    </label>
                                    <input type="text" name="nome" required
                                           value="<?= sanitize($professor['nome']) ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-1"></i>
                                        E-mail *
                                    </label>
                                    <input type="email" name="email" required
                                           value="<?= sanitize($professor['email']) ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-globe mr-1"></i>
                                        Fuso Horário *
                                    </label>
                                    <select name="timezone" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <?php
                                        // Pega todos os timezones do mundo
                                        $allTimezones = DateTimeZone::listIdentifiers();
                                        $currentTimezone = $professor['timezone'] ?? 'America/Sao_Paulo';
                                        
                                        // Agrupa por continente
                                        $timezonesByRegion = [];
                                        foreach ($allTimezones as $tz) {
                                            $parts = explode('/', $tz);
                                            $region = $parts[0];
                                            if (!isset($timezonesByRegion[$region])) {
                                                $timezonesByRegion[$region] = [];
                                            }
                                            $timezonesByRegion[$region][] = $tz;
                                        }
                                        
                                        // Ordem preferencial
                                        $regionOrder = ['America', 'Europe', 'Asia', 'Africa', 'Pacific', 'Atlantic', 'Indian', 'Antarctica', 'Arctic'];
                                        
                                        foreach ($regionOrder as $region):
                                            if (!isset($timezonesByRegion[$region])) continue;
                                        ?>
                                            <optgroup label="<?= $region ?>">
                                                <?php foreach ($timezonesByRegion[$region] as $tz): 
                                                    // Calcula offset
                                                    try {
                                                        $dateTimeZone = new DateTimeZone($tz);
                                                        $dateTime = new DateTime('now', $dateTimeZone);
                                                        $offset = $dateTimeZone->getOffset($dateTime);
                                                        $hours = floor($offset / 3600);
                                                        $minutes = abs(($offset % 3600) / 60);
                                                        $offsetStr = sprintf("GMT%+d%s", $hours, $minutes ? ":$minutes" : "");
                                                        
                                                        // Nome amigável
                                                        $name = str_replace(['_', '/'], [' ', ' / '], $tz);
                                                        $label = "$name ($offsetStr)";
                                                    } catch (Exception $e) {
                                                        $label = $tz;
                                                    }
                                                ?>
                                                    <option value="<?= $tz ?>" <?= $currentTimezone === $tz ? 'selected' : '' ?>>
                                                        <?= $label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Seus horários serão exibidos neste fuso horário
                                    </p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar mr-1"></i>
                                        Membro desde
                                    </label>
                                    <input type="text" readonly
                                           value="<?= formatDate($professor['criado_em']) ?>"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-600">
                                </div>
                                
                                <button type="submit" name="atualizar_dados"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                                    <i class="fas fa-save mr-2"></i>
                                    Salvar Alterações
                                </button>
                            </form>
                        </div>
                        
                        <!-- Alterar Senha -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-6">
                                <i class="fas fa-lock mr-2 text-blue-600"></i>
                                Alterar Senha
                            </h3>
                            
                            <form method="POST" class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-key mr-1"></i>
                                        Senha Atual *
                                    </label>
                                    <input type="password" name="senha_atual" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-lock mr-1"></i>
                                        Nova Senha *
                                    </label>
                                    <input type="password" name="nova_senha" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Mínimo de 6 caracteres</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-lock mr-1"></i>
                                        Confirmar Nova Senha *
                                    </label>
                                    <input type="password" name="confirmar_senha" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <button type="submit" name="alterar_senha"
                                        class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                                    <i class="fas fa-check mr-2"></i>
                                    Alterar Senha
                                </button>
                            </form>
                        </div>
                        
                        <!-- ZONA DE PERIGO: Deletar Conta -->
                        <div class="bg-red-50 border-2 border-red-200 rounded-xl shadow-sm p-6 mt-6">
                            <h3 class="text-xl font-bold text-red-800 mb-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Zona de Perigo
                            </h3>
                            <p class="text-sm text-red-600 mb-6">
                                Esta ação é <strong>irreversível</strong>. Todos os seus dados serão permanentemente excluídos.
                            </p>
                            
                            <button type="button" 
                                    onclick="abrirModalDeletar()"
                                    class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Excluir Minha Conta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Deletar Conta -->
    <div id="modalDeletar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-8">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Excluir Conta?</h3>
                <p class="text-gray-600">
                    Esta ação é <strong class="text-red-600">permanente e irreversível</strong>.
                </p>
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h4 class="font-bold text-red-800 mb-2">O que será excluído:</h4>
                <ul class="text-sm text-red-700 space-y-1">
                    <li>✗ Todos os seus clientes</li>
                    <li>✗ Todos os agendamentos</li>
                    <li>✗ Todas as tags criadas</li>
                    <li>✗ Fotos e arquivos</li>
                    <li>✗ Configurações e preferências</li>
                </ul>
            </div>
            
            <form action="/deletar_conta" method="POST" class="space-y-4">
                <?php
                require_once __DIR__ . '/../core/CSRF.php';
                echo CSRF::field();
                ?>
                
                <!-- Senha -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Digite sua senha para confirmar:
                    </label>
                    <input type="password" 
                           name="senha" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-600 focus:border-transparent"
                           placeholder="Sua senha">
                </div>
                
                <!-- Confirmação -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Digite <strong>DELETAR</strong> para confirmar:
                    </label>
                    <input type="text" 
                           name="confirmacao" 
                           required
                           pattern="DELETAR"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-600 focus:border-transparent"
                           placeholder="DELETAR">
                </div>
                
                <!-- Botões -->
                <div class="flex gap-3 mt-6">
                    <button type="button" 
                            onclick="fecharModalDeletar()"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg transition">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Excluir Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
                
            </div>
            
        </main>
        
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
        }
        
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const currentPhoto = document.getElementById('currentPhoto');
            const btnUpload = document.getElementById('btnUpload');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    currentPhoto.style.display = 'none';
                    btnUpload.classList.remove('hidden');
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    
    <!-- Modal de Crop de Imagem -->
    <div id="modalCrop" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            
            <!-- Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-crop-alt mr-2 text-blue-600"></i>
                        Ajustar Foto de Perfil
                    </h3>
                    <button onclick="fecharModalCrop()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Ajuste a área da imagem que deseja usar como foto de perfil (300x300px WebP)
                </p>
            </div>
            
            <!-- Área de Crop -->
            <div class="p-6">
                <div class="max-h-[400px] overflow-hidden bg-gray-100 rounded-lg">
                    <img id="imagemCrop" class="max-w-full">
                </div>
            </div>
            
            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-mouse-pointer mr-1"></i>
                    Arraste para ajustar | Scroll para zoom
                </div>
                <div class="flex space-x-3">
                    <button onclick="fecharModalCrop()" 
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                    <button id="btnConfirmarCrop" onclick="confirmarCrop()" 
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-check mr-2"></i>
                        Confirmar
                    </button>
                </div>
            </div>
            
        </div>
    </div>
    
    <script>
        let cropper = null;
        
        /**
         * Quando seleciona arquivo, abre modal de crop
         */
        document.getElementById('inputFoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (!file) return;
            
            // Valida tipo de arquivo
            const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!tiposPermitidos.includes(file.type)) {
                alert('❌ Formato inválido! Use JPG, PNG, GIF ou WebP.');
                return;
            }
            
            // Valida tamanho (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('❌ Arquivo muito grande! Máximo 5MB.');
                return;
            }
            
            // Lê arquivo e abre modal
            const reader = new FileReader();
            reader.onload = function(event) {
                const imagemCrop = document.getElementById('imagemCrop');
                imagemCrop.src = event.target.result;
                
                // Abre modal
                document.getElementById('modalCrop').classList.remove('hidden');
                document.getElementById('modalCrop').classList.add('flex');
                
                // Inicializa Cropper.js
                setTimeout(function() {
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(imagemCrop, {
                        aspectRatio: 1, // 1:1 (quadrado)
                        viewMode: 2,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                        minContainerWidth: 300,
                        minContainerHeight: 300
                    });
                }, 100);
            };
            
            reader.readAsDataURL(file);
        });
        
        /**
         * Fecha modal de crop
         */
        function fecharModalCrop() {
            document.getElementById('modalCrop').classList.add('hidden');
            document.getElementById('modalCrop').classList.remove('flex');
            document.getElementById('inputFoto').value = '';
            
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }
        
        /**
         * Confirma crop e envia para servidor
         */
        function confirmarCrop() {
            if (!cropper) return;
            
            // Pega o botão pelo ID
            const btnConfirmar = document.getElementById('btnConfirmarCrop');
            if (!btnConfirmar) {
                console.error('❌ Botão confirmar não encontrado!');
                return;
            }
            
            const textoOriginal = btnConfirmar.innerHTML;
            
            // Mostra loading
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
            
            // Obtém canvas com crop (300x300)
            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 300,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            // Converte para Blob
            canvas.toBlob(function(blob) {
                // Cria FormData
                const formData = new FormData();
                formData.append('foto_crop', blob, 'foto.png');
                formData.append('upload_foto_crop', '1');
                
                console.log('📤 Enviando imagem cropada...');
                
                // Envia via AJAX
                fetch('/perfil', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('📥 Resposta recebida:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('📊 Dados:', data);
                    
                    if (data.success) {
                        console.log('✅ Upload bem-sucedido!');
                        // Atualiza foto na página
                        location.reload();
                    } else {
                        alert('❌ ' + (data.error || 'Erro ao fazer upload'));
                        btnConfirmar.disabled = false;
                        btnConfirmar.innerHTML = textoOriginal;
                    }
                })
                .catch(error => {
                    console.error('❌ Erro:', error);
                    alert('❌ Erro ao fazer upload da foto: ' + error.message);
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = textoOriginal;
                });
            }, 'image/png', 0.95);
        }
        
        // Fecha modal ao clicar fora
        document.getElementById('modalCrop').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalCrop();
            }
        });
        
        // Fecha modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('modalCrop').classList.contains('hidden')) {
                fecharModalCrop();
            }
        });
        
        // ===== MODAL DELETAR CONTA =====
        function abrirModalDeletar() {
            const modal = document.getElementById('modalDeletar');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }
        
        function fecharModalDeletar() {
            const modal = document.getElementById('modalDeletar');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            // Limpa campos
            modal.querySelector('input[name="senha"]').value = '';
            modal.querySelector('input[name="confirmacao"]').value = '';
        }
        
        // Fecha modal ao clicar fora
        document.getElementById('modalDeletar')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalDeletar();
            }
        });
    </script>
    
</body>
</html>
