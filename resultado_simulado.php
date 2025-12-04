<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$tentativa_id = $_GET['tentativa'] ?? 0;

$page_title = 'Resultado do Simulado';
include 'includes/header.php';
?>

<style>
.resultado-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
}

.stat-card {
    text-align: center;
    padding: 20px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.questao-review {
    border-left: 4px solid #e0e0e0;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    background: #f8f9fa;
}

.questao-review.correta {
    border-left-color: #28a745;
    background: #d4edda;
}

.questao-review.incorreta {
    border-left-color: #dc3545;
    background: #f8d7da;
}

.analise-resumo {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
}
</style>

<div class="container py-4">
    <div id="loadingArea">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Carregando resultado...</p>
        </div>
    </div>

    <div id="resultadoArea" style="display: none;">
        <!-- Header com estatísticas gerais -->
        <div class="resultado-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2" id="tituloSimulado"></h2>
                    <p class="mb-0 opacity-75" id="descricaoSimulado"></p>
                </div>
                <div class="col-md-4 text-end">
                    <h1 class="display-3 mb-0" id="notaFinal"></h1>
                    <small>Nota Final</small>
                </div>
            </div>
        </div>

        <!-- Cards de estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-success text-white">
                    <h3 id="totalCorretas">0</h3>
                    <small>Acertos</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-danger text-white">
                    <h3 id="totalErradas">0</h3>
                    <small>Erros</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white">
                    <h3 id="totalQuestoes">0</h3>
                    <small>Total de Questões</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white">
                    <h3 id="tempoTotal">-</h3>
                    <small>Tempo Total</small>
                </div>
            </div>
        </div>

        <!-- Análise geral -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Análise de Desempenho</h5>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height: 30px;">
                    <div class="progress-bar bg-success" id="progressCorretas" style="width: 0%"></div>
                </div>
                <div id="analiseGeral"></div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-primary active" onclick="filtrarQuestoes('todas')">
                        Todas
                    </button>
                    <button class="btn btn-outline-success" onclick="filtrarQuestoes('corretas')">
                        Apenas Corretas
                    </button>
                    <button class="btn btn-outline-danger" onclick="filtrarQuestoes('incorretas')">
                        Apenas Incorretas
                    </button>
                </div>
            </div>
        </div>

        <!-- Revisão das questões -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Revisão Detalhada</h5>
            </div>
            <div class="card-body" id="revisaoQuestoes">
                <!-- Carregado via JavaScript -->
            </div>
        </div>

        <!-- Botões de ação -->
        <div class="text-center mt-4">
            <button class="btn btn-primary btn-lg" onclick="window.location.href='simulados.php'">
                <i class="bi bi-arrow-left"></i> Voltar aos Simulados
            </button>
            <button class="btn btn-outline-primary btn-lg" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir Resultado
            </button>
        </div>
    </div>
</div>

<script>
const tentativaId = <?php echo $tentativa_id; ?>;
let resultado = null;
let filtroAtual = 'todas';

document.addEventListener('DOMContentLoaded', function() {
    carregarResultado();
});

async function carregarResultado() {
    try {
        const response = await fetch(`api/simulados.php?action=resultado&tentativa_id=${tentativaId}`);
        resultado = await response.json();

        if (resultado.error) {
            alert('Erro: ' + resultado.error);
            window.location.href = 'simulados.php';
            return;
        }

        exibirResultado();

    } catch (error) {
        console.error('Erro ao carregar resultado:', error);
        alert('Erro ao carregar resultado');
    }
}

function exibirResultado() {
    // Header
    document.getElementById('tituloSimulado').textContent = resultado.titulo;
    document.getElementById('descricaoSimulado').textContent = resultado.descricao || '';
    document.getElementById('notaFinal').textContent = resultado.nota.toFixed(1) + '%';

    // Estatísticas
    const corretas = resultado.questoes_corretas;
    const total = resultado.questoes_totais;
    const erradas = total - corretas;

    document.getElementById('totalCorretas').textContent = corretas;
    document.getElementById('totalErradas').textContent = erradas;
    document.getElementById('totalQuestoes').textContent = total;

    // Tempo
    if (resultado.data_inicio && resultado.data_fim) {
        const inicio = new Date(resultado.data_inicio);
        const fim = new Date(resultado.data_fim);
        const diffMin = Math.floor((fim - inicio) / 1000 / 60);
        document.getElementById('tempoTotal').textContent = diffMin + ' min';
    }

    // Progress bar
    const percentCorretas = (corretas / total) * 100;
    document.getElementById('progressCorretas').style.width = percentCorretas + '%';
    document.getElementById('progressCorretas').textContent = percentCorretas.toFixed(1) + '%';

    // Análise geral
    let analise = '';
    if (resultado.nota >= 70) {
        analise = '<div class="alert alert-success"><strong>Parabéns!</strong> Excelente desempenho. Continue assim!</div>';
    } else if (resultado.nota >= 50) {
        analise = '<div class="alert alert-warning"><strong>Bom trabalho!</strong> Você está no caminho certo. Revise os erros e tente novamente.</div>';
    } else {
        analise = '<div class="alert alert-danger"><strong>Precisa melhorar.</strong> Revise o conteúdo e pratique mais. Não desista!</div>';
    }
    document.getElementById('analiseGeral').innerHTML = analise;

    // Carregar questões
    exibirQuestoes();

    // Mostrar resultado
    document.getElementById('loadingArea').style.display = 'none';
    document.getElementById('resultadoArea').style.display = 'block';
}

function exibirQuestoes() {
    const container = document.getElementById('revisaoQuestoes');
    container.innerHTML = '';

    resultado.respostas.forEach((resp, index) => {
        if (filtroAtual === 'corretas' && !resp.correta) return;
        if (filtroAtual === 'incorretas' && resp.correta) return;

        const div = document.createElement('div');
        div.className = `questao-review ${resp.correta ? 'correta' : 'incorreta'}`;
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h5>
                    <span class="badge ${resp.correta ? 'bg-success' : 'bg-danger'}">
                        ${resp.correta ? '✓ Correta' : '✗ Incorreta'}
                    </span>
                    Questão ${resp.numero_questao}
                </h5>
                ${resp.tempo_resposta ? `<small class="text-muted"><i class="bi bi-clock"></i> ${resp.tempo_resposta}s</small>` : ''}
            </div>

            <p class="mb-3"><strong>${resp.enunciado}</strong></p>

            <div class="row">
                <div class="col-md-6">
                    ${criarAlternativaRevisao('A', resp.alternativa_a, resp)}
                    ${criarAlternativaRevisao('B', resp.alternativa_b, resp)}
                    ${criarAlternativaRevisao('C', resp.alternativa_c, resp)}
                </div>
                <div class="col-md-6">
                    ${criarAlternativaRevisao('D', resp.alternativa_d, resp)}
                    ${resp.alternativa_e ? criarAlternativaRevisao('E', resp.alternativa_e, resp) : ''}
                </div>
            </div>

            ${resp.explicacao ? `
                <div class="alert alert-info mt-3 mb-2">
                    <strong><i class="bi bi-info-circle"></i> Explicação:</strong><br>
                    ${resp.explicacao}
                </div>
            ` : ''}

            ${!resp.correta && resp.analise_ia ? `
                <div class="analise-resumo">
                    <strong><i class="bi bi-lightbulb"></i> Análise do Professor:</strong><br>
                    <div style="white-space: pre-wrap;" class="mt-2">${resp.analise_ia}</div>
                </div>
            ` : ''}
        `;

        container.appendChild(div);
    });

    if (container.innerHTML === '') {
        container.innerHTML = '<p class="text-center text-muted">Nenhuma questão encontrada com este filtro.</p>';
    }
}

function criarAlternativaRevisao(letra, texto, resp) {
    let classes = 'p-2 mb-1 rounded';
    let icone = '';

    if (letra === resp.resposta_correta) {
        classes += ' bg-success bg-opacity-25 border border-success';
        icone = ' <i class="bi bi-check-circle-fill text-success"></i>';
    } else if (letra === resp.resposta_usuario) {
        classes += ' bg-danger bg-opacity-25 border border-danger';
        icone = ' <i class="bi bi-x-circle-fill text-danger"></i>';
    }

    return `<div class="${classes}"><strong>${letra})</strong> ${texto}${icone}</div>`;
}

function filtrarQuestoes(tipo) {
    filtroAtual = tipo;

    // Atualizar botões
    document.querySelectorAll('.btn-group button').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    exibirQuestoes();
}
</script>

<?php include 'includes/footer.php'; ?>
