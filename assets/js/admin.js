// assets/js/admin.js - JavaScript para painel administrativo

document.addEventListener('DOMContentLoaded', async function() {
    // Carregar bloqueios customizados antes de inicializar
    await loadBloqueiosCustomizados();
    
    // Inicializar calendário admin
    initAdminCalendar();
    
    // Carregar notificações
    loadNotifications();
    
    // Configurar formulário de criação de reserva
    setupCriarReservaForm();
    
    // Configurar gerenciamento de usuários
    setupUsuariosManagement();
    
    // Configurar gerenciamento de bloqueios
    setupBloqueiosManagement();
    
    // Configurar estatísticas
    setupEstatisticas();
    
    // Configurar gerenciamento de todas as reservas
    setupGerenciarReservas();
    
    // Atualizar notificações a cada 30 segundos
    setInterval(loadNotifications, 30000);
    
    // Atualizar bloqueios a cada 2 minutos
    setInterval(() => loadBloqueiosCustomizados(), 120000);
    
    // Adicionar listener para quando a tab do calendário é ativada
    const calendarioTab = document.getElementById('calendario-tab');
    if (calendarioTab) {
        calendarioTab.addEventListener('shown.bs.tab', function() {
            if (window.adminCalendar) {
                // Forçar atualização do calendário quando a tab é mostrada
                setTimeout(() => {
                    window.adminCalendar.render();
                    
                    // Reforçar estilos dos botões e adicionar listeners
                    const buttons = document.querySelectorAll('.fc-button');
                    buttons.forEach(btn => {
                        btn.style.pointerEvents = 'auto';
                        btn.style.cursor = 'pointer';
                        btn.style.zIndex = '1';
                        btn.style.position = 'relative';
                        
                        // Adicionar listener de clique explícito
                        btn.addEventListener('click', function(e) {
                            console.log('Clique detectado no botão:', this.className);
                        });
                    });
                    
                    console.log('Calendário admin atualizado após mostrar tab');
                }, 100);
            }
        });
    }
});

// Inicializar calendário administrativo
function initAdminCalendar() {
    const calendarEl = document.getElementById('calendar-admin');
    
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
                console.log('Admin view mounted:', view.type);
                
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
                console.log('Admin dates set:', dateInfo.startStr, 'to', dateInfo.endStr);
                
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
                showEventDetailsAdmin(info.event);
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
                
                // Preencher data no modal de criação
                document.getElementById('data_reserva_admin').value = info.dateStr;
                new bootstrap.Modal(document.getElementById('modalCriarReserva')).show();
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
        window.adminCalendar = calendar;
        
        // Garantir que os botões funcionem após renderizar
        setTimeout(() => {
            const buttons = document.querySelectorAll('.fc-button');
            console.log('Encontrados', buttons.length, 'botões no calendário admin');
            
            buttons.forEach((btn, index) => {
                btn.style.pointerEvents = 'auto';
                btn.style.cursor = 'pointer';
                btn.style.zIndex = '1';
                btn.style.position = 'relative';
                
                // Verificar se o botão está funcionando
                console.log(`Botão ${index}: ${btn.className} - cliqueable`);
            });
            
            console.log('Botões do calendário admin configurados:', buttons.length);
        }, 200);
    }
}

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

// Configurar formulário de criação de reserva
function setupCriarReservaForm() {
    const form = document.getElementById('formCriarReserva');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            // Validações básicas
            if (!validateReservationData(data)) {
                return;
            }
            
            // Adicionar status aprovado para reservas criadas pelo admin
            data.status = 'APROVADO';
            
            // Enviar solicitação
            fetch('api/reservas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create_admin',
                    ...data
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Reserva criada com sucesso!');
                    bootstrap.Modal.getInstance(document.getElementById('modalCriarReserva')).hide();
                    form.reset();
                    
                    // Recarregar calendário
                    if (window.adminCalendar) {
                        window.adminCalendar.refetchEvents();
                    }
                    
                    // Recarregar página para atualizar dashboard
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', data.message || 'Erro ao criar reserva');
                }
            })
            .catch(error => {
                showAlert('danger', 'Erro ao criar reserva');
            });
        });
    }
}

// Aprovar reserva
function aprovarReserva(reservaId) {
    if (confirm('Tem certeza que deseja aprovar esta reserva?')) {
        updateReservaStatus(reservaId, 'APROVADO');
    }
}

// Recusar reserva
function recusarReserva(reservaId) {
    const motivo = prompt('Por favor, informe o motivo da recusa:');
    if (motivo !== null) { // Usuário não cancelou
        if (!motivo || motivo.trim() === '') {
            showAlert('warning', 'Por favor, informe um motivo para a recusa');
            return;
        }
        updateReservaStatus(reservaId, 'RECUSADO', motivo);
    }
}

// Atualizar status da reserva
function updateReservaStatus(reservaId, status, observacoes = '') {
    fetch('api/reservas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_status',
            id: reservaId,
            status: status,
            observacoes: observacoes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Reserva ${status.toLowerCase()} com sucesso!`);
            
            // Recarregar calendário
            if (window.adminCalendar) {
                window.adminCalendar.refetchEvents();
            }
            
            // Recarregar página para atualizar dashboard
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Erro ao atualizar reserva');
        }
    })
    .catch(error => {
        showAlert('danger', 'Erro ao atualizar reserva');
    });
}

// Cancelar reserva
function cancelarReserva(reservaId) {
    const motivo = prompt('Por favor, informe o motivo do cancelamento (opcional):');
    if (motivo !== null) { // Usuário não cancelou o prompt
        updateReservaStatus(reservaId, 'CANCELADO', motivo || 'Cancelado pelo administrador');
    }
}

// Excluir reserva definitivamente
function excluirReserva(reservaId) {
    // Confirmar com dupla verificação para excluir
    if (confirm('ATENÇÃO: Esta ação irá EXCLUIR PERMANENTEMENTE a reserva do sistema. Esta ação não pode ser desfeita. Deseja continuar?')) {
        if (confirm('Tem certeza absoluta? A reserva será removida definitivamente.')) {
            fetch('api/reservas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: reservaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Reserva excluída permanentemente com sucesso!');
                    
                    // Recarregar calendário
                    if (window.adminCalendar) {
                        window.adminCalendar.refetchEvents();
                    }
                    
                    // Recarregar página para atualizar dashboard
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', data.message || 'Erro ao excluir reserva');
                }
            })
            .catch(error => {
                showAlert('danger', 'Erro ao excluir reserva');
            });
        }
    }
}

// Mostrar detalhes do evento (versão admin)
function showEventDetailsAdmin(event) {
    const modalElement = document.createElement('div');
    modalElement.className = 'modal fade';
    modalElement.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Usuário:</strong> ${event.extendedProps.usuario_nome || 'N/A'}</p>
                            <p><strong>Data:</strong> ${event.start ? event.start.toLocaleDateString('pt-BR') : 'N/A'}</p>
                            <p><strong>Horário:</strong> ${event.extendedProps.hora_inicio || 'N/A'} - ${event.extendedProps.hora_fim || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> <span class="badge badge-${event.extendedProps.status ? event.extendedProps.status.toLowerCase() : 'pendente'}">${event.extendedProps.status || 'PENDENTE'}</span></p>
                            <p><strong>Motivo:</strong> ${event.extendedProps.motivo || 'N/A'}</p>
                            ${event.extendedProps.observacoes ? `<p><strong>Observações:</strong> ${event.extendedProps.observacoes}</p>` : ''}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    ${event.extendedProps.status === 'PENDENTE' ? `
                        <button type="button" class="btn btn-success" onclick="aprovarReserva('${event.id}'); bootstrap.Modal.getInstance(this.closest('.modal')).hide();">
                            <i class="fas fa-check"></i> Aprovar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="recusarReserva('${event.id}'); bootstrap.Modal.getInstance(this.closest('.modal')).hide();">
                            <i class="fas fa-times"></i> Recusar
                        </button>
                    ` : ''}
                    ${event.extendedProps.status === 'APROVADO' ? `
                        <button type="button" class="btn btn-warning" onclick="cancelarReserva('${event.id}'); bootstrap.Modal.getInstance(this.closest('.modal')).hide();">
                            <i class="fas fa-ban"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="excluirReserva('${event.id}'); bootstrap.Modal.getInstance(this.closest('.modal')).hide();">
                            <i class="fas fa-trash"></i> Excluir Definitivamente
                        </button>
                    ` : ''}
                    ${event.extendedProps.status !== 'APROVADO' && event.extendedProps.status !== 'PENDENTE' ? `
                        <button type="button" class="btn btn-danger" onclick="excluirReserva('${event.id}'); bootstrap.Modal.getInstance(this.closest('.modal')).hide();">
                            <i class="fas fa-trash"></i> Excluir Definitivamente
                        </button>
                    ` : ''}
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
    
    if (!data.usuario_id) {
        showAlert('warning', 'Selecione um usuário');
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

// ============ FUNÇÕES DE GERENCIAMENTO DE TODAS AS RESERVAS ============

function setupGerenciarReservas() {
    // Carregar reservas quando a aba for clicada
    const gerenciarReservasTab = document.getElementById('gerenciar-reservas-tab');
    if (gerenciarReservasTab) {
        gerenciarReservasTab.addEventListener('click', function() {
            carregarTodasReservas();
        });
    }
    
    // Configurar filtro de status
    const filtroStatus = document.getElementById('filtro-status-reservas');
    if (filtroStatus) {
        filtroStatus.addEventListener('change', function() {
            carregarTodasReservas();
        });
    }
}

// Carregar todas as reservas
function carregarTodasReservas() {
    const filtroStatus = document.getElementById('filtro-status-reservas');
    const statusFiltro = filtroStatus ? filtroStatus.value : '';
    
    // Buscar todas as reservas
    fetch('api/reservas.php?action=list_all' + (statusFiltro ? '&status=' + statusFiltro : ''))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTabelaTodasReservas(data.reservas);
            } else {
                showAlert('danger', data.message || 'Erro ao carregar reservas');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar reservas:', error);
            showAlert('danger', 'Erro ao carregar reservas');
        });
}

// Renderizar tabela de todas as reservas
function renderTabelaTodasReservas(reservas) {
    const tbody = document.querySelector('#tabelaTodasReservas tbody');
    if (!tbody) return;
    
    if (!reservas || reservas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Nenhuma reserva encontrada</td></tr>';
        return;
    }
    
    tbody.innerHTML = reservas.map(reserva => {
        const statusClass = {
            'PENDENTE': 'warning',
            'APROVADO': 'success',
            'RECUSADO': 'danger',
            'CANCELADO': 'secondary'
        }[reserva.status] || 'secondary';
        
        return `
            <tr>
                <td>${reserva.id}</td>
                <td>${reserva.usuario_nome || 'N/A'}</td>
                <td>${new Date(reserva.data_reserva).toLocaleDateString('pt-BR')}</td>
                <td>${reserva.hora_inicio} - ${reserva.hora_fim}</td>
                <td>${reserva.motivo || 'N/A'}</td>
                <td><span class="badge bg-${statusClass}">${reserva.status}</span></td>
                <td>${new Date(reserva.data_solicitacao).toLocaleDateString('pt-BR')}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        ${reserva.status === 'PENDENTE' ? `
                            <button class="btn btn-success btn-sm" onclick="aprovarReserva(${reserva.id})" title="Aprovar">
                                <i class="fas fa-check"></i> Aprovar
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="recusarReserva(${reserva.id})" title="Recusar">
                                <i class="fas fa-times"></i> Recusar
                            </button>
                        ` : ''}
                        ${reserva.status === 'APROVADO' ? `
                            <button class="btn btn-warning btn-sm" onclick="cancelarReserva(${reserva.id})" title="Cancelar Reserva">
                                <i class="fas fa-ban"></i> Cancelar
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="excluirReserva(${reserva.id})" title="Excluir definitivamente do sistema">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        ` : ''}
                        ${reserva.status !== 'APROVADO' && reserva.status !== 'PENDENTE' ? `
                            <button class="btn btn-danger btn-sm" onclick="excluirReserva(${reserva.id})" title="Excluir definitivamente do sistema">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// ============ FUNÇÕES DE GERENCIAMENTO DE USUÁRIOS ============

function setupUsuariosManagement() {
    // Carregar usuários quando a aba for clicada
    const usuariosTab = document.getElementById('usuarios-tab');
    if (usuariosTab) {
        usuariosTab.addEventListener('click', function() {
            loadUsuarios();
        });
    }
    
    // Configurar formulário de criar usuário
    const formCriarUsuario = document.getElementById('formCriarUsuario');
    if (formCriarUsuario) {
        formCriarUsuario.addEventListener('submit', function(e) {
            e.preventDefault();
            criarUsuario();
        });
    }
    
    // Configurar formulário de editar usuário
    const formEditarUsuario = document.getElementById('formEditarUsuario');
    if (formEditarUsuario) {
        formEditarUsuario.addEventListener('submit', function(e) {
            e.preventDefault();
            atualizarUsuario();
        });
    }
}

// Carregar lista de usuários
function loadUsuarios() {
    fetch('api/usuarios.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUsuariosTable(data.usuarios);
            } else {
                showAlert('danger', data.message || 'Erro ao carregar usuários');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar usuários:', error);
            showAlert('danger', 'Erro ao carregar usuários');
        });
}

// Renderizar tabela de usuários
function renderUsuariosTable(usuarios) {
    const tbody = document.querySelector('#tabelaUsuarios tbody');
    if (!tbody) return;
    
    if (usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhum usuário encontrado</td></tr>';
        return;
    }
    
    tbody.innerHTML = usuarios.map(usuario => `
        <tr>
            <td>${usuario.nome}</td>
            <td>${usuario.email}</td>
            <td>
                <span class="badge ${usuario.tipo === 'admin' ? 'bg-danger' : 'bg-info'}">
                    ${usuario.tipo === 'admin' ? 'Administrador' : 'Usuário'}
                </span>
            </td>
            <td>
                <span class="badge ${usuario.ativo == 1 ? 'bg-success' : 'bg-secondary'}">
                    ${usuario.ativo == 1 ? 'Ativo' : 'Inativo'}
                </span>
            </td>
            <td>${new Date(usuario.data_cadastro).toLocaleDateString('pt-BR')}</td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-primary" onclick="editarUsuario(${usuario.id})" title="Editar usuário">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmarDeletarUsuario(${usuario.id}, '${usuario.nome.replace(/'/g, "\\'")}')") title="Desativar usuário">
                        <i class="fas fa-user-slash"></i> Desativar
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Criar novo usuário
function criarUsuario() {
    const form = document.getElementById('formCriarUsuario');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Criando...';
    submitBtn.disabled = true;
    
    fetch('api/usuarios.php', {
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
            showAlert('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalCriarUsuario')).hide();
            form.reset();
            loadUsuarios();
        } else {
            showAlert('danger', data.message || 'Erro ao criar usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro ao criar usuário');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Editar usuário
function editarUsuario(userId) {
    fetch(`api/usuarios.php?action=get&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const usuario = data.usuario;
                document.getElementById('edit_usuario_id').value = usuario.id;
                document.getElementById('edit_nome_usuario').value = usuario.nome;
                document.getElementById('edit_email_usuario').value = usuario.email;
                document.getElementById('edit_tipo_usuario').value = usuario.tipo;
                document.getElementById('edit_ativo_usuario').value = usuario.ativo;
                
                new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
            } else {
                showAlert('danger', data.message || 'Erro ao carregar usuário');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('danger', 'Erro ao carregar usuário');
        });
}

// Atualizar usuário
function atualizarUsuario() {
    const form = document.getElementById('formEditarUsuario');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Remover senha se estiver vazia
    if (!data.senha) {
        delete data.senha;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
    submitBtn.disabled = true;
    
    fetch('api/usuarios.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            ...data
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('modalEditarUsuario')).hide();
            form.reset();
            loadUsuarios();
        } else {
            showAlert('danger', data.message || 'Erro ao atualizar usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro ao atualizar usuário');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Confirmar deleção de usuário
function confirmarDeletarUsuario(userId, userName) {
    if (confirm(`Tem certeza que deseja desativar o usuário "${userName}"?`)) {
        deletarUsuario(userId);
    }
}

// Deletar (desativar) usuário
function deletarUsuario(userId) {
    fetch('api/usuarios.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete',
            id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            loadUsuarios();
        } else {
            showAlert('danger', data.message || 'Erro ao desativar usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro ao desativar usuário');
    });
}

// ============ FUNÇÕES DE GERENCIAMENTO DE BLOQUEIOS ============

function setupBloqueiosManagement() {
    // Carregar bloqueios quando a aba for clicada
    const bloqueiosTab = document.getElementById('bloqueios-tab');
    if (bloqueiosTab) {
        bloqueiosTab.addEventListener('click', function() {
            loadBloqueios();
        });
    }
    
    // Configurar filtro de ano
    const anoFiltro = document.getElementById('ano_filtro');
    if (anoFiltro) {
        anoFiltro.addEventListener('change', function() {
            loadBloqueios(this.value);
        });
    }
    
    // Configurar formulário de desbloqueio
    const formDesbloquear = document.getElementById('formDesbloquear');
    if (formDesbloquear) {
        formDesbloquear.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = document.getElementById('data_desbloquear').value;
            const descricao = document.getElementById('descricao_desbloquear').value;
            toggleBloqueio(data, descricao);
        });
    }
    
    // Configurar formulário de bloqueio
    const formBloquear = document.getElementById('formBloquear');
    if (formBloquear) {
        formBloquear.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = document.getElementById('data_bloquear').value;
            const descricao = document.getElementById('descricao_bloquear').value;
            bloquearDia(data, descricao);
        });
    }
}

// Carregar lista de bloqueios
function loadBloqueios(ano = null) {
    if (!ano) {
        ano = document.getElementById('ano_filtro')?.value || new Date().getFullYear();
    }
    
    fetch(`api/dias_bloqueados.php?ano=${ano}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderBloqueiosTable(data.dias);
            } else {
                showAlert('danger', data.message || 'Erro ao carregar bloqueios');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar bloqueios:', error);
            showAlert('danger', 'Erro ao carregar bloqueios');
        });
}

// Renderizar tabela de bloqueios
function renderBloqueiosTable(dias) {
    const tbody = document.querySelector('#tabelaBloqueios tbody');
    if (!tbody) return;
    
    if (dias.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhuma customização de bloqueio encontrada para este ano</td></tr>';
        return;
    }
    
    tbody.innerHTML = dias.map(dia => {
        const dataFormatada = new Date(dia.data + 'T00:00:00').toLocaleDateString('pt-BR');
        const statusBadge = dia.bloqueado == 1 
            ? '<span class="badge bg-danger">Bloqueado</span>' 
            : '<span class="badge bg-success">Desbloqueado</span>';
        const dataModificacao = new Date(dia.data_modificacao).toLocaleString('pt-BR');
        
        return `
            <tr>
                <td>${dataFormatada}</td>
                <td><span class="badge bg-secondary">${dia.tipo}</span></td>
                <td>${dia.descricao || '-'}</td>
                <td>${statusBadge}</td>
                <td><small>${dataModificacao}</small></td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-warning" onclick="toggleBloqueio('${dia.data}', '${(dia.descricao || '').replace(/'/g, "\\'")}')") title="Alternar status">
                            <i class="fas fa-exchange-alt"></i> Alternar
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="removerCustomizacao('${dia.data}')" title="Voltar ao padrão">
                            <i class="fas fa-undo"></i> Resetar
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Alternar bloqueio (desbloquear feriado/domingo ou reverter)
function toggleBloqueio(data, descricao = '') {
    if (!data) {
        showAlert('warning', 'Por favor, selecione uma data');
        return;
    }
    
    // Determinar tipo baseado na data
    const dateObj = new Date(data + 'T00:00:00');
    const tipo = isDomingo(dateObj) ? 'domingo' : (isFeriado(dateObj) ? 'feriado' : 'outro');
    
    fetch('api/dias_bloqueados.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            data: data,
            tipo: tipo,
            descricao: descricao
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            loadBloqueios();
            
            // Limpar formulário
            const form = document.getElementById('formDesbloquear');
            if (form) form.reset();
            
            // Forçar reload do cache de bloqueios
            loadBloqueiosCustomizados(null, true).then(() => {
                // Recarregar calendário se estiver visível
                if (window.adminCalendar) {
                    window.adminCalendar.refetchEvents();
                    // Forçar re-renderização dos dias
                    window.adminCalendar.render();
                }
            });
        } else {
            showAlert('danger', data.message || 'Erro ao alternar bloqueio');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro ao alternar bloqueio');
    });
}

// Bloquear dia específico
function bloquearDia(data, descricao = '') {
    if (!data) {
        showAlert('warning', 'Por favor, selecione uma data');
        return;
    }
    
    fetch('api/dias_bloqueados.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'bloquear',
            data: data,
            tipo: 'outro',
            descricao: descricao
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            loadBloqueios();
            
            // Limpar formulário
            const form = document.getElementById('formBloquear');
            if (form) form.reset();
            
            // Forçar reload do cache de bloqueios
            loadBloqueiosCustomizados(null, true).then(() => {
                // Recarregar calendário se estiver visível
                if (window.adminCalendar) {
                    window.adminCalendar.refetchEvents();
                    // Forçar re-renderização dos dias
                    window.adminCalendar.render();
                }
            });
        } else {
            showAlert('danger', data.message || 'Erro ao bloquear dia');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro ao bloquear dia');
    });
}

// Remover customização (volta ao comportamento padrão)
function removerCustomizacao(data) {
    if (!confirm('Tem certeza que deseja remover a customização? O dia voltará ao comportamento padrão.')) {
        return;
    }
    
    fetch('api/dias_bloqueados.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            data: data
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            loadBloqueios();
            
            // Forçar reload do cache de bloqueios
            loadBloqueiosCustomizados(null, true).then(() => {
                // Recarregar calendário se estiver visível
                if (window.adminCalendar) {
                    window.adminCalendar.refetchEvents();
                    // Forçar re-renderização dos dias
                    window.adminCalendar.render();
                }
            });
        } else {
            showAlert('danger', data.message || 'Erro ao remover customização');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro ao remover customização');
    });
}

// ============ FUNÇÕES DE ESTATÍSTICAS E GRÁFICOS ============

let charts = {}; // Armazenar instâncias dos gráficos

function setupEstatisticas() {
    const estatisticasTab = document.getElementById('estatisticas-tab');
    if (estatisticasTab) {
        estatisticasTab.addEventListener('click', function() {
            loadEstatisticas();
        });
    }
}

function loadEstatisticas() {
    loadResumo();
    loadChartReservasAno();
    loadChartStatusReservas();
    loadChartUsuariosNovos();
    loadChartTopUsuarios();
}

// Carregar resumo
function loadResumo() {
    fetch('api/estatisticas.php?action=resumo')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('stat-usuarios').textContent = data.dados.total_usuarios;
                document.getElementById('stat-reservas-mes').textContent = data.dados.reservas_mes;
                document.getElementById('stat-reservas-ano').textContent = data.dados.reservas_ano;
                document.getElementById('stat-taxa-aprovacao').textContent = data.dados.taxa_aprovacao + '%';
            }
        })
        .catch(error => console.error('Erro ao carregar resumo:', error));
}

// Gráfico de Reservas por Mês
function loadChartReservasAno() {
    fetch('api/estatisticas.php?action=reservas_ano')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.dados.map(d => d.mes);
                const values = data.dados.map(d => d.total);
                
                // Destruir gráfico anterior se existir
                if (charts.reservasAno) {
                    charts.reservasAno.destroy();
                }
                
                const ctx = document.getElementById('chartReservasAno').getContext('2d');
                charts.reservasAno = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Reservas',
                            data: values,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Erro ao carregar gráfico:', error));
}

// Gráfico de Status das Reservas
function loadChartStatusReservas() {
    fetch('api/estatisticas.php?action=status_reservas')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.dados.map(d => d.status);
                const values = data.dados.map(d => d.total);
                
                const cores = {
                    'APROVADO': '#28a745',
                    'PENDENTE': '#ffc107',
                    'RECUSADO': '#dc3545',
                    'CANCELADO': '#6c757d'
                };
                
                const backgroundColors = labels.map(l => cores[l] || '#6c757d');
                
                if (charts.statusReservas) {
                    charts.statusReservas.destroy();
                }
                
                const ctx = document.getElementById('chartStatusReservas').getContext('2d');
                charts.statusReservas = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: backgroundColors
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Erro ao carregar gráfico:', error));
}

// Gráfico de Novos Usuários
function loadChartUsuariosNovos() {
    fetch('api/estatisticas.php?action=usuarios_novos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.dados.map(d => {
                    const [ano, mes] = d.mes.split('-');
                    const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                    return meses[parseInt(mes) - 1] + '/' + ano.substr(2);
                });
                const values = data.dados.map(d => d.total);
                
                if (charts.usuariosNovos) {
                    charts.usuariosNovos.destroy();
                }
                
                const ctx = document.getElementById('chartUsuariosNovos').getContext('2d');
                charts.usuariosNovos = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Novos Usuários',
                            data: values,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Erro ao carregar gráfico:', error));
}

// Gráfico Top Usuários
function loadChartTopUsuarios() {
    fetch('api/estatisticas.php?action=reservas_usuario')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const labels = data.dados.map(d => {
                    // Truncar nome se muito longo
                    return d.nome.length > 20 ? d.nome.substr(0, 20) + '...' : d.nome;
                });
                const values = data.dados.map(d => d.total);
                
                if (charts.topUsuarios) {
                    charts.topUsuarios.destroy();
                }
                
                const ctx = document.getElementById('chartTopUsuarios').getContext('2d');
                charts.topUsuarios = new Chart(ctx, {
                    type: 'horizontalBar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Reservas Aprovadas',
                            data: values,
                            backgroundColor: 'rgba(153, 102, 255, 0.6)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Erro ao carregar gráfico:', error));
}
