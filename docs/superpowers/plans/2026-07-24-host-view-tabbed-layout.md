# Host View Tabbed Layout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reestruturar `protected/views/host/view.php` (tela de detalhe/manutenção de um equipamento) de uma tabela HTML monolítica + sidebar de links para um cabeçalho fixo (identidade + menu de ações) e 4 abas (Visão Geral, Documentação, Histórico, Diagnóstico), sem nenhuma mudança de banco de dados.

**Architecture:** `HostController::actionView()`/`actionViewByName()` passam a usar o layout de coluna única (`//layouts/column1`) em vez do padrão de duas colunas com sidebar "Operations" (`//layouts/column2`). O conteúdo da página é montado em `view.php` a partir de partials pequenos e focados: `_header.php` (identidade + dropdown de ações) e um `_tab*.php` por aba, amarrados com o widget `TbTabs` já disponível na extensão bootstrap do projeto.

**Tech Stack:** Yii 1.x (PHP 7.4), extensão `bootstrap.widgets` (Bootstrap 2.x) já usada no projeto — `TbTabs`, `TbDetailView`, `CHtml`. Sem JS novo (dropdown e tabs do Bootstrap 2 já carregados globalmente via `TbNav`/`TbBreadcrumb` no layout principal).

**Spec:** `docs/superpowers/specs/2026-07-24-host-view-tabbed-layout-design.md`

---

## Referência: estado atual de `protected/views/host/view.php`

```php
<?php
/* @var $this HostController */
/* @var $model Host */

$this->breadcrumbs=array(
	'Hosts'=>array('admin'),
	$model->name,
);

$this->pageTitle = $model ." ". $this->pageTitle;

$this->menu = array(
    array('label' => 'Web Config. ' . $model->name , 'url' => 'http://' . $model->ip, 'linkOptions' => array('target'=>'_blank')),
    array('label' => 'Show Host Connections', 'url' => array('host/connections','name' => $model->name )),
    array('label' => 'Show ARP Table', 'url' => array('host/arpTable', 'name' => $model->name )),
    array('label' => 'Show CAM Table', 'url' => array('host/camTable', 'name' => $model->name )),
    array('label' => 'Show Traffic', 'url' => array('host/traffic', 'name' => $model->name )),
    array('label' => 'Update Host', 'url' => array('update', 'id' => $model->id)),
    array('label' => 'Delete Host', 'url' => '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm' => 'Are you sure you want to delete this item?')),
    array('label' => 'Manage Host', 'url' => array('admin')),
    array('label' => 'Manage Host Faces', 'url' => array('hostFace/index')),
    array('label' => 'List Host', 'url' => array('index')),
    array('label' => 'Create Host', 'url' => array('create')),
);
?>
<table>
    <tr>
        <td>
            <?php

            $this->renderPartial('/map/_view', array(
                'height' => 300,
                'width' => 800,
                'navigation' => true,
                'dataUrl' => Yii::app()->createUrl('/map/listHosts?hostId=' . $model->id),
            ));
            ?>
        </td>
    </tr>

    <tr>
        <td>
            <?php $this->renderPartial('/host/_ports', array('model' => $model)); ?>
        </td>
    </tr>

    <tr>
        <td width="70%" style="vertical-align: top">
            <h3>Info:</h3>
            <?php
            $this->widget('bootstrap.widgets.TbDetailView', array(
                'data' => $model,
                'attributes' => array(
                        'id',
                        'name',
                        'type',
                        'mac',
                        'ip',
                        'snmpTemplate',
                        'InfoSerialNumber',
                        'InfoModel',
                        'InfoSystem',
                        'InfoUptime',
                        'InfoContact',
                        'InfoLocation', 
                ),
            ));
            ?>
        </td>
    </tr>
</table>
```

Este arquivo é usado tanto por `actionView($id)` quanto por `actionViewByName($name, $ip, $mac)` (ambos fazem `$this->render('view', array('model' => ...))`), então qualquer mudança nele cobre as duas rotas (`host/view/<id>` e `host/view/<name>`).

## File Structure

- Modify: `protected/controllers/HostController.php` — trocar layout para coluna única em `actionView`/`actionViewByName`
- Modify: `protected/views/host/view.php` — remove `$this->menu`, monta cabeçalho + mapa + portas + abas
- Create: `protected/views/host/_header.php` — identidade do equipamento + dropdown "Ações"
- Modify: `protected/models/Host.php` — adiciona `formatSnmpInfo()` (helper estático testável)
- Create: `protected/views/host/_tabOverview.php` — conteúdo da aba "Visão Geral" (era a tabela "Info:")
- Create: `protected/views/host/_tabDocs.php` — estado vazio da aba "Documentação"
- Create: `protected/views/host/_tabHistory.php` — estado vazio da aba "Histórico"
- Create: `protected/views/host/_tabDiagnostics.php` — cards de atalho da aba "Diagnóstico"
- Modify: `css/main.css` — regras novas: `.host-header`, `.diagnostic-cards`/`.diagnostic-card`, `.tab-empty-state`

---

### Task 1: Cabeçalho fixo (identidade + menu Ações) e layout de coluna única

**Files:**
- Modify: `protected/controllers/HostController.php:50-76`
- Create: `protected/views/host/_header.php`
- Modify: `protected/views/host/view.php`
- Modify: `css/main.css`

- [ ] **Step 1: Trocar o layout para coluna única nas duas actions que renderizam `view.php`**

Em `protected/controllers/HostController.php`, localizar:

```php
    public function actionView($id) {
        $this->render('view', array(
            'model' => $this->loadModel($id),
        ));
    }

    /**
     * Displays a particular model.
     * @param string $name the Name of the model to be displayed
     */
    public function actionViewByName($name = null, $ip = null, $mac = null) {
        try {
            $this->render('view', array(
                'model' => $this->loadModelByName($name, $ip, $mac),
            ));
        } catch (CHttpException $e) {
```

Substituir por:

```php
    public function actionView($id) {
        $this->layout = '//layouts/column1';
        $this->render('view', array(
            'model' => $this->loadModel($id),
        ));
    }

    /**
     * Displays a particular model.
     * @param string $name the Name of the model to be displayed
     */
    public function actionViewByName($name = null, $ip = null, $mac = null) {
        $this->layout = '//layouts/column1';
        try {
            $this->render('view', array(
                'model' => $this->loadModelByName($name, $ip, $mac),
            ));
        } catch (CHttpException $e) {
```

(O `catch` continua renderizando `addHostNotFound` — esse ainda usa o layout padrão de duas colunas do controller, `//layouts/column2`, porque a troca de `$this->layout` já foi feita antes do `try`. Isso é aceitável: a página de "host não encontrado" pode continuar com o layout antigo, não faz parte desta spec. Se quiser manter *exatamente* o comportamento antigo nesse caso de erro, mova a linha `$this->layout = '//layouts/column1';` para dentro do `try`, logo antes do `$this->render('view', ...)`.)

- [ ] **Step 2: Criar `protected/views/host/_header.php`**

```php
<?php
/* @var $this HostController */
/* @var $model Host */
?>
<div class="host-header">
    <div class="host-header-identity">
        <span class="view host-type <?php echo CHtml::encode($model->type); ?>"><?php echo CHtml::encode($model->name); ?></span>
        <span class="host-header-ip"><?php echo CHtml::encode($model->ip); ?></span>
    </div>
    <div class="btn-group host-header-actions">
        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
            Ações
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu pull-right">
            <li><?php echo CHtml::link('Web Config', 'http://' . $model->ip, array('target' => '_blank')); ?></li>
            <li><?php echo CHtml::link('Update Host', array('update', 'id' => $model->id)); ?></li>
            <li class="divider"></li>
            <li><?php echo CHtml::link('Delete Host', '#', array(
                'submit' => array('delete', 'id' => $model->id),
                'confirm' => 'Are you sure you want to delete this item?',
            )); ?></li>
        </ul>
    </div>
</div>
```

- [ ] **Step 3: Adicionar CSS do cabeçalho em `css/main.css`**

Adicionar ao final do arquivo:

```css
/* host/view header */
.host-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.host-header-ip {
    color: #999;
    margin-left: 8px;
    font-size: 12px;
}
```

- [ ] **Step 4: Ligar o cabeçalho em `view.php` e remover a sidebar antiga (`$this->menu`)**

Em `protected/views/host/view.php`, substituir o bloco inicial (do `$this->breadcrumbs` até o `?>` antes do `<table>`):

```php
$this->menu = array(
    array('label' => 'Web Config. ' . $model->name , 'url' => 'http://' . $model->ip, 'linkOptions' => array('target'=>'_blank')),
    array('label' => 'Show Host Connections', 'url' => array('host/connections','name' => $model->name )),
    array('label' => 'Show ARP Table', 'url' => array('host/arpTable', 'name' => $model->name )),
    array('label' => 'Show CAM Table', 'url' => array('host/camTable', 'name' => $model->name )),
    array('label' => 'Show Traffic', 'url' => array('host/traffic', 'name' => $model->name )),
    array('label' => 'Update Host', 'url' => array('update', 'id' => $model->id)),
    array('label' => 'Delete Host', 'url' => '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm' => 'Are you sure you want to delete this item?')),
    array('label' => 'Manage Host', 'url' => array('admin')),
    array('label' => 'Manage Host Faces', 'url' => array('hostFace/index')),
    array('label' => 'List Host', 'url' => array('index')),
    array('label' => 'Create Host', 'url' => array('create')),
);
?>
<table>
```

por:

```php
?>
<?php $this->renderPartial('/host/_header', array('model' => $model)); ?>
<table>
```

O restante do arquivo (mapa, portas, tabela "Info:") fica **inalterado** por enquanto — será substituído no Task 5.

- [ ] **Step 5: Verificar sintaxe PHP**

Rodar:
```bash
php -l protected/controllers/HostController.php
php -l protected/views/host/view.php
php -l protected/views/host/_header.php
```
Esperado: `No syntax errors detected` nos três.

- [ ] **Step 6: Verificação manual no navegador**

Abrir `host/view/<algum host com snmpTemplate>` (localmente ou no ambiente de testes já usado nesta conversa) e confirmar:
- A sidebar "Operations" sumiu, a página ocupa a largura toda.
- No topo aparece o nome do host com o ícone do tipo (reaproveitando a classe `.view.host-type.<tipo>` já usada no mapa/lista de hosts) + IP, e um botão "Ações" à direita.
- Clicar em "Ações" abre um dropdown com Web Config / Update Host / Delete Host.
- "Update Host" e "Web Config" levam pros lugares certos; "Delete Host" mostra o confirm do navegador (não precisa confirmar de verdade no teste).
- Mapa, portas e a tabela "Info:" continuam aparecendo abaixo, exatamente como antes.

- [ ] **Step 7: Commit**

```bash
git add protected/controllers/HostController.php protected/views/host/view.php protected/views/host/_header.php css/main.css
git commit -m "Adicionar cabeçalho fixo com menu de ações e trocar host/view para layout de coluna única"
```

---

### Task 2: Aba "Visão Geral" com tratamento de campos SNMP vazios

**Files:**
- Modify: `protected/models/Host.php`
- Create: `protected/views/host/_tabOverview.php`
- Test: script standalone (não há PHPUnit funcional neste projeto — ver Step 2)

- [ ] **Step 1: Adicionar `Host::formatSnmpInfo()`**

Em `protected/models/Host.php`, adicionar este método logo após `macFromOidSuffix()` (método privado adicionado mais cedo nesta mesma sessão, ver `git log` — fica na mesma área de helpers do model):

```php
    /**
     * Formata um valor obtido via SNMP para exibição na tela do equipamento,
     * deixando claro quando é o próprio equipamento que não relatou aquele
     * dado (em vez do "Not set" genérico do Yii, usado também por atributos
     * de banco vazios como mac/ip).
     * @param mixed $value
     * @return string
     */
    public static function formatSnmpInfo($value) {
        if ($value === null || $value === '') {
            return '<span class="muted">não informado via SNMP</span>';
        }
        return CHtml::encode($value);
    }
```

- [ ] **Step 2: Validar a lógica com um script standalone**

Este projeto não tem um bootstrap de PHPUnit funcional para os models (só testes funcionais Selenium, que não rodam neste ambiente). Seguindo o mesmo padrão já usado nesta sessão para validar lógica pura sem depender do framework Yii inteiro, criar um script temporário:

```bash
cat > /tmp/test_format_snmp_info.php << 'EOF'
<?php
// Cópia isolada da lógica de Host::formatSnmpInfo() para validar sem bootstrap do Yii.
function formatSnmpInfo($value) {
    if ($value === null || $value === '') {
        return '<span class="muted">não informado via SNMP</span>';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$cases = [
    [null, '<span class="muted">não informado via SNMP</span>'],
    ['', '<span class="muted">não informado via SNMP</span>'],
    ['S5720-56C-EI-48S-AC', 'S5720-56C-EI-48S-AC'],
    ['<script>', '&lt;script&gt;'],
];

$failures = 0;
foreach ($cases as [$input, $expected]) {
    $actual = formatSnmpInfo($input);
    $ok = $actual === $expected;
    if (!$ok) $failures++;
    printf("%s input=%s => %s\n", $ok ? 'OK  ' : 'FAIL', var_export($input, true), $actual);
}
exit($failures > 0 ? 1 : 0);
EOF
php /tmp/test_format_snmp_info.php
rm /tmp/test_format_snmp_info.php
```

Esperado: 4 linhas `OK`, exit code 0. Se algum `FAIL` aparecer, ajustar `Host::formatSnmpInfo()` até bater (TDD: o script já reflete o comportamento esperado antes de existir o método real no model — depois de rodar aqui e confirmar a lógica, o Step 1 acima é o que fica no código de produção).

- [ ] **Step 3: Criar `protected/views/host/_tabOverview.php`**

```php
<?php
/* @var $this HostController */
/* @var $model Host */
?>
<?php $this->widget('bootstrap.widgets.TbDetailView', array(
    'data' => $model,
    'attributes' => array(
        'id',
        'name',
        'type',
        'mac',
        'ip',
        'snmpTemplate',
        array(
            'name' => 'InfoSerialNumber',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoSerialNumber); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoModel',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoModel); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoSystem',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoSystem); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoUptime',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoUptime); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoContact',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoContact); },
            'type' => 'raw',
        ),
        array(
            'name' => 'InfoLocation',
            'value' => function ($data) { return Host::formatSnmpInfo($data->InfoLocation); },
            'type' => 'raw',
        ),
    ),
)); ?>
```

Nota: `id`, `name`, `type`, `mac`, `ip`, `snmpTemplate` continuam como strings simples (sem `value` customizado) — esses são atributos de banco, não de SNMP, então mantêm o `nullDisplay` padrão do Yii ("Not set") quando vazios, como já documentado na spec.

- [ ] **Step 4: Verificar sintaxe PHP**

```bash
php -l protected/models/Host.php
php -l protected/views/host/_tabOverview.php
```
Esperado: `No syntax errors detected` nos dois.

Este arquivo ainda não está referenciado em `view.php` — isso acontece no Task 5. Não há o que verificar no navegador ainda.

- [ ] **Step 5: Commit**

```bash
git add protected/models/Host.php protected/views/host/_tabOverview.php
git commit -m "Adicionar Host::formatSnmpInfo() e partial da aba Visão Geral"
```

---

### Task 3: Aba "Diagnóstico" (cards de atalho)

**Files:**
- Create: `protected/views/host/_tabDiagnostics.php`
- Modify: `css/main.css`

- [ ] **Step 1: Criar `protected/views/host/_tabDiagnostics.php`**

```php
<?php
/* @var $this HostController */
/* @var $model Host */
?>
<div class="diagnostic-cards">
    <?php echo CHtml::link(
        '<strong>ARP Table</strong><br/>Mapeamento IP ↔ MAC visto por este host',
        array('host/arpTable', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>CAM Table</strong><br/>MACs aprendidos em cada porta',
        array('host/camTable', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>Tráfego</strong><br/>Gráfico de tráfego por porta',
        array('host/traffic', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>Conexões</strong><br/>Hosts conectados a este equipamento',
        array('host/connections', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
</div>
```

- [ ] **Step 2: Adicionar CSS dos cards em `css/main.css`**

Adicionar ao final do arquivo:

```css
/* host/view - aba Diagnóstico */
.diagnostic-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.diagnostic-card {
    display: block;
    padding: 15px;
    min-width: 160px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    text-decoration: none;
    color: inherit;
}

.diagnostic-card:hover {
    background-color: #f5f5f5;
    text-decoration: none;
}
```

- [ ] **Step 3: Verificar sintaxe PHP**

```bash
php -l protected/views/host/_tabDiagnostics.php
```
Esperado: `No syntax errors detected`.

Este arquivo ainda não está referenciado em `view.php` — isso acontece no Task 5.

- [ ] **Step 4: Commit**

```bash
git add protected/views/host/_tabDiagnostics.php css/main.css
git commit -m "Adicionar partial da aba Diagnóstico (cards de atalho pras telas existentes)"
```

---

### Task 4: Abas "Documentação" e "Histórico" (estado vazio)

**Files:**
- Create: `protected/views/host/_tabDocs.php`
- Create: `protected/views/host/_tabHistory.php`
- Modify: `css/main.css`

- [ ] **Step 1: Criar `protected/views/host/_tabDocs.php`**

```php
<?php
/* @var $this HostController */
/* @var $model Host */
?>
<div class="tab-empty-state">
    <p>Nenhuma nota registrada ainda.</p>
    <p class="tab-empty-hint">(recurso chega numa próxima etapa)</p>
</div>
```

- [ ] **Step 2: Criar `protected/views/host/_tabHistory.php`**

```php
<?php
/* @var $this HostController */
/* @var $model Host */
?>
<div class="tab-empty-state">
    <p>Nenhum registro de manutenção ainda.</p>
    <p class="tab-empty-hint">(recurso chega numa próxima etapa)</p>
</div>
```

- [ ] **Step 3: Adicionar CSS do estado vazio em `css/main.css`**

Adicionar ao final do arquivo:

```css
/* host/view - estado vazio (Documentação / Histórico) */
.tab-empty-state {
    text-align: center;
    padding: 30px 10px;
    color: #999;
}

.tab-empty-state .tab-empty-hint {
    font-size: 12px;
}
```

- [ ] **Step 4: Verificar sintaxe PHP**

```bash
php -l protected/views/host/_tabDocs.php
php -l protected/views/host/_tabHistory.php
```
Esperado: `No syntax errors detected` nos dois.

Esses arquivos ainda não estão referenciados em `view.php` — isso acontece no Task 5.

- [ ] **Step 5: Commit**

```bash
git add protected/views/host/_tabDocs.php protected/views/host/_tabHistory.php css/main.css
git commit -m "Adicionar partials de estado vazio das abas Documentação e Histórico"
```

---

### Task 5: Montar as 4 abas em `view.php` (integração final)

**Files:**
- Modify: `protected/views/host/view.php`

- [ ] **Step 1: Substituir a tabela antiga pelo widget `TbTabs`**

Em `protected/views/host/view.php`, o conteúdo (após o Task 1) deve estar assim:

```php
<?php $this->renderPartial('/host/_header', array('model' => $model)); ?>
<table>
    <tr>
        <td>
            <?php

            $this->renderPartial('/map/_view', array(
                'height' => 300,
                'width' => 800,
                'navigation' => true,
                'dataUrl' => Yii::app()->createUrl('/map/listHosts?hostId=' . $model->id),
            ));
            ?>
        </td>
    </tr>

    <tr>
        <td>
            <?php $this->renderPartial('/host/_ports', array('model' => $model)); ?>
        </td>
    </tr>

    <tr>
        <td width="70%" style="vertical-align: top">
            <h3>Info:</h3>
            <?php
            $this->widget('bootstrap.widgets.TbDetailView', array(
                'data' => $model,
                'attributes' => array(
                        'id',
                        'name',
                        'type',
                        'mac',
                        'ip',
                        'snmpTemplate',
                        'InfoSerialNumber',
                        'InfoModel',
                        'InfoSystem',
                        'InfoUptime',
                        'InfoContact',
                        'InfoLocation', 
                ),
            ));
            ?>
        </td>
    </tr>
</table>
```

Substituir esse bloco inteiro (do `<table>` até `</table>`) por:

```php
<?php

$this->renderPartial('/map/_view', array(
    'height' => 300,
    'width' => 800,
    'navigation' => true,
    'dataUrl' => Yii::app()->createUrl('/map/listHosts?hostId=' . $model->id),
));

$this->renderPartial('/host/_ports', array('model' => $model));

$this->widget('bootstrap.widgets.TbTabs', array(
    // 'type' não precisa ser passado — o default de TbTabs já é 'tabs' (ver
    // protected/extensions/bootstrap/widgets/TbTabs.php:19).
    'tabs' => array(
        array(
            'label' => 'Visão Geral',
            'active' => true,
            'view' => '/host/_tabOverview',
        ),
        array(
            'label' => 'Documentação',
            'view' => '/host/_tabDocs',
        ),
        array(
            'label' => 'Histórico',
            'view' => '/host/_tabHistory',
        ),
        array(
            'label' => 'Diagnóstico',
            'view' => '/host/_tabDiagnostics',
        ),
    ),
    'viewData' => array('model' => $model),
));

?>
```

O arquivo completo (`protected/views/host/view.php`) fica:

```php
<?php
/* @var $this HostController */
/* @var $model Host */

$this->breadcrumbs=array(
	'Hosts'=>array('admin'),
	$model->name,
);

$this->pageTitle = $model ." ". $this->pageTitle;
?>
<?php $this->renderPartial('/host/_header', array('model' => $model)); ?>
<?php

$this->renderPartial('/map/_view', array(
    'height' => 300,
    'width' => 800,
    'navigation' => true,
    'dataUrl' => Yii::app()->createUrl('/map/listHosts?hostId=' . $model->id),
));

$this->renderPartial('/host/_ports', array('model' => $model));

$this->widget('bootstrap.widgets.TbTabs', array(
    // 'type' não precisa ser passado — o default de TbTabs já é 'tabs' (ver
    // protected/extensions/bootstrap/widgets/TbTabs.php:19).
    'tabs' => array(
        array(
            'label' => 'Visão Geral',
            'active' => true,
            'view' => '/host/_tabOverview',
        ),
        array(
            'label' => 'Documentação',
            'view' => '/host/_tabDocs',
        ),
        array(
            'label' => 'Histórico',
            'view' => '/host/_tabHistory',
        ),
        array(
            'label' => 'Diagnóstico',
            'view' => '/host/_tabDiagnostics',
        ),
    ),
    'viewData' => array('model' => $model),
));

?>
```

- [ ] **Step 2: Verificar sintaxe PHP**

```bash
php -l protected/views/host/view.php
```
Esperado: `No syntax errors detected`.

- [ ] **Step 3: Verificação manual no navegador — checklist completo da spec**

Repetir os testes descritos na seção "Testes" da spec (`docs/superpowers/specs/2026-07-24-host-view-tabbed-layout-design.md`):

1. **Host com SNMP funcionando normalmente** (ex.: `host/view/localhost`): mapa e portas continuam aparecendo acima das abas, exatamente como antes. Aba "Visão Geral" ativa por padrão, mostrando os mesmos dados que a tabela "Info:" antiga mostrava.
2. **Host com campos SNMP vazios** (Serial/Model tipicamente "Not set" — ex.: um switch real como `terra1`): na aba "Visão Geral", `InfoSerialNumber`/`InfoModel` mostram "não informado via SNMP" em vez de "Not set"; `mac`/`ip` (se vazios) continuam mostrando o "Not set" padrão do Yii.
3. **Menu "Ações"**: Update Host, Delete Host (confirm) e Web Config continuam funcionando (testado no Task 1, confirmar que sobrevive à mudança deste task).
4. **Aba "Diagnóstico"**: os 4 cards (ARP Table, CAM Table, Tráfego, Conexões) levam pras páginas corretas.
5. **Abas "Documentação" e "Histórico"**: mostram a mensagem de estado vazio.
6. Clicar entre as 4 abas funciona (JS do Bootstrap 2 já carregado globalmente, sem necessidade de registrar script novo).

- [ ] **Step 4: Commit**

```bash
git add protected/views/host/view.php
git commit -m "Integrar as 4 abas em host/view, substituindo a tabela Info antiga"
```

---

## Fora de escopo (não faz parte deste plano)

Conforme a spec: conteúdo real das abas "Documentação" (notas, campos estruturados de local) e "Histórico" (log de manutenção datado), notas por porta, e captura de foto + GPS. Cada um vira uma spec e um plano próprios mais adiante.
