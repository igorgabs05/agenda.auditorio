// assets/js/minhas-reservas.js - JavaScript para página de reservas do usuário

document.addEventListener('DOMContentLoaded', function() {
    // Carregar notificações
    loadNotifications();
    
    // Configurar formulário de solicitação
    setupSolicitarForm();
    
    // Atualizar notificações a cada 30 segundos
    setInterval(loadNotifications, 30000);
});

// Carregar notificações
function loadNotifications() {
    fetch('api/notificacoes.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationsUI(data.notifications);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar notificações:', error);
        });
}

// Atualizar interface de notificações
function updateNotificationsUI(notifications) {
    const container = document.getElementById('notificacoes-lista');
    
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = '<li><span class="dropdown-item-text text-muted">Nenhuma notificação</span></li>';
        return;
    }
    
    container.innerHTML = notifications.map(notif => `
        <li>
            <a class="dropdown-item ${notif.lida ? '' : 'fw-bold'}" href="#" onclick="markAsRead(${notif.id})">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-bold">${notif.titulo}</div>
                        <small class="text-muted">${notif.mensagem}</small>
                    </div>
                    <small class="text-muted">${formatDate(notif.data_criacao)}</small>
                </div>
            </a>
        </li>
    `).join('');
}

// Marcar notificação como lida
function markAsRead(notifId) {
    fetch('api/notificacoes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_read',
            id: notifId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications(); // Recarregar notificações
        }
    });
}

// Configurar formulário de solicitação
function setupSolicitarForm() {
    const form = document.getElementById('formSolicitar');
    
    if (form) {
        // Carregar horários ocupados quando a data mudar
        const dataInput = document.getElementById('data_reserva');
        if (dataInput) {
            dataInput.addEventListener('change', function() {
                if (this.value) {
                    loadHorariosOcupados(this.value);
                }
            });
        }
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            // Validações básicas
            if (!validateReservationData(data)) {
                return;
            }
            
            // Mostrar loading
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
            submitBtn.disabled = true;
            
            // Enviar solicitação
            fetch('api/reservas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create',
                    ...data
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Solicitação enviada com sucesso!');
                    bootstrap.Modal.getInstance(document.getElementById('modalSolicitar')).hide();
                    form.reset();
                    
                    // Recarregar página para mostrar nova reserva
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', data.message || 'Erro ao enviar solicitação');
                }
            })
            .catch(error => {
                showAlert('danger', 'Erro ao enviar solicitação');
            })
            .finally(() => {
                // Restaurar botão
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
}

// Ver detalhes da reserva
function verDetalhes(reservaId) {
    fetch(`api/reservas.php?action=get&id=${reservaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showReservaDetails(data.reserva);
            } else {
                showAlert('danger', 'Erro ao carregar detalhes da reserva');
            }
        })
        .catch(error => {
            showAlert('danger', 'Erro ao carregar detalhes da reserva');
        });
}

// Mostrar detalhes da reserva
function showReservaDetails(reserva) {
    const modal = new bootstrap.Modal(document.createElement('div'));
    modal._element.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Data:</strong> ${new Date(reserva.data_reserva).toLocaleDateString('pt-BR')}</p>
                            <p><strong>Horário:</strong> ${reserva.hora_inicio} - ${reserva.hora_fim}</p>
                            <p><strong>Status:</strong> <span class="badge badge-${reserva.status.toLowerCase()}">${reserva.status}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Solicitado em:</strong> ${new Date(reserva.data_solicitacao).toLocaleString('pt-BR')}</p>
                            ${reserva.data_aprovacao ? `<p><strong>Aprovado em:</strong> ${new Date(reserva.data_aprovacao).toLocaleString('pt-BR')}</p>` : ''}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Motivo:</strong></p>
                            <p class="border p-3 rounded">${reserva.motivo}</p>
                            ${reserva.observacoes ? `
                                <p><strong>Observações:</strong></p>
                                <p class="border p-3 rounded">${reserva.observacoes}</p>
                            ` : ''}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal._element);
    modal.show();
    
    modal._element.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal._element);
    });
}

// Cancelar reserva
function cancelarReserva(reservaId) {
    if (confirm('Tem certeza que deseja cancelar esta reserva?')) {
        fetch('api/reservas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cancel',
                id: reservaId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Reserva cancelada com sucesso!');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert('danger', data.message || 'Erro ao cancelar reserva');
            }
        })
        .catch(error => {
            showAlert('danger', 'Erro ao cancelar reserva');
        });
    }
}

// Validar dados da reserva
function validateReservationData(data) {
    const hoje = new Date().toISOString().split('T')[0];
    
    if (data.data_reserva < hoje) {
        showAlert('warning', 'A data deve ser hoje ou no futuro');
        return false;
    }
    
    if (data.hora_inicio >= data.hora_fim) {
        showAlert('warning', 'A hora de fim deve ser posterior à hora de início');
        return false;
    }
    
    return true;
}

// Mostrar alerta
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Carregar horários ocupados para uma data
function loadHorariosOcupados(data) {
    fetch(`api/horarios.php?data=${data}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showHorariosOcupados(data.horarios);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar horários:', error);
        });
}

// Mostrar horários ocupados no modal
function showHorariosOcupados(horarios) {
    const container = document.getElementById('horarios-ocupados');
    if (!container) return;
    
    if (horarios.length === 0) {
        container.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Nenhum horário ocupado nesta data</div>';
        return;
    }
    
    let html = '<div class="alert alert-warning"><i class="fas fa-clock me-2"></i><strong>Horários já reservados:</strong></div>';
    html += '<div class="list-group">';
    
    horarios.forEach(horario => {
        html += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>${horario.hora_inicio} - ${horario.hora_fim}</strong>
                        <br><small class="text-muted">${horario.motivo}</small>
                    </div>
                    <small class="text-muted">${horario.usuario_nome}</small>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
