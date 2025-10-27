// assets/js/main.js - JavaScript principal do sistema

document.addEventListener('DOMContentLoaded', async function() {
    // Carregar bloqueios customizados antes de inicializar
    await loadBloqueiosCustomizados();
    
    // Inicializar calendário
    initCalendar();
    
    // Carregar notificações
    loadNotifications();
    
    // Configurar formulário de solicitação
    setupSolicitarForm();
    
    // Atualizar notificações a cada 30 segundos
    setInterval(loadNotifications, 30000);
    
    // Atualizar bloqueios a cada 2 minutos
    setInterval(() => loadBloqueiosCustomizados(), 120000);
});

// Inicializar FullCalendar
function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    
    if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pt-br',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'Hoje',
                month: 'Mês',
                week: 'Semana',
                day: 'Dia'
            },
            viewDidMount: function(view) {
                // Recarregar bloqueios quando a visualização muda
                console.log('View mounted:', view.type);
                
                // Verificar se view.currentStart existe antes de acessar
                if (view && view.currentStart) {
                    const ano = view.currentStart.getFullYear();
                    loadBloqueiosCustomizados(ano, true);
                } else {
                    // Se não tiver currentStart, usar ano atual
                    const ano = new Date().getFullYear();
                    loadBloqueiosCustomizados(ano, true);
                }
            },
            datesSet: function(dateInfo) {
                // Recarregar bloqueios quando o intervalo de datas muda
                console.log('Dates set:', dateInfo.startStr, 'to', dateInfo.endStr);
                
                // Verificar se dateInfo.start existe antes de acessar
                if (dateInfo && dateInfo.start) {
                    const ano = dateInfo.start.getFullYear();
                    loadBloqueiosCustomizados(ano, true);
                } else {
                    // Se não tiver start, usar ano atual
                    const ano = new Date().getFullYear();
                    loadBloqueiosCustomizados(ano, true);
                }
            },
            events: function(info, successCallback, failureCallback) {
                // Carregar eventos via AJAX
                fetch('api/calendario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        start: info.startStr,
                        end: info.endStr
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        successCallback(data.events);
                    } else {
                        failureCallback(data.message);
                    }
                })
                .catch(error => {
                    failureCallback('Erro ao carregar eventos');
                });
            },
            eventClick: function(info) {
                showEventDetails(info.event);
            },
            dateClick: async function(info) {
                // Recarregar bloqueios para garantir dados atualizados
                await loadBloqueiosCustomizados(info.date.getFullYear());
                
                // Verificar se o dia está disponível (versão assíncrona)
                const disponibilidade = await isDiaDisponivelAsync(info.date);
                
                if (!disponibilidade.disponivel) {
                    showWarning(disponibilidade.motivo, 'Data indisponível');
                    return;
                }
                
                // Se for usuário comum, permitir clicar para solicitar reserva
                const modalSolicitar = document.getElementById('modalSolicitar');
                if (modalSolicitar) {
                    document.getElementById('data_reserva').value = info.dateStr;
                    new bootstrap.Modal(modalSolicitar).show();
                } else {
                    // Para administradores, mostrar informação sobre o dia
                    showInfo(`Dia disponível: ${info.dateStr}\n\nAdministradores não podem fazer reservas diretamente. Use o Painel Admin para gerenciar reservas.`, 'Dia Disponível');
                }
            },
            eventDidMount: function(info) {
                // Adicionar tooltip com informações do evento
                info.el.setAttribute('title', 
                    `${info.event.title}\n${info.event.extendedProps.usuario_nome}\n${info.event.extendedProps.motivo}`
                );
                
                // Adicionar classe baseada no status
                if (info.event.extendedProps.status) {
                    info.el.classList.add(info.event.extendedProps.status.toLowerCase());
                }
            },
            dayCellDidMount: function(info) {
                // Verificar se temos o elemento necessário (apenas em dayGrid view)
                const dayTop = info.el.querySelector('.fc-daygrid-day-top');
                if (!dayTop) return; // Se não existir, pular (timeGrid views)
                
                // Verificar bloqueio customizado (usa cache já carregado)
                const bloqueioCustom = getBloqueioCustomizado(info.date);
                
                if (bloqueioCustom) {
                    // Tem customização do admin
                    if (bloqueioCustom.bloqueado == 1) {
                        // Bloqueado
                        info.el.classList.add('fc-day-disabled');
                        info.el.style.cursor = 'not-allowed';
                        
                        const badge = document.createElement('span');
                        badge.className = 'feriado-badge';
                        badge.textContent = '❌ Bloqueado';
                        badge.title = bloqueioCustom.descricao || 'Bloqueado pelo administrador';
                        dayTop.appendChild(badge);
                    } else {
                        // Desbloqueado pelo admin - garantir que o dia esteja disponível
                        // Remover todas as classes de bloqueio
                        info.el.classList.remove('fc-day-disabled');
                        info.el.classList.remove('feriado');
                        info.el.style.cursor = 'pointer';
                        info.el.style.backgroundColor = ''; // Limpar cor de fundo de feriado
                        
                        const badge = document.createElement('span');
                        badge.className = 'feriado-badge';
                        badge.style.backgroundColor = '#28a745';
                        badge.textContent = '✔ Liberado';
                        badge.title = 'Desbloqueado pelo administrador';
                        dayTop.appendChild(badge);
                    }
                } else {
                    // Comportamento padrão (sem customização)
                    // Marcar domingos
                    if (isDomingo(info.date)) {
                        info.el.classList.add('fc-day-disabled');
                        info.el.style.cursor = 'not-allowed';
                    }
                    
                    // Marcar feriados
                    const feriado = isFeriado(info.date);
                    if (feriado) {
                        info.el.classList.add('feriado');
                        info.el.style.cursor = 'not-allowed';
                        
                        // Adicionar badge de feriado
                        const badge = document.createElement('span');
                        badge.className = 'feriado-badge';
                        badge.textContent = feriado.nome;
                        badge.title = feriado.nome;
                        dayTop.appendChild(badge);
                    }
                }
            }
        });
        
        calendar.render();
        window.calendar = calendar; // Armazenar instância globalmente
        
        // Garantir que os botões funcionem após renderizar
        setTimeout(() => {
            const buttons = document.querySelectorAll('.fc-button');
            console.log('Encontrados', buttons.length, 'botões no calendário');
            
            buttons.forEach((btn, index) => {
                btn.style.pointerEvents = 'auto';
                btn.style.cursor = 'pointer';
                btn.style.zIndex = '1';
                btn.style.position = 'relative';
                
                // Verificar se o botão está funcionando
                console.log(`Botão ${index}: ${btn.className} - cliqueable`);
            });
            
            console.log('Botões do calendário configurados:', buttons.length);
        }, 200);
    }
}

// Carregar notificações
function loadNotifications() {
    // Buscar lista de notificações
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
    
    // Buscar contador de não lidas
    fetch('api/notificacoes.php?action=count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount(data.count);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar contador de notificações:', error);
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

// Atualizar contador de notificações
function updateNotificationCount(count) {
    const badge = document.querySelector('.notification-badge');
    if (!badge) return;
    
    // Remover contador existente
    const existingCount = badge.querySelector('.notification-count');
    if (existingCount) {
        existingCount.remove();
    }
    
    // Adicionar novo contador se houver notificações não lidas
    if (count > 0) {
        const countSpan = document.createElement('span');
        countSpan.className = 'notification-count';
        countSpan.textContent = count;
        badge.appendChild(countSpan);
        
        // Adicionar animação de pulse se for nova notificação
        badge.classList.add('has-notifications');
    } else {
        badge.classList.remove('has-notifications');
    }
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
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showAlert('success', 'Solicitação enviada com sucesso!');
                    bootstrap.Modal.getInstance(document.getElementById('modalSolicitar')).hide();
                    form.reset();
                    
                    // Recarregar calendário
                    if (window.calendar) {
                        window.calendar.refetchEvents();
                    }
                } else {
                    showAlert('danger', data.message || 'Erro ao enviar solicitação');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('danger', 'Erro ao enviar solicitação: ' + error.message);
            })
            .finally(() => {
                // Restaurar botão
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
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

// Mostrar detalhes do evento
function showEventDetails(event) {
    const modalElement = document.createElement('div');
    modalElement.className = 'modal fade';
    modalElement.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Usuário:</strong> ${event.extendedProps.usuario_nome || 'N/A'}</p>
                    <p><strong>Data:</strong> ${event.start ? event.start.toLocaleDateString('pt-BR') : 'N/A'}</p>
                    <p><strong>Horário:</strong> ${event.extendedProps.hora_inicio || 'N/A'} - ${event.extendedProps.hora_fim || 'N/A'}</p>
                    <p><strong>Motivo:</strong> ${event.extendedProps.motivo || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="badge badge-${event.extendedProps.status ? event.extendedProps.status.toLowerCase() : 'pendente'}">${event.extendedProps.status || 'PENDENTE'}</span></p>
                    ${event.extendedProps.observacoes ? `<p><strong>Observações:</strong> ${event.extendedProps.observacoes}</p>` : ''}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modalElement);
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    modalElement.addEventListener('hidden.bs.modal', function() {
        modal.dispose();
        document.body.removeChild(modalElement);
    });
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

// Função global para logout
function logout() {
    if (confirm('Tem certeza que deseja sair?')) {
        window.location.href = 'logout.php';
    }
}
