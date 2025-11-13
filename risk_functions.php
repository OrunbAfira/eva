<?php
// Funções auxiliares para o sistema de risco

/**
 * Calcula e atualiza o nível de risco de um aluno baseado na pontuação
 */
function calcularNivelRisco($pontuacao_risco) {
    if ($pontuacao_risco >= 25) {
        return 'alto';
    } elseif ($pontuacao_risco >= 10) {
        return 'medio';
    } else {
        return 'baixo';
    }
}

/**
 * Atualiza o nível de risco de todos os alunos
 */
function atualizarNiveisRisco($conexao) {
    $stmt = $conexao->prepare("
        UPDATE alunos 
        SET nivel_risco = CASE 
            WHEN pontuacao_risco >= 25 THEN 'alto'
            WHEN pontuacao_risco >= 10 THEN 'medio'
            ELSE 'baixo'
        END
    ");
    return $stmt->execute();
}

/**
 * Adiciona pontos de risco a um aluno e cria um alerta
 */
function adicionarAlerta($conexao, $aluno_id, $regra_id, $pontos, $observacoes = '') {
    try {
        $conexao->begin_transaction();
        
        $stmt = $conexao->prepare("
            INSERT INTO alertas (aluno_id, regra_id, pontos_atribuidos, observacoes) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $aluno_id, $regra_id, $pontos, $observacoes);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conexao->prepare("
            UPDATE alunos 
            SET pontuacao_risco = pontuacao_risco + ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $pontos, $aluno_id);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conexao->prepare("SELECT pontuacao_risco FROM alunos WHERE id = ?");
        $stmt->bind_param("i", $aluno_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $nova_pontuacao = $result->fetch_assoc()['pontuacao_risco'];
        $stmt->close();
        
        $novo_nivel = calcularNivelRisco($nova_pontuacao);
        $stmt = $conexao->prepare("UPDATE alunos SET nivel_risco = ? WHERE id = ?");
        $stmt->bind_param("si", $novo_nivel, $aluno_id);
        $stmt->execute();
        $stmt->close();
        
        $conexao->commit();
        return true;
        
    } catch (Exception $e) {
        $conexao->rollback();
        return false;
    }
}

/**
 * Busca alunos por nível de risco
 */
function buscarAlunosPorRisco($conexao, $nivel_risco) {
    $stmt = $conexao->prepare("
        SELECT id, nome_completo, ra_matricula, turma, pontuacao_risco, nivel_risco 
        FROM alunos 
        WHERE nivel_risco = ? AND status = 'ativo'
        ORDER BY pontuacao_risco DESC
    ");
    $stmt->bind_param("s", $nivel_risco);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alunos = [];
    while ($row = $result->fetch_assoc()) {
        $alunos[] = $row;
    }
    
    $stmt->close();
    return $alunos;
}

/**
 * Gera relatório de estatísticas do sistema
 */
function obterEstatisticas($conexao) {
    $stats = [];
    
    $stmt = $conexao->prepare("
        SELECT nivel_risco, COUNT(*) as total 
        FROM alunos 
        WHERE status = 'ativo' 
        GROUP BY nivel_risco
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stats['alunos_' . $row['nivel_risco']] = $row['total'];
    }
    $stmt->close();
    
    $stmt = $conexao->prepare("
        SELECT 
            COUNT(*) as total_alertas,
            COUNT(CASE WHEN DATE(data_alerta) = CURDATE() THEN 1 END) as alertas_hoje,
            COUNT(CASE WHEN data_alerta >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as alertas_semana
        FROM alertas
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $alertas_stats = $result->fetch_assoc();
    $stats = array_merge($stats, $alertas_stats);
    $stmt->close();
    
    return $stats;
}
?>