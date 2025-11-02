<?php
/**
 * Classe ReCaptcha - Verificação Google reCAPTCHA v3
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:40
 */

class ReCaptcha {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/recaptcha.php';
    }
    
    /**
     * Verifica se reCAPTCHA está habilitado
     */
    public function isEnabled() {
        return $this->config['enabled'];
    }
    
    /**
     * Obtém a chave do site (pública)
     */
    public function getSiteKey() {
        return $this->config['site_key'];
    }
    
    /**
     * Verifica token do reCAPTCHA
     */
    public function verify($token, $action = 'submit') {
        if (!$this->isEnabled()) {
            return ['success' => true, 'message' => 'reCAPTCHA desabilitado'];
        }
        
        if (empty($token)) {
            return ['success' => false, 'message' => 'Token reCAPTCHA não fornecido'];
        }
        
        // Prepara requisição para Google
        $data = [
            'secret' => $this->config['secret_key'],
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => $this->config['timeout'],
            ],
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        
        if ($response === false) {
            return ['success' => false, 'message' => 'Erro ao conectar com reCAPTCHA'];
        }
        
        $result = json_decode($response, true);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Verificação reCAPTCHA falhou',
                'errors' => $result['error-codes'] ?? [],
            ];
        }
        
        // Verifica score (v3)
        if (isset($result['score'])) {
            if ($result['score'] < $this->config['min_score']) {
                return [
                    'success' => false,
                    'message' => 'Score reCAPTCHA muito baixo',
                    'score' => $result['score'],
                ];
            }
        }
        
        // Verifica action
        if (isset($result['action']) && $result['action'] !== $action) {
            return [
                'success' => false,
                'message' => 'Action reCAPTCHA inválida',
            ];
        }
        
        return [
            'success' => true,
            'score' => $result['score'] ?? null,
            'action' => $result['action'] ?? null,
        ];
    }
    
    /**
     * Renderiza script do reCAPTCHA
     */
    public function renderScript() {
        if (!$this->isEnabled()) {
            return '';
        }
        
        $siteKey = $this->getSiteKey();
        
        return <<<HTML
<script src="https://www.google.com/recaptcha/api.js?render={$siteKey}"></script>
<script>
    // Função global para executar reCAPTCHA
    function executeRecaptcha(action) {
        return new Promise((resolve, reject) => {
            grecaptcha.ready(function() {
                grecaptcha.execute('{$siteKey}', {action: action})
                    .then(function(token) {
                        resolve(token);
                    })
                    .catch(function(error) {
                        reject(error);
                    });
            });
        });
    }
</script>
HTML;
    }
    
    /**
     * Adiciona token ao formulário via JavaScript
     */
    public function renderFormScript($formId, $action = 'submit') {
        if (!$this->isEnabled()) {
            return '';
        }
        
        return <<<HTML
<script>
    // Aguarda DOM estar pronto
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('{$formId}');
        
        if (!form) {
            console.error('Formulário {$formId} não encontrado');
            return;
        }
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Desabilita botão para evitar múltiplos cliques
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verificando...';
            }
            
            executeRecaptcha('{$action}').then(function(token) {
                // Remove token antigo se existir
                const oldToken = form.querySelector('input[name="recaptcha_token"]');
                if (oldToken) {
                    oldToken.remove();
                }
                
                // Adiciona novo token
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'recaptcha_token';
                input.value = token;
                form.appendChild(input);
                
                // Submete formulário
                form.submit();
            }).catch(function(error) {
                console.error('Erro reCAPTCHA:', error);
                alert('Erro ao verificar reCAPTCHA. Por favor, recarregue a página e tente novamente.');
                
                // Reabilita botão
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        });
    });
</script>
HTML;
    }
}
