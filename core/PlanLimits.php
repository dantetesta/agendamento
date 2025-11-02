<?php
/**
 * Classe PlanLimits - Verificação de Limites de Plano
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 16:40
 */

class PlanLimits {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->config = require __DIR__ . '/../config/plans.php';
    }
    
    /**
     * Obtém o plano do professor
     */
    public function getPlan($professorId) {
        $stmt = $this->db->prepare("
            SELECT plano, plano_expira_em 
            FROM professores 
            WHERE id = ?
        ");
        $stmt->execute([$professorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['plano']) {
            return $this->config['default_plan'];
        }
        
        // Verifica se plano expirou
        if ($result['plano_expira_em'] && strtotime($result['plano_expira_em']) < time()) {
            return $this->config['default_plan'];
        }
        
        return $result['plano'];
    }
    
    /**
     * Obtém configurações do plano
     */
    public function getPlanConfig($planSlug) {
        return $this->config['plans'][$planSlug] ?? $this->config['plans'][$this->config['default_plan']];
    }
    
    /**
     * Verifica se pode criar novo agendamento
     */
    public function canCreateAgendamento($professorId) {
        $plan = $this->getPlan($professorId);
        $planConfig = $this->getPlanConfig($plan);
        
        $limit = $planConfig['limits']['agendamentos_ativos'];
        
        // -1 = ilimitado
        if ($limit === -1) {
            return ['allowed' => true];
        }
        
        // Conta agendamentos ativos
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM agendamentos 
            WHERE professor_id = ? 
            AND data_aula >= CURDATE()
            AND status != 'cancelado'
        ");
        $stmt->execute([$professorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $current = $result['total'];
        
        if ($current >= $limit) {
            return [
                'allowed' => false,
                'message' => str_replace('{plan}', $planConfig['name'], $this->config['messages']['limit_reached']),
                'current' => $current,
                'limit' => $limit,
            ];
        }
        
        return [
            'allowed' => true,
            'current' => $current,
            'limit' => $limit,
            'remaining' => $limit - $current,
        ];
    }
    
    /**
     * Verifica se pode usar recurso
     */
    public function canUseFeature($professorId, $feature) {
        $plan = $this->getPlan($professorId);
        $planConfig = $this->getPlanConfig($plan);
        
        return $planConfig['features'][$feature] ?? false;
    }
    
    /**
     * Obtém estatísticas de uso
     */
    public function getUsageStats($professorId) {
        $plan = $this->getPlan($professorId);
        $planConfig = $this->getPlanConfig($plan);
        
        // Agendamentos ativos
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM agendamentos 
            WHERE professor_id = ? 
            AND data_aula >= CURDATE()
            AND status != 'cancelado'
        ");
        $stmt->execute([$professorId]);
        $agendamentos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Agendamentos no mês
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM agendamentos 
            WHERE professor_id = ? 
            AND MONTH(data_aula) = MONTH(CURDATE())
            AND YEAR(data_aula) = YEAR(CURDATE())
        ");
        $stmt->execute([$professorId]);
        $agendamentosMes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'plan' => $plan,
            'plan_name' => $planConfig['name'],
            'usage' => [
                'agendamentos_ativos' => [
                    'current' => $agendamentos,
                    'limit' => $planConfig['limits']['agendamentos_ativos'],
                    'percentage' => $planConfig['limits']['agendamentos_ativos'] > 0 
                        ? round(($agendamentos / $planConfig['limits']['agendamentos_ativos']) * 100) 
                        : 0,
                ],
                'agendamentos_mes' => [
                    'current' => $agendamentosMes,
                    'limit' => $planConfig['limits']['agendamentos_mes'],
                ],
            ],
        ];
    }
}
