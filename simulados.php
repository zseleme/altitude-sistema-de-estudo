<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Simulados';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h2>Simulados Disponíveis</h2>
        <p class="text-muted">Teste seus conhecimentos e melhore seu desempenho</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Simulados Realizados</h5>
                    <h2 id="totalRealizados">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Média Geral</h5>
                    <h2 id="mediaGeral">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Disponíveis</h5>
                    <h2 id="totalDisponiveis">-</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="listaSimulados">
        <!-- Carregado via JavaScript -->
    </div>
</div>

<script>
let simulados = [];

document.addEventListener('DOMContentLoaded', function() {
    carregarSimulados();
});

async function carregarSimulados() {
    try {
        const response = await fetch('api/simulados.php?action=listar');
        simulados = await response.json();

        const container = document.getElementById('listaSimulados');
        container.innerHTML = '';

        let totalRealizados = 0;
        let somaNotas = 0;
        let countNotas = 0;

        simulados.forEach(sim => {
            if (sim.tentativas > 0) {
                totalRealizados++;
            }

            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-4 mb-4';
            card.innerHTML = `
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">${sim.titulo}</h5>
                        <p class="card-text text-muted small">${sim.descricao || 'Sem descrição'}</p>

                        <div class="mb-3">
                            ${sim.disciplina ? `<span class="badge bg-primary">${sim.disciplina}</span>` : ''}
                            <span class="badge bg-secondary">${sim.total_questoes} questões</span>
                            ${sim.tempo_limite > 0 ? `<span class="badge bg-warning text-dark">${sim.tempo_limite} min</span>` : ''}
                        </div>

                        ${sim.tentativas > 0 ? `
                            <div class="alert alert-info mb-3 py-2">
                                <small><i class="bi bi-clock-history"></i> Você já realizou ${sim.tentativas}x</small>
                            </div>
                        ` : ''}

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="iniciarSimulado(${sim.id})">
                                <i class="bi bi-play-circle"></i> ${sim.tentativas > 0 ? 'Refazer' : 'Iniciar'} Simulado
                            </button>
                            ${sim.tentativas > 0 ? `
                                <button class="btn btn-outline-secondary btn-sm" onclick="verResultados(${sim.id})">
                                    <i class="bi bi-bar-chart"></i> Ver Resultados
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="card-footer text-muted small">
                        Criado em ${new Date(sim.created_at).toLocaleDateString('pt-BR')}
                    </div>
                </div>
            `;
            container.appendChild(card);
        });

        // Atualizar estatísticas
        document.getElementById('totalRealizados').textContent = totalRealizados;
        document.getElementById('totalDisponiveis').textContent = simulados.length;
        document.getElementById('mediaGeral').textContent = countNotas > 0 ?
            (somaNotas / countNotas).toFixed(1) + '%' : '-';

    } catch (error) {
        console.error('Erro ao carregar simulados:', error);
        alert('Erro ao carregar simulados');
    }
}

async function iniciarSimulado(simuladoId) {
    try {
        const formData = new FormData();
        formData.append('simulado_id', simuladoId);

        const response = await fetch('api/simulados.php?action=iniciar', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = `realizar_simulado.php?id=${simuladoId}&tentativa=${result.tentativa_id}`;
        } else {
            alert('Erro ao iniciar simulado: ' + result.error);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao iniciar simulado');
    }
}

function verResultados(simuladoId) {
    window.location.href = `historico_simulados.php?simulado_id=${simuladoId}`;
}
</script>

<?php include 'includes/footer.php'; ?>
