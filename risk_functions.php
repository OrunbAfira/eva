<?php

function recalcularPontuacaoAluno($conexao, $aluno_id) {
    $stmt = $conexao->prepare("
        SELECT COALESCE(SUM(al.pontos_atribuidos), 0) as total
        FROM alertas al
        JOIN regras_risco r ON al.regra_id = r.id
        WHERE al.aluno_id = ? AND r.ativo = 1
    ");
    $stmt->bind_param("i", $aluno_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nova_pontuacao = $row['total'];
    $stmt->close();
    
    if ($nova_pontuacao >= 25) {
        $nivel_risco = 'alto';
    } elseif ($nova_pontuacao >= 10) {
        $nivel_risco = 'medio';
    } else {
        $nivel_risco = 'baixo';
    }
    
    $stmt = $conexao->prepare("
        UPDATE alunos 
        SET pontuacao_risco = ?, nivel_risco = ?
        WHERE id = ?
    ");
    $stmt->bind_param("isi", $nova_pontuacao, $nivel_risco, $aluno_id);
    $sucesso = $stmt->execute();
    $stmt->close();
    
    return $sucesso;
}

function recalcularAlunosPorRegra($conexao, $regra_id) {
    $stmt = $conexao->prepare("
        SELECT DISTINCT aluno_id 
        FROM alertas 
        WHERE regra_id = ?
    ");
    $stmt->bind_param("i", $regra_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alunos_atualizados = 0;
    while ($row = $result->fetch_assoc()) {
        if (recalcularPontuacaoAluno($conexao, $row['aluno_id'])) {
            $alunos_atualizados++;
        }
    }
    $stmt->close();
    
    return $alunos_atualizados;
}

function recalcularTodasPontuacoes($conexao) {
    $stmt = $conexao->prepare("SELECT id FROM alunos WHERE status = 'ativo'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alunos_atualizados = 0;
    while ($row = $result->fetch_assoc()) {
        if (recalcularPontuacaoAluno($conexao, $row['id'])) {
            $alunos_atualizados++;
        }
    }
    $stmt->close();
    
    return $alunos_atualizados;
}
