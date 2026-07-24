# Editor visual de Host Face

## Contexto

`HostFace` (`protected/models/HostFace.php`) representa o diagrama visual do painel de um switch: uma foto do equipamento com retângulos `<rect class="port" id="portN">` sobrepostos nas posições reais das portas, usado pela tela `host/view` para desenhar o status (cor) de cada porta em cima da foto real do equipamento em vez de uma grade genérica. Hoje existem 6 faces cadastradas (3Com Baseline Switch 2928-SFP, HP V1910-48G, H3C S5500EI, Procurve J9279A, Procurve J9280A, HUAWEI_S5700), cada uma criada à mão no Inkscape e colada como texto bruto no único campo do formulário (`protected/views/hostFace/_form.php`, um `<textarea>`).

O levantamento do Zabbix do POP-RR identificou **15 modelos de switch sem face cadastrada** (Huawei S5720/S6730, várias linhas Extreme ExtremeXOS, HP/HPE 1920/1910/OfficeConnect, H3C "4210G", Cisco CBS250, TP-Link JetStream). Criar essas faces à mão, sem uma foto de referência alinhada pixel a pixel, não é viável com precisão. Esta spec cobre um **editor visual** embutido na própria tela de criar/editar Host Face, que substitui o textarea.

## Objetivo

Permitir montar uma Host Face nova (foto + portas posicionadas corretamente) arrastando retângulos pré-numerados de uma lista lateral para cima da foto do switch — sem editar XML/SVG à mão — e reabrir uma face já existente (nova ou uma das 6 antigas) para continuar ajustando.

## Arquitetura

O editor substitui o `<textarea>` do campo `svg` em `protected/views/hostFace/_form.php`. Um campo oculto (`<input type="hidden" name="HostFace[svg]">`) é mantido sincronizado pelo JS a cada mudança no canvas; ao submeter, o formulário e o `HostFaceController` continuam exatamente como são hoje — nenhuma mudança de controller/model além de um novo endpoint auxiliar (ver "Buscar imagem por URL" abaixo). Tecnologia: jQuery + Drag and Drop API nativa do HTML5 (mesma pilha já usada no app — sem biblioteca nova).

```
┌───────────────────────────────────────────────────┐
│ Host de origem (SNMP): [dropdown]  Porta: [W]x[H] │  ← barra de config
├──────────────────────────────┬────────────────────┤
│                               │  Portas             │
│   [foto do switch]            │  ⠿ port1 — Gi0/1   │  ← lista lateral
│   [rect port1] [rect port2]  │  ⠿ port2 — Gi0/2   │    (arrasta pro canvas)
│                               │  ⠿ port3 — Gi0/3   │
├──────────────────────────────┴────────────────────┤
│ [ Upload de imagem ]  [ Colar URL da imagem ]      │
└───────────────────────────────────────────────────┘
```

## Componentes

### 1. Barra de configuração
- **Host de origem (SNMP)**: `<select>` populado com `Host::model()->findAll()` (mesma fonte de dados já usada no `dropDownList` de `hosts` no formulário atual). Ao escolher um host, dispara AJAX pro endpoint **já existente** `host/loadPortInfo/<id>` (o mesmo usado por `_ports.php`), que devolve `[{ifIndex, ifDescr, ifAlias, ...}]` — isso popula a lista lateral. Não é obrigatório escolher um host (dá pra editar uma face sem lista de portas pré-carregada, ficando só com o canvas livre).
- **Tamanho da porta (largura x altura em px)**: dois `<input type="number">`. Definem o tamanho do retângulo que será criado quando uma porta da lista for solta no canvas. Mudar o valor não afeta portas já posicionadas (só as próximas).

### 2. Canvas (foto + portas posicionadas)
- `<div>` com a imagem de fundo (`<img>` ou a própria imagem embutida via SVG) e um `<svg>` overlay do mesmo tamanho natural da imagem, escalado via CSS para caber na tela — coordenadas de clique/drop são convertidas da escala exibida para a escala natural da imagem antes de salvar, garantindo que o SVG final tenha as posições corretas independente do zoom de exibição.
- Portas já posicionadas aparecem como `<rect class="port" id="portN">` com o número/label visível (texto sobreposto ou tooltip).
- **Mover**: clicar e arrastar uma porta já posicionada reposiciona ela (mesmo mecanismo de drag do drop inicial).
- **Remover**: um botão "×" no canto do retângulo (aparece ao passar o mouse) ou tecla Delete com a porta selecionada — remove do canvas e ela **volta a aparecer na lista lateral**.

### 3. Lista lateral (portas disponíveis)
- Cada item é `⠿ port<ifIndex> — <ifDescr ou ifAlias>`, `draggable="true"`.
- Ao ser solta sobre o canvas, cria um `<rect class="port" id="port<ifIndex>">` na posição do drop, do tamanho configurado na barra de configuração, e o item some da lista (volta se a porta for removida do canvas).
- Sem host escolhido, a lista fica vazia com uma instrução ("Escolha um host acima para carregar a lista de portas") — a fonte de portas é sempre um host real via SNMP, conforme decidido; não há modo de entrada manual de portas nesta spec.

### 4. Origem da imagem
- **Upload de arquivo**: `<input type="file" accept="image/*">`, lido via `FileReader.readAsDataURL()` no navegador — vira base64 direto no cliente, sem round-trip ao servidor.
- **Colar URL**: campo de texto + botão "Carregar". Chama um novo endpoint `hostFace/fetchImage?url=...` (`HostFaceController::actionFetchImage`) que faz o download da imagem **no servidor** (evita bloqueio de CORS do navegador) e devolve `{dataUri: "data:image/png;base64,..."}`. Validações no servidor: `Content-Type` da resposta precisa começar com `image/`, tamanho máximo (5MB) — rejeita e mostra erro no editor caso contrário. Essa action fica nas `accessRules` do controller junto com `create`/`update` (só usuário autenticado `@`, mesma restrição que já existe pra essas duas ações — mitiga risco de SSRF por não ser endpoint público).
- Nos dois casos, o resultado final é sempre um data URI base64 embutido no SVG salvo (`<image xlink:href="data:image/...;base64,...">`) — igual ao formato das 6 faces já existentes. Nenhuma URL externa é referenciada no SVG final.

### 5. Reabrir uma face existente pra editar
- Ao abrir `hostFace/update` de uma face já existente, o editor faz o parse do `svg` salvo: extrai o `<image xlink:href="data:...">` (vira a imagem de fundo do canvas) e cada `<rect class="port" id="portN">` (posição, tamanho, id — viram portas já posicionadas no canvas). Como as 6 faces atuais foram desenhadas à mão no Inkscape, a implementação vai validar o parser contra os 6 SVGs reais (via um script de teste, não a UI) antes de considerar essa parte pronta — se algum desviar do padrão `<image>` + `<rect class="port" id="portN">`, essa face específica cai num modo de aviso ("não foi possível carregar visualmente, edite o SVG bruto") em vez de quebrar a tela.
- Se o parse falhar (SVG em formato inesperado), a tela mostra uma mensagem e oferece um textarea de fallback (o comportamento atual) só para esse caso — não é a experiência padrão, é uma rede de segurança.

## Fluxo de dados

Nenhuma migration de banco — `host_face.svg` continua sendo uma string de texto (o conteúdo dela é que passa a ser majoritariamente gerado pelo editor em vez de digitado). Único código novo no backend: `HostFaceController::actionFetchImage()`.

## Estados vazios / erros

- Sem host escolhido: lista lateral mostra "Escolha um host para carregar a lista de portas".
- Upload/URL falha (arquivo não é imagem, URL não responde, imagem maior que 5MB): mensagem de erro inline, canvas continua vazio.
- Parse de SVG existente falha: fallback pro textarea bruto (ver componente 5).

## Testes

Sem suíte automatizada de UI neste projeto (mesma limitação já registrada nas specs anteriores). Verificação:
- Script standalone validando o parser de SVG (extrair imagem + portas) contra os 6 SVGs reais do banco atual — esse é o teste automatizável real desta spec, roda sem navegador.
- Checklist manual no navegador: criar uma face nova do zero (upload de imagem, arrastar portas de um host real, salvar, reabrir e confirmar que carrega de volta certinho); testar colar URL de imagem; testar mover/remover porta já posicionada; abrir uma das 6 faces antigas e confirmar que carrega no editor.

## Fora de escopo

- Preencher automaticamente as 15 faces de switches identificadas no Zabbix — isso é o próximo passo, feito manualmente pelo usuário usando o editor depois que ele existir (fora desta spec).
- Detecção automática de posição de porta via IA/visão computacional na foto — é tudo manual (arrastar), por enquanto.
