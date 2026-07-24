# Layout em abas para a tela de manutenção do equipamento (`host/view`)

## Contexto

A tela `host/view` (mapa + portas + tabela "Info:" + sidebar "Operations") foi construída com uma tabela HTML simples e uma sidebar de links de texto (gerada pelo Gii do Yii). Ela mistura, na mesma coluna lateral, telas de visualização (ARP Table, CAM Table, Traffic, Host Connections) com ações de gestão do host (Update, Delete, Manage, Create, Web Config). Não há hoje nenhum lugar para o operador registrar informação própria sobre o equipamento (notas, localização física, histórico de manutenção) — os únicos campos "Info" vêm do próprio SNMP e costumam aparecer como "Not set".

Este é o primeiro de uma série de specs para tornar essa tela mais intuitiva para manutenção de campo. **Esta spec cobre apenas a reestruturação do layout** (onde cada bloco de informação mora). Specs seguintes vão preencher as abas "Documentação" e "Histórico" com campos reais (notas livres, dados estruturados de local, histórico datado, notas por porta, foto + GPS).

## Objetivo

Reorganizar `host/view` em um cabeçalho fixo (sempre visível) + 4 abas, preparando o terreno de navegação para as futuras funcionalidades de documentação, sem exigir nenhuma mudança de banco de dados nesta etapa.

## Arquitetura da página

```
┌─────────────────────────────────────────────┐
│ 🔀 <nome> — <tipo> — <ip>          Ações ▾  │  ← cabeçalho fixo
├─────────────────────────────────────────────┤
│ [mapa]                                       │
│ [portas — faixa colorida, sempre visível]    │
├─────────────────────────────────────────────┤
│ Visão Geral │ Documentação │ Histórico │ Diagnóstico │  ← abas
├─────────────────────────────────────────────┤
│ (conteúdo da aba ativa)                      │
└─────────────────────────────────────────────┘
```

Mapa e portas ficam **fora** das abas, sempre visíveis — são a informação mais consultada (posição visual, status das portas) e não fazem sentido escondidas atrás de uma aba.

## Componentes

### Cabeçalho
- Identidade do equipamento: ícone por tipo (já existe, reaproveitado de `_view.php`/CSS `host-type`), nome, tipo, IP.
- Menu "Ações" (dropdown Bootstrap 2 nativo — `<div class="btn-group">` + `dropdown-toggle`/`dropdown-menu`, já usado nesse padrão pelo tema Bootstrap do projeto; não existe widget `TbDropdown` pronto na extensão) reunindo o que hoje é ação de gestão *deste* host específico:
  - Web Config (link externo pro IP do equipamento)
  - Update Host
  - Delete Host (mantém confirm dialog existente)
- Itens que **não** são específicos deste host (List Host, Create Host, Manage Host, Manage Host Faces) saem da página de detalhe — já existem no menu principal "Hosts" da barra de navegação superior, então ficam só lá. Evita duplicar navegação global em cada host.

### Mapa + Portas
Mantém exatamente o que já existe hoje (`renderPartial('/map/_view', ...)` e `renderPartial('/host/_ports', ...)`), só move de posição — sem mudança de comportamento ou JS.

### Abas (`TbTabs`, já disponível na extensão bootstrap do projeto — sem nova dependência)

1. **Visão Geral** — substitui a tabela "Info:" atual (`TbDetailView` com os mesmos atributos: id, name, type, mac, ip, snmpTemplate, InfoSerialNumber, InfoModel, InfoSystem, InfoUptime, InfoContact, InfoLocation). Única mudança de comportamento: os 6 atributos que vêm de SNMP (`InfoSerialNumber`, `InfoModel`, `InfoSystem`, `InfoUptime`, `InfoContact`, `InfoLocation` — todos via `PNMSnmp::get()`, que retorna `null` quando o equipamento não responde) passam a usar um `'value'` customizado no `TbDetailView` que troca o `null` por "não informado via SNMP", em vez de depender do `nullDisplay` global do widget (que é "Not set" pra qualquer atributo vazio, inclusive campos de banco como `mac`/`ip`/`snmpTemplate` — esses continuam com o "Not set" padrão do Yii, sem mudança). Isso também prepara terreno para uma spec futura permitir preenchimento manual desses campos.

2. **Documentação** — nesta spec, só o estado vazio ("Nenhuma nota registrada ainda"). Conteúdo real (notas livres, campos estruturados de local, notas por porta) é escopo de spec futura.

3. **Histórico** — nesta spec, só o estado vazio ("Nenhum registro de manutenção ainda"). Conteúdo real (log datado de intervenções) é escopo de spec futura.

4. **Diagnóstico** — grid de cards/atalhos para as 4 páginas que já existem hoje (ARP Table, CAM Table, Traffic, Host Connections). Cada card é só um link — essas páginas continuam sendo navegação completa (saem de `host/view`), **não** viram conteúdo carregado via AJAX dentro da aba. Isso mantém o escopo desta spec pequeno; virar AJAX-embedded é uma melhoria possível para depois, não faz parte daqui.

## Fluxo de dados

Nenhuma mudança de model, controller action ou schema de banco nesta spec — é reorganização de view. `HostController::actionView()` continua igual; o que muda é só `protected/views/host/view.php` (e a criação de partials novos para os blocos de cada aba, quebrando o que hoje é um `<table>` monolítico em pedaços menores e nomeados, ex.: `_header.php`, `_tabOverview.php`, `_tabDocs.php`, `_tabHistory.php`, `_tabDiagnostics.php`).

## Estados vazios / erros

- Campos SNMP vazios na aba Visão Geral: texto "não informado via SNMP" em vez de "Not set" (label idêntico ao padrão do Yii hoje, só muda o texto do valor vazio).
- Abas Documentação e Histórico: mensagem central discreta explicando que o recurso ainda não existe, sem formulário nem botão de ação (evita sugerir uma funcionalidade que ainda não foi implementada).

## Compatibilidade / responsividade

O layout usa os componentes Bootstrap 2 já carregados no projeto (`TbTabs`, `nav-tabs`), então funciona sem JS novo. Em telas estreitas, as abas do Bootstrap 2 quebram linha por padrão — aceitável para esta spec (o layout mobile-first "coluna única" foi cogitado como direção C e não foi escolhido agora; pode voltar como uma spec própria mais adiante, especialmente quando entrar o fluxo de foto + GPS pensado para uso em campo).

## Testes

Este projeto não tem suíte de testes automatizados para views (só testes funcionais Selenium, que não rodam neste ambiente). Verificação será manual, no navegador, cobrindo:
- Host com SNMP funcionando normalmente (ex.: `localhost`) — todas as abas renderizam, portas continuam aparecendo.
- Host com campos SNMP vazios (Serial/Model "not set") — aba Visão Geral mostra o novo texto.
- Menu Ações — Update/Delete/Web Config continuam funcionando como antes.
- Aba Diagnóstico — os 4 links levam pras páginas corretas.

## Fora de escopo (specs futuras)

- Campos de notas livres e dados estruturados de local (aba Documentação)
- Histórico de manutenção datado (aba Histórico)
- Notas locais por porta (distintas do `ifAlias`, que já é gravado no próprio switch via SNMP)
- Captura de foto + GPS pelo celular — **atenção**: a Geolocation API do navegador exige contexto seguro (HTTPS); o ambiente hoje roda em HTTP puro, então essa spec futura vai precisar resolver certificado antes (ou aceitar que a marcação de GPS não funcione)
