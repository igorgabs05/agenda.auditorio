// assets/js/utils.js - Funções utilitárias

// Feriados brasileiros (pode ser expandido)
const feriados = {
    fixos: [
        { mes: 1, dia: 1, nome: 'Ano Novo' },
        { mes: 4, dia: 21, nome: 'Tiradentes' },
        { mes: 5, dia: 1, nome: 'Dia do Trabalho' },
        { mes: 9, dia: 7, nome: 'Independência' },
        { mes: 10, dia: 12, nome: 'Nossa Senhora Aparecida' },
        { mes: 11, dia: 2, nome: 'Finados' },
        { mes: 11, dia: 15, nome: 'Proclamação da República' },
        { mes: 12, dia: 25, nome: 'Natal' }
    ],
    moveis: {
        2025: [
            { mes: 3, dia: 4, nome: 'Carnaval' },
            { mes: 4, dia: 18, nome: 'Sexta-feira Santa' },
            { mes: 6, dia: 19, nome: 'Corpus Christi' }
        ],
        2026: [
            { mes: 2, dia: 17, nome: 'Carnaval' },
            { mes: 4, dia: 3, nome: 'Sexta-feira Santa' },
            { mes: 6, dia: 4, nome: 'Corpus Christi' }
        ]
    }
};

// Verificar se é feriado
function isFeriado(date) {
    const dia = date.getDate();
    const mes = date.getMonth() + 1;
    const ano = date.getFullYear();
    
    // Verificar feriados fixos
    const feriadoFixo = feriados.fixos.find(f => f.mes === mes && f.dia === dia);
    if (feriadoFixo) return feriadoFixo;
    
    // Verificar feriados móveis
    if (feriados.moveis[ano]) {
        const feriadoMovel = feriados.moveis[ano].find(f => f.mes === mes && f.dia === dia);
        if (feriadoMovel) return feriadoMovel;
    }
    
    return null;
}

// Verificar se é domingo
function isDomingo(date) {
    return date.getDay() === 0;
}

// Cache de bloqueios customizados (para performance)
let bloqueiosCache = null;
let bloqueiosCacheTime = 0;
const CACHE_DURATION = 60000; // 1 minuto

// Carregar bloqueios customizados do servidor
async function loadBloqueiosCustomizados(ano = null, forceReload = false) {
    if (!ano) ano = new Date().getFullYear();
    
    // Usar cache se ainda válido (a menos que forçado a recarregar)
    const now = Date.now();
    if (!forceReload && bloqueiosCache && (now - bloqueiosCacheTime) < CACHE_DURATION) {
        console.log('Usando cache de bloqueios:', bloqueiosCache.length, 'itens');
        return bloqueiosCache;
    }
    
    try {
        console.log('Carregando bloqueios do servidor para ano:', ano);
        const response = await fetch(`api/dias_bloqueados.php?ano=${ano}`);
        const data = await response.json();
        
        console.log('Resposta da API de bloqueios:', data);
        
        if (data.success) {
            bloqueiosCache = data.dias;
            bloqueiosCacheTime = now;
            console.log('Bloqueios carregados com sucesso:', bloqueiosCache.length, 'dias customizados');
            return data.dias;
        } else {
            console.error('API retornou erro:', data.message);
        }
    } catch (error) {
        console.error('Erro ao carregar bloqueios customizados:', error);
    }
    
    return [];
}

// Verificar se uma data tem bloqueio customizado
function getBloqueioCustomizado(date) {
    if (!bloqueiosCache) {
        console.warn('Cache de bloqueios não inicializado');
        return null;
    }
    
    const dateStr = date.toISOString().split('T')[0];
    const bloqueio = bloqueiosCache.find(b => b.data === dateStr);
    
    if (bloqueio) {
        console.log('Bloqueio encontrado para', dateStr, ':', bloqueio);
    }
    
    return bloqueio;
}

// Verificar se o dia está disponível para agendamento (versão assíncrona)
async function isDiaDisponivelAsync(date) {
    console.log('Verificando disponibilidade para:', date.toISOString().split('T')[0]);
    
    // Carregar bloqueios customizados
    await loadBloqueiosCustomizados(date.getFullYear());
    
    // Verificar se tem customização
    const bloqueioCustom = getBloqueioCustomizado(date);
    
    if (bloqueioCustom) {
        // Se customizado, usar o status customizado
        if (bloqueioCustom.bloqueado == 1) {
            console.log('Dia customizado como BLOQUEADO');
            return { 
                disponivel: false, 
                motivo: bloqueioCustom.descricao || 'Dia bloqueado pelo administrador' 
            };
        } else {
            // Desbloqueado pelo admin
            console.log('Dia customizado como DESBLOQUEADO');
            return { disponivel: true };
        }
    }
    
    // Comportamento padrão (sem customização)
    if (isDomingo(date)) {
        console.log('Domingo (bloqueado por padrão)');
        return { disponivel: false, motivo: 'Domingos não estão disponíveis' };
    }
    
    const feriado = isFeriado(date);
    if (feriado) {
        console.log('Feriado (bloqueado por padrão):', feriado.nome);
        return { disponivel: false, motivo: `Feriado: ${feriado.nome}` };
    }
    
    console.log('Dia disponível');
    return { disponivel: true };
}

// Versão síncrona (para compatibilidade com código existente)
function isDiaDisponivel(date) {
    // Verificar se tem customização no cache
    const bloqueioCustom = getBloqueioCustomizado(date);
    
    if (bloqueioCustom) {
        if (bloqueioCustom.bloqueado == 1) {
            return { 
                disponivel: false, 
                motivo: bloqueioCustom.descricao || 'Dia bloqueado pelo administrador' 
            };
        } else {
            return { disponivel: true };
        }
    }
    
    // Comportamento padrão
    if (isDomingo(date)) return { disponivel: false, motivo: 'Domingos não estão disponíveis' };
    
    const feriado = isFeriado(date);
    if (feriado) return { disponivel: false, motivo: `Feriado: ${feriado.nome}` };
    
    return { disponivel: true };
}

// ============ SISTEMA DE NOTIFICAÇÕES TOAST ============

let toastContainer = null;

// Criar container de toast se não existir
function getToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    return toastContainer;
}

// Mostrar notificação toast
function showToast(type, title, message, duration = 4000) {
    const container = getToastContainer();
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || 'ℹ'}</div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this)">×</button>
    `;
    
    container.appendChild(toast);
    
    // Auto remover após duração
    setTimeout(() => {
        closeToast(toast.querySelector('.toast-close'));
    }, duration);
}

// Fechar toast
function closeToast(button) {
    const toast = button.closest('.toast-notification');
    if (toast) {
        toast.classList.add('removing');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

// Atalhos para tipos de notificação
function showSuccess(message, title = 'Sucesso!') {
    showToast('success', title, message);
}

function showError(message, title = 'Erro!') {
    showToast('error', title, message);
}

function showWarning(message, title = 'Atenção!') {
    showToast('warning', title, message);
}

function showInfo(message, title = 'Informação') {
    showToast('info', title, message);
}

// Substituir a função showAlert antiga para compatibilidade
function showAlert(type, message) {
    const titles = {
        success: 'Sucesso!',
        danger: 'Erro!',
        error: 'Erro!',
        warning: 'Atenção!',
        info: 'Informação'
    };
    
    const toastType = type === 'danger' ? 'error' : type;
    showToast(toastType, titles[type] || 'Notificação', message);
}

// ============ FORMATAÇÃO ============

// Formatar data para exibição
function formatarData(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Formatar data e hora
function formatarDataHora(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Exportar funções globalmente
window.showToast = showToast;
window.showSuccess = showSuccess;
window.showError = showError;
window.showWarning = showWarning;
window.showInfo = showInfo;
window.showAlert = showAlert;
window.closeToast = closeToast;
window.isFeriado = isFeriado;
window.isDomingo = isDomingo;
window.isDiaDisponivel = isDiaDisponivel;
window.isDiaDisponivelAsync = isDiaDisponivelAsync;
window.loadBloqueiosCustomizados = loadBloqueiosCustomizados;
window.getBloqueioCustomizado = getBloqueioCustomizado;
window.formatarData = formatarData;
window.formatarDataHora = formatarDataHora;
