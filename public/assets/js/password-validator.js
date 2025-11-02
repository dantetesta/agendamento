/**
 * Validador de Senha Forte com Indicador Visual
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 14:25
 */

class PasswordValidator {
    constructor(inputId, options = {}) {
        this.input = document.getElementById(inputId);
        if (!this.input) {
            console.error(`Input ${inputId} não encontrado`);
            return;
        }
        
        this.options = {
            minLength: options.minLength || 8,
            requireUppercase: options.requireUppercase !== false,
            requireLowercase: options.requireLowercase !== false,
            requireNumber: options.requireNumber !== false,
            requireSpecial: options.requireSpecial !== false,
            showToggle: options.showToggle !== false,
            showStrength: options.showStrength !== false,
            ...options
        };
        
        this.init();
    }
    
    init() {
        // Cria wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'password-validator-wrapper';
        this.input.parentNode.insertBefore(wrapper, this.input);
        wrapper.appendChild(this.input);
        
        // Adiciona classes ao input
        this.input.classList.add('password-input');
        
        // Cria botão de mostrar/ocultar
        if (this.options.showToggle) {
            this.createToggleButton(wrapper);
        }
        
        // Cria indicador de força
        if (this.options.showStrength) {
            this.createStrengthIndicator(wrapper);
        }
        
        // Cria lista de requisitos
        this.createRequirementsList(wrapper);
        
        // Event listeners
        this.input.addEventListener('input', () => this.validate());
        this.input.addEventListener('blur', () => this.validate());
    }
    
    createToggleButton(wrapper) {
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        toggleBtn.setAttribute('aria-label', 'Mostrar senha');
        
        toggleBtn.addEventListener('click', () => {
            const type = this.input.type === 'password' ? 'text' : 'password';
            this.input.type = type;
            
            const icon = type === 'password' ? 'fa-eye' : 'fa-eye-slash';
            toggleBtn.innerHTML = `<i class="fas ${icon}"></i>`;
            toggleBtn.setAttribute('aria-label', type === 'password' ? 'Mostrar senha' : 'Ocultar senha');
        });
        
        wrapper.style.position = 'relative';
        wrapper.appendChild(toggleBtn);
    }
    
    createStrengthIndicator(wrapper) {
        const indicator = document.createElement('div');
        indicator.className = 'password-strength-indicator';
        indicator.innerHTML = `
            <div class="strength-bar">
                <div class="strength-bar-fill"></div>
            </div>
            <div class="strength-text">Digite uma senha</div>
        `;
        wrapper.appendChild(indicator);
        
        this.strengthBar = indicator.querySelector('.strength-bar-fill');
        this.strengthText = indicator.querySelector('.strength-text');
    }
    
    createRequirementsList(wrapper) {
        const list = document.createElement('ul');
        list.className = 'password-requirements';
        
        const requirements = [
            { id: 'length', text: `Mínimo ${this.options.minLength} caracteres`, check: () => this.input.value.length >= this.options.minLength },
            { id: 'uppercase', text: 'Uma letra maiúscula (A-Z)', check: () => /[A-Z]/.test(this.input.value), enabled: this.options.requireUppercase },
            { id: 'lowercase', text: 'Uma letra minúscula (a-z)', check: () => /[a-z]/.test(this.input.value), enabled: this.options.requireLowercase },
            { id: 'number', text: 'Um número (0-9)', check: () => /[0-9]/.test(this.input.value), enabled: this.options.requireNumber },
            { id: 'special', text: 'Um caractere especial (!@#$%)', check: () => /[^a-zA-Z0-9]/.test(this.input.value), enabled: this.options.requireSpecial }
        ];
        
        this.requirements = requirements.filter(req => req.enabled !== false);
        
        this.requirements.forEach(req => {
            const li = document.createElement('li');
            li.id = `req-${req.id}`;
            li.innerHTML = `
                <i class="fas fa-circle"></i>
                <span>${req.text}</span>
            `;
            list.appendChild(li);
            req.element = li;
        });
        
        wrapper.appendChild(list);
    }
    
    validate() {
        const password = this.input.value;
        let metRequirements = 0;
        
        // Valida cada requisito
        this.requirements.forEach(req => {
            const met = req.check();
            if (met) {
                req.element.classList.add('met');
                req.element.classList.remove('unmet');
                metRequirements++;
            } else {
                req.element.classList.add('unmet');
                req.element.classList.remove('met');
            }
        });
        
        // Atualiza indicador de força
        if (this.options.showStrength && password.length > 0) {
            const strength = this.calculateStrength(password, metRequirements);
            this.updateStrengthIndicator(strength);
        }
        
        // Retorna se senha é válida
        return metRequirements === this.requirements.length;
    }
    
    calculateStrength(password, metRequirements) {
        const percentage = (metRequirements / this.requirements.length) * 100;
        
        if (percentage < 40) {
            return { level: 'weak', text: 'Senha fraca', percentage };
        } else if (percentage < 80) {
            return { level: 'medium', text: 'Senha média', percentage };
        } else if (percentage < 100) {
            return { level: 'good', text: 'Senha boa', percentage };
        } else {
            return { level: 'strong', text: 'Senha forte', percentage };
        }
    }
    
    updateStrengthIndicator(strength) {
        this.strengthBar.style.width = strength.percentage + '%';
        this.strengthBar.className = 'strength-bar-fill strength-' + strength.level;
        this.strengthText.textContent = strength.text;
        this.strengthText.className = 'strength-text strength-' + strength.level;
    }
    
    isValid() {
        return this.validate();
    }
}

// Inicialização automática
document.addEventListener('DOMContentLoaded', function() {
    // Auto-inicializa inputs com classe 'validate-password'
    document.querySelectorAll('.validate-password').forEach(input => {
        new PasswordValidator(input.id);
    });
});
