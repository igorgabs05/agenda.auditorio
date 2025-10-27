# 🧪 Teste: Dias Desbloqueados para Usuários

## ✅ Correções Implementadas

### 1. **API Liberada para Usuários Comuns**
- Antes: Apenas admins podiam consultar bloqueios
- Agora: Todos usuários logados podem consultar (GET)
- Apenas admins podem modificar (POST/DELETE)

### 2. **Logs de Debug Adicionados**
- Console mostrará todo o processo de verificação
- Útil para identificar problemas

### 3. **Cache Forçado a Recarregar**
- Após admin modificar bloqueios, cache é invalidado
- Garante que todos vejam dados atualizados

## 📋 Como Testar

### Teste 1: Admin Desbloqueia um Domingo

1. **Login como Admin**
   - Acesse: `admin@auditorio.com` / `password`

2. **Desbloquear Domingo**
   - Vá para: Painel Admin → Gerenciar Bloqueios
   - Selecione um domingo futuro (ex: próximo domingo)
   - Adicione descrição: "Evento especial"
   - Clique em "Desbloquear"

3. **Verificar no Console**
   ```
   Abra DevTools (F12) → Console
   Você deve ver:
   - "Bloqueios carregados com sucesso: X dias customizados"
   ```

4. **Verificar no Calendário (Admin)**
   - O domingo deve ter badge verde "✔ Liberado"
   - Clique no dia → Deve abrir modal de reserva

### Teste 2: Usuário Comum Tenta Reservar

1. **Logout do Admin**
   - Clique em "Sair"

2. **Login como Usuário Comum**
   - Use um usuário comum ou crie um novo

3. **Abrir DevTools**
   ```
   F12 → Console
   Limpe o console (Ctrl+L)
   ```

4. **Recarregar Página**
   ```
   F5 ou Ctrl+R
   
   No console você deve ver:
   - "Carregando bloqueios do servidor para ano: 2025"
   - "Resposta da API de bloqueios: {success: true, dias: [...]}"
   - "Bloqueios carregados com sucesso: X dias customizados"
   ```

5. **Clicar no Domingo Desbloqueado**
   ```
   No console você deve ver:
   - "Verificando disponibilidade para: 2025-XX-XX"
   - "Bloqueio encontrado para 2025-XX-XX: {bloqueado: 0, ...}"
   - "Dia customizado como DESBLOQUEADO"
   - "Dia disponível"
   ```

6. **Modal Deve Abrir**
   - Se tudo funcionar, modal de "Solicitar Reserva" abre
   - Data já preenchida com o domingo selecionado

### Teste 3: Verificar Bloqueio Continua Funcionando

1. **Clicar em Domingo Normal (Não Desbloqueado)**
   ```
   Console deve mostrar:
   - "Verificando disponibilidade para: 2025-XX-XX"
   - "Domingo (bloqueado por padrão)"
   ```
   - Toast de aviso aparece
   - Modal NÃO abre

## 🐛 Debug de Problemas

### Problema: API Retorna Erro 401 ou 403

**Sintoma:**
```
API retornou erro: Acesso negado
```

**Solução:**
1. Verifique se está logado
2. Limpe cookies e faça login novamente
3. Verifique sessão PHP em `C:\xampp\tmp\`

### Problema: Cache Não Atualiza

**Sintoma:**
```
Usando cache de bloqueios: 0 itens
(mesmo após desbloquear)
```

**Solução:**
1. Limpe cache do navegador (Ctrl+Shift+Del)
2. No console execute:
   ```javascript
   bloqueiosCache = null;
   bloqueiosCacheTime = 0;
   await loadBloqueiosCustomizados(2025, true);
   ```

### Problema: Dia Não Aparece como Desbloqueado

**Sintoma:**
- Admin desbloqueou mas usuário não vê

**Verificações:**
1. **Verifique Banco de Dados**
   ```sql
   SELECT * FROM dias_bloqueados;
   ```
   - Deve ter registro com `bloqueado = 0`

2. **Teste a API Diretamente**
   ```
   Navegador: http://localhost/agenda%20auditorio/api/dias_bloqueados.php?ano=2025
   ```
   - Deve retornar JSON com dias customizados

3. **Verifique Data no Formato Correto**
   - Deve ser: `2025-01-05` (YYYY-MM-DD)
   - Banco e JavaScript devem usar mesmo formato

### Problema: Modal Não Abre

**Sintoma:**
- Console mostra "Dia disponível"
- Mas modal não abre

**Verificação:**
1. Verifique se elemento existe:
   ```javascript
   console.log(document.getElementById('modalSolicitar'));
   ```
   - Se `null`, usuário não tem permissão (não é tipo 'usuario')

2. Verifique tipo de usuário:
   ```php
   // Em index.php, adicione temporariamente:
   <?php var_dump($_SESSION['user_tipo']); ?>
   ```

## 🔍 Monitoramento em Tempo Real

Execute no console para monitorar:

```javascript
// Ver cache atual
console.log('Cache:', bloqueiosCache);

// Forçar reload
await loadBloqueiosCustomizados(2025, true);

// Testar dia específico
const date = new Date('2025-01-05'); // Ajuste a data
const result = await isDiaDisponivelAsync(date);
console.log('Resultado:', result);

// Ver bloqueio de dia específico
const date = new Date('2025-01-05');
const bloqueio = getBloqueioCustomizado(date);
console.log('Bloqueio:', bloqueio);
```

## ✅ Checklist Final

- [ ] API `dias_bloqueados.php` permite GET para usuários comuns
- [ ] Admin consegue desbloquear dias
- [ ] Dias desbloqueados aparecem com badge verde
- [ ] Usuário comum vê dias desbloqueados
- [ ] Usuário comum pode clicar em dias desbloqueados
- [ ] Modal abre corretamente
- [ ] Dias normais bloqueados (domingos/feriados) continuam bloqueados
- [ ] Cache atualiza após modificações do admin

## 📞 Se Ainda Não Funcionar

1. **Copie os logs do console** (F12 → Console → Clique direito → Save as...)

2. **Verifique o banco de dados**:
   ```sql
   SELECT * FROM dias_bloqueados WHERE bloqueado = 0;
   ```

3. **Teste a API no Postman/Insomnia**:
   - GET: `http://localhost/agenda%20auditorio/api/dias_bloqueados.php?ano=2025`
   - Headers: Cookie da sessão PHP

4. **Verifique permissões de arquivo**:
   - `api/dias_bloqueados.php` deve ter permissão de leitura

## 🎯 Resultado Esperado

**Admin desbloqueia domingo 05/01/2025:**
1. Admin vê badge verde "✔ Liberado" no dia
2. Admin pode clicar e criar reserva

**Usuário comum acessa sistema:**
1. Vê badge verde "✔ Liberado" no dia
2. Pode clicar no dia
3. Modal de solicitar reserva abre
4. Pode enviar solicitação normalmente
