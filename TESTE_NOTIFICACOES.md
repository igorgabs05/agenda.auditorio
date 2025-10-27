# Guia de Teste - Sistema de Notificações com Motivo de Recusa

## Objetivo
Verificar se o sistema de notificações está funcionando corretamente, especialmente:
1. Exibição do ícone de sino com contador
2. Atualização dinâmica do contador de notificações não lidas
3. Exibição do motivo de recusa nas notificações
4. Animações visuais do ícone de notificação

## Pré-requisitos
- Sistema rodando no XAMPP
- Dois navegadores ou abas anônimas (uma para admin, outra para usuário)
- Banco de dados configurado

## Teste 1: Verificar Ícone de Sino e Contador

### Como Admin:
1. Faça login como administrador
2. Observe o ícone de sino no canto superior direito da navbar
3. Se houver notificações não lidas, deve aparecer um contador laranja sobre o sino

### Como Usuário:
1. Faça login como usuário comum
2. Observe o ícone de sino no canto superior direito da navbar
3. Se houver notificações não lidas, deve aparecer um contador laranja sobre o sino

## Teste 2: Criar Solicitação e Verificar Notificação ao Admin

### Como Usuário:
1. Clique em uma data disponível no calendário
2. Preencha o formulário de solicitação:
   - Horário início: 09:00
   - Horário fim: 12:00
   - Motivo: "Reunião de equipe"
   - Observações: "Precisamos do projetor"
3. Clique em "Solicitar Reserva"

### Como Admin:
1. Aguarde alguns segundos ou recarregue a página
2. O contador de notificações deve aumentar
3. O ícone de sino deve ter uma animação de "shake"
4. Clique no ícone de sino
5. Deve aparecer uma notificação sobre a nova solicitação

## Teste 3: Recusar Reserva com Motivo

### Como Admin:
1. Vá para o "Painel Admin"
2. Localize a solicitação pendente
3. Clique em "Recusar"
4. No prompt, digite um motivo detalhado, por exemplo:
   "O auditório estará em manutenção nesta data. Por favor, escolha outra data após o dia 15."
5. Confirme a recusa

### Como Usuário:
1. Aguarde alguns segundos ou recarregue a página
2. O contador de notificações deve aumentar
3. Clique no ícone de sino
4. A notificação deve mostrar:
   - Título: "Reserva Recusada"
   - Mensagem incluindo o motivo digitado pelo admin

## Teste 4: Verificar Atualização Automática

### Como Usuário:
1. Deixe a página aberta
2. Peça ao admin para aprovar ou recusar outra reserva
3. Aguarde 30 segundos (intervalo de atualização automática)
4. O contador deve atualizar automaticamente sem recarregar a página

## Teste 5: Marcar Notificação como Lida

### Como Usuário:
1. Clique no ícone de sino
2. Clique em uma notificação não lida (aparece em negrito)
3. A notificação deve perder o negrito
4. O contador deve diminuir
5. Se não houver mais notificações não lidas, o contador deve desaparecer

## Verificações Visuais

### CSS e Animações:
- [ ] O ícone de sino está visível e tem cor branca
- [ ] Ao passar o mouse sobre o sino, ele muda para laranja
- [ ] O contador tem fundo laranja e texto branco
- [ ] O contador tem uma animação de "pulse" suave
- [ ] Quando uma nova notificação chega, o sino faz uma animação de "shake"
- [ ] As notificações não lidas aparecem em negrito no dropdown
- [ ] O motivo da recusa aparece como texto secundário na notificação

## Debug - Console do Navegador

Se algo não estiver funcionando, abra o console (F12) e verifique:

```javascript
// No console, digite:
console.log('Verificando notificações...');

// Deve aparecer no log:
// - Chamadas para api/notificacoes.php?action=list
// - Chamadas para api/notificacoes.php?action=count
// - Mensagens sobre carregamento de notificações
```

## Troubleshooting

### Problema: Ícone de sino não aparece
**Solução:** Verifique se o Font Awesome está carregando corretamente. No console, procure por erros de carregamento de fontes.

### Problema: Contador não atualiza
**Solução:** 
1. Verifique se a API está retornando o count correto: `api/notificacoes.php?action=count`
2. Verifique no console se há erros JavaScript
3. Certifique-se de que a sessão está ativa

### Problema: Motivo da recusa não aparece
**Solução:**
1. Verifique no banco de dados se o campo `observacoes` foi salvo na tabela `reservas`
2. Verifique se a API está retornando o campo `mensagem` completo
3. No console, faça: `fetch('api/notificacoes.php?action=list').then(r=>r.json()).then(console.log)`

### Problema: Animações não funcionam
**Solução:** Verifique se o arquivo `senac-theme.css` está sendo carregado e contém as animações @keyframes

## Resultado Esperado

Ao final dos testes, o sistema deve:
1. ✅ Exibir ícone de sino com contador dinâmico
2. ✅ Atualizar notificações automaticamente a cada 30 segundos
3. ✅ Mostrar o motivo da recusa nas notificações do usuário
4. ✅ Ter animações visuais funcionando (pulse no contador, shake no sino)
5. ✅ Permitir marcar notificações como lidas
6. ✅ Exibir notificações com formatação adequada (negrito para não lidas)