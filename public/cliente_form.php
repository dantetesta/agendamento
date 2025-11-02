<?php
/**
 * Formulário de Cliente - Agenda Professor
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 17:52
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../app/Models/Cliente.php';
require_once __DIR__ . '/../app/Models/Tag.php';

// Verifica autenticação
Auth::requireAuth();

// Define variável $user para o sidebar
$user = Auth::user();

$clienteModel = new Cliente();
$tagModel = new Tag();
$professorId = Auth::id();

// Modo: novo ou editar
$id = $_GET['id'] ?? null;
$isEdit = !empty($id);

$cliente = null;
$errors = [];
// Busca apenas tags de CLIENTE (não de serviço)
$todasTags = $tagModel->getByCategoria('cliente');
$tagClienteId = null;

// Se está editando, busca cliente e sua tag
if ($isEdit) {
    $cliente = $clienteModel->findByIdAndProfessor($id, $professorId);
    
    if (!$cliente) {
        setFlash('error', 'Cliente não encontrado.');
        redirect('/clientes');
    }
    
    // Pega tag_id do cliente
    $tagClienteId = $cliente['tag_id'] ?? null;
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? '');
    $telefone = sanitize($_POST['telefone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $observacoes = sanitize($_POST['observacoes'] ?? '');
    // Status: se checkbox marcado = ativo, senão = inativo
    $status = (isset($_POST['status']) && $_POST['status'] === 'ativo') ? 'ativo' : 'inativo';
    
    // Validações
    if (empty($nome)) {
        $errors[] = 'O nome é obrigatório.';
    }
    
    if (empty($telefone)) {
        $errors[] = 'O telefone é obrigatório.';
    }
    
    if ($email && !validEmail($email)) {
        $errors[] = 'E-mail inválido.';
    }
    
    if ($email && $clienteModel->emailExists($email, $professorId, $id)) {
        $errors[] = 'Este e-mail já está cadastrado para outro cliente.';
    }
    
    // Processa upload da foto
    $fotoPath = null;
    $removerFoto = false;
    
    // Se marcou para remover
    if (isset($_POST['foto_cropped']) && $_POST['foto_cropped'] === 'REMOVER') {
        $removerFoto = true;
        if ($isEdit && !empty($cliente['foto'])) {
            // Remove /public/ do caminho pois __DIR__ já aponta para /public
            $caminhoRelativo = str_replace('/public/', '/', $cliente['foto']);
            $fotoAntiga = __DIR__ . $caminhoRelativo;
            if (file_exists($fotoAntiga)) {
                unlink($fotoAntiga);
            }
        }
    }
    // Se enviou nova foto
    elseif (!empty($_POST['foto_cropped']) && $_POST['foto_cropped'] !== 'REMOVER') {
        try {
            // Recebe imagem base64 do crop
            $img = $_POST['foto_cropped'];
            $img = str_replace('data:image/png;base64,', '', $img);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            
            // Gera nome único
            $nomeArquivo = 'cliente_' . uniqid() . '_' . time() . '.png';
            $uploadDir = __DIR__ . '/uploads/clientes/';
            
            // Cria diretório se não existir
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $caminhoCompleto = $uploadDir . $nomeArquivo;
            
            // Salva arquivo
            if (file_put_contents($caminhoCompleto, $data)) {
                $fotoPath = '/public/uploads/clientes/' . $nomeArquivo;
                
                // Se está editando e tinha foto antiga, deleta
                if ($isEdit && !empty($cliente['foto'])) {
                    // Remove /public/ do caminho pois __DIR__ já aponta para /public
                    $caminhoRelativo = str_replace('/public/', '/', $cliente['foto']);
                    $fotoAntiga = __DIR__ . $caminhoRelativo;
                    if (file_exists($fotoAntiga)) {
                        unlink($fotoAntiga);
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Erro ao processar foto: ' . $e->getMessage();
        }
    }
    
    // Salva
    if (empty($errors)) {
        $dados = [
            'nome' => $nome,
            'telefone' => $telefone,
            'email' => $email,
            'observacoes' => $observacoes,
            'status' => $status,
            'tag_id' => !empty($_POST['tag_id']) ? (int)$_POST['tag_id'] : null,
            'foto' => null, // Sempre define, será sobrescrito se necessário
        ];
        
        // Gerencia foto
        if ($removerFoto) {
            // Remove foto
            $dados['foto'] = null;
        } elseif ($fotoPath) {
            // Nova foto enviada
            $dados['foto'] = $fotoPath;
        } elseif ($isEdit && isset($cliente['foto'])) {
            // Mantém foto existente se não houver alteração
            $dados['foto'] = $cliente['foto'];
        }
        
        if ($isEdit) {
            $clienteModel->update($id, $dados);
            setFlash('success', 'Cliente atualizado com sucesso!');
        } else {
            $dados['professor_id'] = $professorId;
            $clienteModel->create($dados);
            setFlash('success', 'Cliente cadastrado com sucesso!');
        }
        
        redirect('/clientes');
    }
}

$currentPage = 'clientes.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar' : 'Novo' ?> Cliente - Agenda Professor</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- International Telephone Input -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
    
    <!-- Cropper.js para recorte de imagem -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    
    <style>
        /* Custom radio button styling */
        input[type="radio"]:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <?php include __DIR__ . '/../app/Views/partials/sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Header -->
            <header class="bg-white shadow-sm sticky top-0 z-40">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="md:hidden text-gray-600">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-user-<?= $isEdit ? 'edit' : 'plus' ?> mr-2 text-blue-600"></i>
                            <?= $isEdit ? 'Editar' : 'Novo' ?> Cliente
                        </h2>
                    </div>
                    <a href="/clientes" 
                       class="text-gray-600 hover:text-gray-800 transition">
                        <i class="fas fa-times text-xl"></i>
                    </a>
                </div>
            </header>
            
            <!-- Conteúdo -->
            <div class="flex-1 overflow-auto p-6">
                
                <!-- Erros -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                            <div>
                                <h3 class="font-bold text-red-800 mb-2">Erro ao salvar cliente:</h3>
                                <ul class="list-disc list-inside text-red-700 text-sm">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário -->
                <div class="max-w-6xl mx-auto">
                    <div class="bg-white rounded-lg shadow p-6">
                        <form method="POST" class="space-y-6">
                            
                            <!-- Linha 1: Nome | Status -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Nome -->
                                <div>
                                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2 text-gray-400"></i>
                                        Nome Completo *
                                    </label>
                                    <input type="text" 
                                           id="nome" 
                                           name="nome" 
                                           value="<?= $cliente ? sanitize($cliente['nome']) : ($_POST['nome'] ?? '') ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                           placeholder="Nome do cliente"
                                           required>
                                </div>
                                
                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-toggle-on mr-2 text-gray-400"></i>
                                        Status do Cliente
                                    </label>
                                    <div class="flex items-center space-x-4 h-12">
                                        <!-- Toggle Switch -->
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   id="status_toggle" 
                                                   name="status" 
                                                   value="ativo" 
                                                   <?= $isEdit ? ($cliente['status'] === 'ativo' ? 'checked' : '') : 'checked' ?>
                                                   class="sr-only peer">
                                            <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-500"></div>
                                        </label>
                                        
                                        <!-- Label dinâmico -->
                                        <span id="status_label" class="text-sm font-medium">
                                            <?php if ($isEdit): ?>
                                                <?= $cliente['status'] === 'ativo' ? '✅ Ativo' : '⏸️ Inativo' ?>
                                            <?php else: ?>
                                                ✅ Ativo
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Foto do Cliente -->
                            <div class="border-t pt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-4">
                                    <i class="fas fa-camera mr-2 text-gray-400"></i>
                                    Foto do Cliente (300x300px)
                                </label>
                                <div class="flex items-start gap-6">
                                    <!-- Preview -->
                                    <div class="flex-shrink-0">
                                        <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-100 border-4 border-gray-200 flex items-center justify-center shadow-md">
                                            <?php if ($isEdit && !empty($cliente['foto'])): ?>
                                                <img src="<?= $cliente['foto'] ?>" id="foto_preview" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <img src="" id="foto_preview" class="w-full h-full object-cover hidden">
                                                <i class="fas fa-user text-5xl text-gray-400" id="icone_placeholder"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Controles -->
                                    <div class="flex-1">
                                        <input type="file" id="foto_input" accept="image/*" class="hidden">
                                        <input type="hidden" name="foto_cropped" id="foto_cropped">
                                        
                                        <button type="button" onclick="document.getElementById('foto_input').click()" 
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                            <i class="fas fa-upload mr-2"></i>
                                            Escolher Foto
                                        </button>
                                        
                                        <?php if ($isEdit && !empty($cliente['foto'])): ?>
                                            <button type="button" onclick="removerFoto()" 
                                                    class="ml-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                                <i class="fas fa-trash mr-2"></i>
                                                Remover
                                            </button>
                                        <?php endif; ?>
                                        
                                        <p class="text-xs text-gray-500 mt-2">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Formatos: JPG, PNG, GIF. Tamanho máximo: 5MB<br>
                                            A imagem será ajustada automaticamente para 300x300px (formato quadrado 1:1)
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Linha 2: Tag | Telefone | E-mail (3 colunas) -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Tag do Cliente -->
                                <div>
                                    <label for="tag_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-tag mr-2 text-gray-400"></i>
                                        Tipo de Cliente
                                    </label>
                                    
                                    <?php if (empty($todasTags)): ?>
                                        <p class="text-gray-500 text-sm">
                                            Nenhuma tag de cliente disponível. 
                                            <a href="/tags" class="text-blue-600 hover:underline">Criar tags</a>
                                        </p>
                                    <?php else: ?>
                                        <select name="tag_id" 
                                                id="tag_id"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent">
                                            <option value="">Sem classificação</option>
                                            <?php foreach ($todasTags as $tag): ?>
                                                <option value="<?= $tag['id'] ?>" 
                                                        <?= ($tagClienteId == $tag['id']) ? 'selected' : '' ?>>
                                                    <?= sanitize($tag['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Ex: Aluno, VIP, etc
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Telefone -->
                                <div>
                                    <label for="telefone" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-phone mr-2 text-gray-400"></i>
                                        Telefone Internacional *
                                    </label>
                                    <input type="tel" 
                                           id="telefone" 
                                           name="telefone" 
                                           value="<?= $cliente ? sanitize($cliente['telefone']) : ($_POST['telefone'] ?? '') ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                           placeholder="Digite o número"
                                           required>
                                    <input type="hidden" id="telefone_completo" name="telefone_completo">
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-globe mr-1"></i>
                                        Selecione o país
                                    </p>
                                </div>
                                
                                <!-- E-mail -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                        E-mail
                                    </label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           value="<?= $cliente ? sanitize($cliente['email']) : ($_POST['email'] ?? '') ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                           placeholder="email@exemplo.com">
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Opcional
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Linha 4: Observações (full width) -->
                            <div>
                                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-sticky-note mr-2 text-gray-400"></i>
                                    Observações
                                </label>
                                <textarea id="observacoes" 
                                          name="observacoes" 
                                          rows="4"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                          placeholder="Informações adicionais sobre o cliente..."><?= $cliente ? sanitize($cliente['observacoes']) : ($_POST['observacoes'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- Botões -->
                            <div class="flex justify-end gap-4 pt-4 border-t">
                                <a href="/clientes" 
                                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                    <i class="fas fa-save mr-2"></i>
                                    <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?> Cliente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="/public/assets/js/sidebar.js"></script>
    
    <!-- International Telephone Input -->
    <script>
        // Inicializa intl-tel-input
        const input = document.querySelector("#telefone");
        const iti = window.intlTelInput(input, {
            initialCountry: "br",
            preferredCountries: ["br", "us", "pt", "es", "ar", "mx"],
            separateDialCode: true,
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js",
            formatOnDisplay: true,
            nationalMode: false,
            autoPlaceholder: "aggressive",
            customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                return "Ex: " + selectedCountryPlaceholder;
            }
        });
        
        // Validação e formatação ao enviar
        const form = input.closest('form');
        form.addEventListener('submit', function(e) {
            // Pega o número completo com DDI
            const fullNumber = iti.getNumber();
            
            // Valida o número
            if (!iti.isValidNumber()) {
                e.preventDefault();
                alert('Por favor, insira um número de telefone válido.');
                input.focus();
                return false;
            }
            
            // Atualiza o campo com o número completo formatado
            input.value = fullNumber;
            
            console.log('Número completo:', fullNumber);
            console.log('País:', iti.getSelectedCountryData().name);
        });
        
        // Feedback visual de validação
        input.addEventListener('blur', function() {
            if (input.value.trim()) {
                if (iti.isValidNumber()) {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-green-500');
                } else {
                    input.classList.remove('border-green-500');
                    input.classList.add('border-red-500');
                }
            }
        });
        
        input.addEventListener('focus', function() {
            input.classList.remove('border-red-500', 'border-green-500');
        });
        
        // ============================================
        // TOGGLE DE STATUS
        // ============================================
        
        const statusToggle = document.getElementById('status_toggle');
        const statusLabel = document.getElementById('status_label');
        
        if (statusToggle && statusLabel) {
            statusToggle.addEventListener('change', function() {
                if (this.checked) {
                    statusLabel.textContent = '✅ Ativo';
                    statusLabel.classList.remove('text-gray-600');
                    statusLabel.classList.add('text-green-600');
                    this.value = 'ativo';
                } else {
                    statusLabel.textContent = '⏸️ Inativo';
                    statusLabel.classList.remove('text-green-600');
                    statusLabel.classList.add('text-gray-600');
                    this.value = 'inativo';
                }
            });
            
            // Define cor inicial
            if (statusToggle.checked) {
                statusLabel.classList.add('text-green-600');
            } else {
                statusLabel.classList.add('text-gray-600');
            }
        }
        
        // Tags agora usam radio buttons (tag única por cliente)
        // Não precisa mais de JavaScript especial
    </script>
    
    <!-- Modal do Cropper -->
    <div id="cropModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <i class="fas fa-crop mr-2 text-blue-600"></i>
                Ajustar Foto do Cliente
            </h3>
            <div class="mb-4 max-h-96 overflow-hidden">
                <img id="crop_image" style="max-width: 100%;">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="cancelarCrop()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </button>
                <button type="button" onclick="aplicarCrop()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-check mr-2"></i>
                    Aplicar
                </button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript do Cropper -->
    <script>
    let cropper = null;

    // Quando seleciona uma imagem
    document.getElementById('foto_input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Valida tamanho (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Arquivo muito grande! Máximo: 5MB');
            return;
        }
        
        // Valida tipo
        if (!file.type.match('image.*')) {
            alert('Por favor, selecione uma imagem válida');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(event) {
            // Define imagem no modal
            document.getElementById('crop_image').src = event.target.result;
            document.getElementById('cropModal').classList.remove('hidden');
            
            // Destroy cropper anterior se existir
            if (cropper) {
                cropper.destroy();
            }
            
            // Inicializa novo cropper
            setTimeout(() => {
                cropper = new Cropper(document.getElementById('crop_image'), {
                    aspectRatio: 1, // 1:1 (quadrado)
                    viewMode: 1,
                    minCropBoxWidth: 100,
                    minCropBoxHeight: 100,
                    autoCropArea: 1,
                    responsive: true,
                    guides: true,
                    center: true,
                    highlight: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            }, 100);
        };
        reader.readAsDataURL(file);
    });

    function aplicarCrop() {
        if (!cropper) return;
        
        // Gera imagem cropada em 300x300px
        const canvas = cropper.getCroppedCanvas({
            width: 300,
            height: 300,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        // Converte para base64
        const dataURL = canvas.toDataURL('image/png', 0.9);
        
        // Salva no campo hidden
        document.getElementById('foto_cropped').value = dataURL;
        
        // Atualiza preview
        const preview = document.getElementById('foto_preview');
        preview.src = dataURL;
        preview.classList.remove('hidden');
        
        // Esconde ícone placeholder
        const placeholder = document.getElementById('icone_placeholder');
        if (placeholder) {
            placeholder.classList.add('hidden');
        }
        
        // Fecha modal
        cancelarCrop();
    }

    function cancelarCrop() {
        document.getElementById('cropModal').classList.add('hidden');
        document.getElementById('foto_input').value = '';
        
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function removerFoto() {
        if (!confirm('Deseja realmente remover a foto?')) return;
        
        // Limpa preview
        const preview = document.getElementById('foto_preview');
        preview.src = '';
        preview.classList.add('hidden');
        
        // Mostra ícone placeholder
        const placeholder = document.getElementById('icone_placeholder');
        if (placeholder) {
            placeholder.classList.remove('hidden');
        }
        
        // Limpa campo hidden (envia vazio para deletar no backend)
        document.getElementById('foto_cropped').value = 'REMOVER';
        
        // Limpa input file
        document.getElementById('foto_input').value = '';
    }
    </script>
</body>
</html>
