/**
 * Validador de Senha v2 - Versão Limpa e Moderna
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 15:30
 * 
 * Versão simplificada com gerador de senha
 */

class PasswordValidatorV2 {
    constructor(inputId) {
        this.input = document.getElementById(inputId);
        if (!this.input) return;
        
        this.init();
    }
    
    init() {
        // Cria container do validador (SEM botão gerar)
        const container = document.createElement('div');
        container.className = 'password-validator-v2';
        container.innerHTML = `
            <div class="password-strength-bar">
                <div class="strength-fill" data-strength="0"></div>
            </div>
            <div class="password-actions">
                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar senha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="password-strength-text"></div>
        `;
        
        // Insere após o input
        this.input.parentNode.insertBefore(container, this.input.nextSibling);
        
        // Elementos
        this.strengthBar = container.querySelector('.strength-fill');
        this.strengthText = container.querySelector('.password-strength-text');
        this.btnToggle = container.querySelector('.btn-toggle-password');
        
        // Eventos
        this.input.addEventListener('input', () => this.validate());
        this.btnToggle.addEventListener('click', () => this.toggleVisibility());
        
        // Validação inicial
        if (this.input.value) {
            this.validate();
        }
    }
    
    validate() {
        const password = this.input.value;
        const strength = this.calculateStrength(password);
        
        // Atualiza barra
        this.strengthBar.style.width = `${strength.percentage}%`;
        this.strengthBar.setAttribute('data-strength', strength.level);
        
        // Atualiza texto
        this.strengthText.textContent = strength.text;
        this.strengthText.className = `password-strength-text strength-${strength.level}`;
        
        return strength.level >= 3; // Senha boa ou forte
    }
    
    calculateStrength(password) {
        if (!password) {
            return { level: 0, percentage: 0, text: '' };
        }
        
        let score = 0;
        
        // Critérios
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        // Níveis
        if (score <= 2) {
            return { level: 1, percentage: 25, text: 'Senha fraca' };
        } else if (score <= 4) {
            return { level: 2, percentage: 50, text: 'Senha média' };
        } else if (score <= 5) {
            return { level: 3, percentage: 75, text: 'Senha boa' };
        } else {
            return { level: 4, percentage: 100, text: 'Senha forte' };
        }
    }
    
    toggleVisibility() {
        const type = this.input.type === 'password' ? 'text' : 'password';
        this.input.type = type;
        
        const icon = this.btnToggle.querySelector('i');
        icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }
    
    generatePassword() {
        const length = 12;
        const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        let password = '';
        
        // Garante pelo menos um de cada tipo
        password += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 26)];
        password += 'abcdefghijklmnopqrstuvwxyz'[Math.floor(Math.random() * 26)];
        password += '0123456789'[Math.floor(Math.random() * 10)];
        password += '!@#$%&*'[Math.floor(Math.random() * 7)];
        
        // Completa o resto
        for (let i = password.length; i < length; i++) {
            password += charset[Math.floor(Math.random() * charset.length)];
        }
        
        // Embaralha
        password = password.split('').sort(() => Math.random() - 0.5).join('');
        
        // Define no input atual
        this.input.value = password;
        this.input.type = 'text'; // Mostra a senha gerada
        this.validate();
        
        // Atualiza ícone do toggle
        const icon = this.btnToggle.querySelector('i');
        icon.className = 'fas fa-eye-slash';
        
        // Procura campo de confirmação e preenche com a MESMA senha
        const confirmFields = [
            'confirmar_senha',
            'confirmar_nova_senha',
            'password_confirm',
            'confirm_password'
        ];
        
        for (const fieldId of confirmFields) {
            const confirmInput = document.getElementById(fieldId);
            if (confirmInput && confirmInput !== this.input) {
                confirmInput.value = password;
                confirmInput.type = 'text'; // Mostra também
                
                // Dispara validação no campo de confirmação
                const event = new Event('input', { bubbles: true });
                confirmInput.dispatchEvent(event);
                
                // Feedback visual no campo de confirmação
                confirmInput.classList.add('password-generated');
                setTimeout(() => {
                    confirmInput.classList.remove('password-generated');
                }, 1000);
                
                break; // Encontrou, para de procurar
            }
        }
        
        // Feedback visual no campo atual
        this.input.classList.add('password-generated');
        setTimeout(() => {
            this.input.classList.remove('password-generated');
        }, 1000);
        
        // Copia para clipboard
        navigator.clipboard.writeText(password).then(() => {
            this.showToast('Senha gerada e preenchida nos dois campos!');
        });
    }
    
    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'password-toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }
}

// Auto-inicializa em campos com classe 'validate-password'
document.addEventListener('DOMContentLoaded', function() {
    const passwordFields = document.querySelectorAll('.validate-password');
    
    // Inicializa validadores
    passwordFields.forEach(input => {
        new PasswordValidatorV2(input.id);
    });
    
    // Se tem 2 campos de senha (senha + confirmar), adiciona botão único
    if (passwordFields.length === 2) {
        const firstField = passwordFields[0];
        const secondField = passwordFields[1];
        
        // Encontra o container pai comum (pode ser form, div, etc)
        let commonParent = firstField.parentNode;
        while (commonParent && !commonParent.contains(secondField)) {
            commonParent = commonParent.parentNode;
        }
        
        // Cria botão único de gerar senha
        const btnContainer = document.createElement('div');
        btnContainer.className = 'password-generate-container';
        btnContainer.innerHTML = `
            <button type="button" class="btn-generate-both-passwords">
                <i class="fas fa-key"></i> Gerar senha forte
            </button>
        `;
        
        // Insere no topo do container comum, antes de tudo
        if (commonParent) {
            commonParent.insertBefore(btnContainer, commonParent.firstChild);
        } else {
            // Fallback: insere antes do primeiro campo
            firstField.parentNode.insertBefore(btnContainer, firstField);
        }
        
        // Evento do botão
        const btn = btnContainer.querySelector('.btn-generate-both-passwords');
        btn.addEventListener('click', function() {
            // Gera senha
            const length = 12;
            const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
            let password = '';
            
            password += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 26)];
            password += 'abcdefghijklmnopqrstuvwxyz'[Math.floor(Math.random() * 26)];
            password += '0123456789'[Math.floor(Math.random() * 10)];
            password += '!@#$%&*'[Math.floor(Math.random() * 7)];
            
            for (let i = password.length; i < length; i++) {
                password += charset[Math.floor(Math.random() * charset.length)];
            }
            
            password = password.split('').sort(() => Math.random() - 0.5).join('');
            
            // Preenche ambos os campos
            firstField.value = password;
            secondField.value = password;
            
            // Mostra as senhas
            firstField.type = 'text';
            secondField.type = 'text';
            
            // Dispara validação
            firstField.dispatchEvent(new Event('input', { bubbles: true }));
            secondField.dispatchEvent(new Event('input', { bubbles: true }));
            
            // Animação
            firstField.classList.add('password-generated');
            secondField.classList.add('password-generated');
            setTimeout(() => {
                firstField.classList.remove('password-generated');
                secondField.classList.remove('password-generated');
            }, 1000);
            
            // Copia e notifica
            navigator.clipboard.writeText(password).then(() => {
                const toast = document.createElement('div');
                toast.className = 'password-toast';
                toast.textContent = '✅ Senha forte gerada e preenchida!';
                document.body.appendChild(toast);
                
                setTimeout(() => toast.classList.add('show'), 10);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 2000);
            });
        });
    }
});
