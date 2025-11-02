<?php
/**
 * Classe Mailer - Envio de E-mails via SMTP
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 10:04
 * 
 * Utiliza PHPMailer para envio de e-mails transacionais
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

class Mailer {
    private $config;
    private $mail;
    
    public function __construct() {
        $configFile = __DIR__ . '/../config/smtp.php';
        
        // Verifica se arquivo existe
        if (!file_exists($configFile)) {
            throw new Exception("
                ❌ ERRO: Arquivo de configuração SMTP não encontrado!
                
                Arquivo esperado: {$configFile}
                
                SOLUÇÃO:
                1. Crie o arquivo /config/smtp.php
                2. Copie o conteúdo de /config/smtp.php do pacote
                3. Ou crie manualmente com as configurações SMTP
                
                Exemplo de conteúdo:
                <?php
                return [
                    'host' => 'mail.dantetesta.com.br',
                    'port' => 465,
                    'username' => 'no-reply@dantetesta.com.br',
                    'password' => 'sua_senha',
                    'encryption' => 'ssl',
                    'from_email' => 'no-reply@dantetesta.com.br',
                    'from_name' => 'Sistema de Agenda',
                    'charset' => 'UTF-8'
                ];
            ");
        }
        
        $this->config = require $configFile;
        $this->mail = new PHPMailer(true);
        $this->configure();
    }
    
    /**
     * Configura PHPMailer com dados do SMTP
     */
    private function configure() {
        try {
            // Configurações do servidor
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPSecure = $this->config['encryption'];
            $this->mail->Port = $this->config['port'];
            $this->mail->CharSet = $this->config['charset'];
            
            // Remetente padrão
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Configurações adicionais
            $this->mail->isHTML(true);
            
        } catch (Exception $e) {
            error_log("Erro ao configurar PHPMailer: {$e->getMessage()}");
        }
    }
    
    /**
     * Envia e-mail
     */
    public function send($to, $subject, $content) {
        try {
            // Carrega nome do app
            $appConfig = require __DIR__ . '/../config/app.php';
            $appName = $appConfig['name'] ?? 'Agenda Master';
            
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $this->getTemplate($content, $appName);
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Template HTML para e-mails
     */
    private function getTemplate($content, $appName = 'Agenda Master') {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }
                .content {
                    background: #f9f9f9;
                    padding: 30px;
                    border-radius: 0 0 10px 10px;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    background: #667eea;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1 style='margin: 0;'>📅 {$appName}</h1>
            </div>
            <div class='content'>
                {$content}
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " {$appName} - Todos os direitos reservados</p>
                <p>Desenvolvido por <a href='https://dantetesta.com.br'>Dante Testa</a></p>
            </div>
        </body>
        </html>
        ";
    }
}
