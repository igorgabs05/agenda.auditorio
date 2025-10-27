# Guia de Teste - Correções Finais do Sistema

## Correções Implementadas

1. **Dias desbloqueados no painel admin agora aparecem desbloqueados no index.php**
2. **Botões de visualização do calendário (Mês/Semana/Dia) agora funcionam corretamente**
3. **Cache de bloqueios é atualizado automaticamente ao trocar de visualização**

## Pré-requisitos

- Sistema rodando no XAMPP
- Dois navegadores ou abas anônimas (uma para admin, outra para usuário)
- Banco de dados configurado

---

## Teste 1: Desbloquear Feriado e Verificar em Ambos os Calendários

### Passo 1: Como Admin - Desbloquear um Feriado

1. Faça login como administrador
2. Vá para "Painel Admin"
3. Clique na aba "Gerenciar Bloqueios"
4. Localize um feriado (ex: Natal - 25/12)
5. Na seção "Desbloquear Feriado/Domingo":
   - Selecione a data do feriado
   - Digite uma descrição (ex: "Evento especial de Natal")
   - Clique em "Desbloquear"
6. Verifique que:
   - ✅ Aparece mensagem de sucesso
   - ✅ O feriado aparece na lista com status "Desbloqueado" (badge verde)
   - ✅ No calendário do painel admin, o dia tem badge verde "✔ Liberado"

### Passo 2: Como Usuário - Verificar o Mesmo Feriado

1. Em outra aba/navegador, faça login como usuário comum
2. Na página inicial (index.php), observe o calendário
3. Localize o mesmo feriado que foi desbloqueado
4. Verifique que:
   - ✅ O dia mostra badge verde "✔ Liberado"
   - ✅ O dia NÃO está com cursor "not-allowed"
   - ✅ Ao clicar no dia, abre o modal de solicitação de reserva
   - ✅ É possível criar uma solicitação para esse dia

### Passo 3: Criar Reserva no Dia Desbloqueado

1. Como usuário, clique no feriado desbloqueado
2. Preencha o formulário:
   - Horário início: 14:00
   - Horário fim: 18:00
   - Motivo: "Evento especial de fim de ano"
3. Clique em "Solicitar Reserva"
4. Verifique que:
   - ✅ Solicitação é enviada com sucesso
   - ✅ O evento aparece no calendário como "PENDENTE"

### Passo 4: Como Admin - Aprovar e Verificar

1. Volte para o admin
2. Veja a notificação da nova solicitação
3. Aprove a reserva
4. Verifique que:
   - ✅ A reserva aparece no calendário do admin
   - ✅ A reserva aparece no calendário do usuário (após recarregar)

---

## Teste 2: Botões de Visualização do Calendário

### No Calendário do Usuário (index.php)

1. Faça login como usuário
2. No calendário, clique nos botões no canto superior direito:

#### Teste 2.1: Botão "Semana"
1. Clique em "Semana"
2. Verifique que:
   - ✅ A visualização muda para semana
   - ✅ Mostra os horários das reservas corretamente
   - ✅ O título mostra o intervalo da semana
   - ✅ Os dias bloqueados/desbloqueados são mostrados corretamente

#### Teste 2.2: Botão "Dia"
1. Clique em "Dia"
2. Verifique que:
   - ✅ A visualização muda para um único dia
   - ✅ Mostra os horários em detalhes
   - ✅ O título mostra a data do dia

#### Teste 2.3: Botão "Mês"
1. Clique em "Mês"
2. Verifique que:
   - ✅ A visualização volta para o mês completo
   - ✅ Todos os eventos estão visíveis

#### Teste 2.4: Troca Múltipla de Visualizações
1. Clique em "Semana" → "Dia" → "Mês" → "Semana"
2. Verifique que:
   - ✅ Todas as transições funcionam suavemente
   - ✅ Não há erros no console (F12)
   - ✅ Os bloqueios são atualizados em cada visualização

### No Calendário do Admin (admin.php)

Repita os mesmos testes acima no painel admin e verifique que:
- ✅ Todos os botões funcionam corretamente
- ✅ As visualizações mudam adequadamente
- ✅ Os bloqueios/desbloqueios são mostrados corretamente em todas as visualizações

---

## Teste 3: Sincronização Entre Admin e Usuário

### Passo 1: Admin Bloqueia um Dia Normal

1. Como admin, vá para "Gerenciar Bloqueios"
2. Na seção "Bloquear Dia Específico":
   - Selecione uma data futura qualquer (ex: próxima segunda-feira)
   - Digite uma descrição (ex: "Manutenção preventiva")
   - Clique em "Bloquear"
3. Verifique no calendário admin:
   - ✅ O dia mostra badge vermelho "❌ Bloqueado"

### Passo 2: Usuário Vê o Bloqueio

1. Como usuário, recarregue a página (F5)
2. Localize o mesmo dia que foi bloqueado
3. Verifique que:
   - ✅ O dia mostra badge vermelho "❌ Bloqueado"
   - ✅ Não é possível clicar para criar reserva

### Passo 3: Admin Remove o Bloqueio

1. Como admin, na lista de bloqueios
2. Clique em "Resetar" no dia bloqueado
3. Confirme a remoção
4. Verifique no calendário admin:
   - ✅ O badge desaparece
   - ✅ O dia volta ao comportamento normal

### Passo 4: Usuário Vê a Mudança

1. Como usuário, aguarde 2 minutos (atualização automática) ou recarregue (F5)
2. Verifique que:
   - ✅ O dia não tem mais badge
   - ✅ É possível clicar e criar reserva

---

## Teste 4: Navegação Entre Meses com Bloqueios

### No Calendário do Usuário

1. Faça login como usuário
2. Clique em "próximo" (→) para ir para o próximo mês
3. Verifique que:
   - ✅ Os bloqueios do novo mês são carregados
   - ✅ Feriados e domingos são mostrados corretamente
   - ✅ Desbloqueios feitos pelo admin aparecem
4. Clique em "anterior" (←) para voltar
5. Verifique que:
   - ✅ Os bloqueios são mantidos corretamente

### No Calendário do Admin

Repita os mesmos passos e verifique que tudo funciona corretamente.

---

## Teste 5: Console do Navegador (Debug)

Durante todos os testes, mantenha o console aberto (F12) e observe:

### Logs Esperados ao Trocar de Visualização:

```
View mounted: dayGridMonth
Carregando bloqueios do servidor para ano: 2025
Bloqueios carregados com sucesso: X dias customizados
```

Quando trocar para Semana:
```
View mounted: timeGridWeek
Carregando bloqueios do servidor para ano: 2025
Bloqueios carregados com sucesso: X dias customizados
```

Quando trocar para Dia:
```
View mounted: timeGridDay
Carregando bloqueios do servidor para ano: 2025
Bloqueios carregados com sucesso: X dias customizados
```

### Logs ao Desbloquear/Bloquear (Admin):

```
Dia customizado como DESBLOQUEADO
ou
Dia customizado como BLOQUEADO
```

---

## Verificação de Problemas Anteriores

### ❌ PROBLEMA ANTIGO: Dia desbloqueado no admin continuava bloqueado no index.php
### ✅ SOLUÇÃO: Agora ambos os calendários consultam a mesma API e cache

### ❌ PROBLEMA ANTIGO: Botões Mês/Semana/Dia não funcionavam para trocar
### ✅ SOLUÇÃO: Adicionados eventos `viewDidMount` e `datesSet` para atualizar bloqueios

---

## Checklist Final

Ao final de todos os testes, confirme:

- [ ] ✅ Dias desbloqueados no admin aparecem desbloqueados no index.php
- [ ] ✅ Dias bloqueados no admin aparecem bloqueados no index.php
- [ ] ✅ Botões Mês/Semana/Dia funcionam no calendário do usuário
- [ ] ✅ Botões Mês/Semana/Dia funcionam no calendário do admin
- [ ] ✅ Ao trocar de visualização, os bloqueios são atualizados
- [ ] ✅ Ao navegar entre meses, os bloqueios são carregados corretamente
- [ ] ✅ Não há erros no console do navegador
- [ ] ✅ A sincronização entre admin e usuário funciona (com reload ou após 2 minutos)
- [ ] ✅ É possível criar reservas em dias desbloqueados
- [ ] ✅ Não é possível criar reservas em dias bloqueados

---

## Troubleshooting

### Problema: Calendário não muda de visualização

**Solução:**
1. Verifique no console se há erros JavaScript
2. Confirme que o FullCalendar está carregado: `typeof FullCalendar` no console deve retornar "object"
3. Limpe o cache do navegador (Ctrl + Shift + Del)

### Problema: Bloqueios não sincronizam entre admin e usuário

**Solução:**
1. Verifique se a API está respondendo: `api/dias_bloqueados.php?ano=2025`
2. Confirme que o banco de dados tem a tabela `dias_bloqueados`
3. Recarregue a página do usuário após fazer mudanças no admin

### Problema: Badges não aparecem no calendário

**Solução:**
1. Verifique se o CSS está carregado: `senac-theme.css`
2. Confirme que `loadBloqueiosCustomizados` está sendo chamado
3. Verifique no console: `bloqueiosCache` deve conter os bloqueios

---

## Resultado Esperado

✅ **SISTEMA TOTALMENTE FUNCIONAL**
- Bloqueios sincronizados entre admin e usuário
- Todas as visualizações do calendário funcionando
- Dias desbloqueados realmente disponíveis para reserva
- Interface responsiva e sem erros