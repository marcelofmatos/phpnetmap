# Host Face Visual Editor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Substituir o campo de texto puro (`<textarea>`) do formulário de Host Face por um editor visual: escolher um host real (via SNMP), arrastar retângulos de porta pré-numerados de uma lista lateral para cima de uma foto do switch, e salvar automaticamente o SVG resultante — sem editar XML à mão.

**Architecture:** Duas camadas de JS puro (sem framework/bundler novo, consistente com o resto do app): `js/hostFaceSvg.js` (funções puras de gerar/interpretar SVG, testáveis via Node sem navegador) e `js/hostFaceEditor.js` (interação de UI: drag-and-drop, canvas, lista lateral, chama as funções puras). O `<textarea>` de `protected/views/hostFace/_form.php` vira um `<input type="hidden">` que o JS mantém sincronizado — o `HostFaceController` não muda nada em `actionCreate`/`actionUpdate`. Único código novo de backend: `HostFaceController::actionFetchImage()`, que baixa uma imagem por URL no servidor (evita bloqueio de CORS do navegador) e devolve como base64.

**Tech Stack:** JavaScript puro (ES5, sem build step — mesma convenção do resto do app), HTML5 Drag and Drop API nativa, PHP/Yii 1.x (`HostFaceController`), Node.js só para rodar o script de teste standalone de `hostFaceSvg.js` (não faz parte do runtime da aplicação).

**Spec:** `docs/superpowers/specs/2026-07-24-host-face-visual-editor-design.md`

---

## Referência: estrutura atual

`protected/models/HostFace.php`: `id`, `name`, `svg` (texto). `protected/views/hostFace/_form.php` hoje tem 3 campos: `name` (texto), `svg` (`<textarea>`), `hosts` (multi-select, já funciona, **não mexer**). `HostFaceController::actionCreate()`/`actionUpdate()` já leem `$_POST['HostFace']['svg']` como string — continuam iguais.

As 6 faces já cadastradas seguem o padrão: um `<image xlink:href="data:image/...;base64,...">` (a foto) + vários `<rect class="port" id="port<N>" x=".." y=".." width=".." height=".." />` (um por porta). Fixtures reais dessas 6 faces já estão em `protected/tests/fixtures/host_faces/*.svg` (exportadas do banco de produção nesta sessão) — **não precisa buscar de novo, já estão no repo**:

| Arquivo | Portas |
|---|---|
| `3Com_Baseline_Switch_2928-SFP.svg` | 28 |
| `HP_V1910-48G.svg` | 52 |
| `H3C_S5500EI.svg` | 32 |
| `Procurve_J9279A.svg` | 24 |
| `Procurve_J9280A.svg` | 48 |
| `HUAWEI_S5700.svg` | 28 |

O endpoint `host/loadPortInfo/<id>` **já existe** (`HostController::actionLoadPortInfo`) e devolve JSON: `[{"ifIndex": 1, "ifDescr": "GigabitEthernet0/1", "ifAlias": "...", ...}, ...]` — é a mesma consulta usada em `host/view`.

## File Structure

- Create: `js/hostFaceSvg.js` — funções puras: montar SVG a partir de portas, interpretar SVG existente de volta em portas
- Create: `protected/tests/js/test_host_face_svg.js` — script de validação standalone (roda com `node`, sem dependências)
- Modify: `protected/controllers/HostFaceController.php` — nova action `actionFetchImage()`, adiciona ela às `accessRules`
- Create: `protected/views/hostFace/jsonFetchImage.php` — resposta JSON de `actionFetchImage`
- Create: `protected/views/hostFace/jsonError.php` — resposta JSON de erro de `actionFetchImage`
- Modify: `protected/views/hostFace/_form.php` — substitui o `<textarea>` do campo `svg` pelo markup do editor, registra os dois arquivos JS
- Create: `js/hostFaceEditor.js` — interação: escolher host, carregar/renderizar lista de portas, drag-and-drop pro canvas, mover/remover porta já posicionada, upload/URL de imagem, sincroniza o campo escondido
- Modify: `css/main.css` — estilos do editor (`.host-face-editor-*`)

---

### Task 1: `js/hostFaceSvg.js` — gerar e interpretar SVG (funções puras, testadas via Node)

**Files:**
- Create: `js/hostFaceSvg.js`
- Test: `protected/tests/js/test_host_face_svg.js`

- [ ] **Step 1: Criar `js/hostFaceSvg.js`**

```javascript
// js/hostFaceSvg.js
// Funções puras (sem DOM) para montar e interpretar o SVG de uma Host Face.
// Funciona tanto no navegador (expõe window.HostFaceSvg) quanto no Node
// (module.exports), pra dar pra testar sem navegador.

(function () {
    'use strict';

    function extractAttr(tagText, attrName) {
        var re = new RegExp(attrName + '="([^"]*)"');
        var match = tagText.match(re);
        return match ? match[1] : null;
    }

    function extractFirstTag(svgString, tagName) {
        var re = new RegExp('<' + tagName + '\\b[^>]*/?>', 'i');
        var match = svgString.match(re);
        return match ? match[0] : null;
    }

    function extractAllTags(svgString, tagName) {
        var re = new RegExp('<' + tagName + '\\b[^>]*/?>', 'gi');
        return svgString.match(re) || [];
    }

    /**
     * Monta o SVG final a partir da imagem de fundo e da lista de portas
     * posicionadas.
     * @param {string} imageDataUri data URI da imagem (data:image/...;base64,...)
     * @param {number} imageWidth
     * @param {number} imageHeight
     * @param {Array<{id:string,x:number,y:number,width:number,height:number}>} ports
     * @returns {string} SVG completo
     */
    function buildSvgFromPorts(imageDataUri, imageWidth, imageHeight, ports) {
        var portRects = ports.map(function (p) {
            return '<rect class="port" id="port' + p.id + '" x="' + p.x + '" y="' + p.y +
                '" width="' + p.width + '" height="' + p.height + '" />';
        }).join('\n  ');

        return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="' + imageWidth + '" height="' + imageHeight + '">\n' +
            '  <image xlink:href="' + imageDataUri + '" x="0" y="0" width="' + imageWidth + '" height="' + imageHeight + '" />\n' +
            (portRects ? '  ' + portRects + '\n' : '') +
            '</svg>';
    }

    /**
     * Faz o caminho inverso: a partir de um SVG (novo, gerado por
     * buildSvgFromPorts, ou antigo, desenhado à mão no Inkscape), extrai a
     * imagem de fundo e as portas já posicionadas.
     * @param {string} svgString
     * @returns {{imageDataUri:string,imageWidth:number,imageHeight:number,ports:Array}|null}
     *          null se o SVG não seguir o formato esperado (image + rect.port)
     */
    function parseSvgToPorts(svgString) {
        if (typeof svgString !== 'string' || svgString.indexOf('<svg') === -1) {
            return null;
        }

        var imageTag = extractFirstTag(svgString, 'image');
        if (!imageTag) {
            return null;
        }

        var imageDataUri = extractAttr(imageTag, 'xlink:href') || extractAttr(imageTag, 'href');
        var imageWidth = parseFloat(extractAttr(imageTag, 'width'));
        var imageHeight = parseFloat(extractAttr(imageTag, 'height'));

        if (!imageDataUri || isNaN(imageWidth) || isNaN(imageHeight)) {
            return null;
        }

        var rectTags = extractAllTags(svgString, 'rect').filter(function (tag) {
            return tag.indexOf('class="port"') !== -1;
        });

        var ports = [];
        for (var i = 0; i < rectTags.length; i++) {
            var tag = rectTags[i];
            var id = extractAttr(tag, 'id');
            var x = parseFloat(extractAttr(tag, 'x'));
            var y = parseFloat(extractAttr(tag, 'y'));
            var width = parseFloat(extractAttr(tag, 'width'));
            var height = parseFloat(extractAttr(tag, 'height'));

            if (!id || id.indexOf('port') !== 0 || isNaN(x) || isNaN(y) || isNaN(width) || isNaN(height)) {
                return null; // formato inesperado nessa porta — falha o parse inteiro
            }

            ports.push({
                id: id.substring(4), // remove o prefixo "port"
                x: x,
                y: y,
                width: width,
                height: height
            });
        }

        return {
            imageDataUri: imageDataUri,
            imageWidth: imageWidth,
            imageHeight: imageHeight,
            ports: ports
        };
    }

    var HostFaceSvg = {
        buildSvgFromPorts: buildSvgFromPorts,
        parseSvgToPorts: parseSvgToPorts
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = HostFaceSvg;
    } else if (typeof window !== 'undefined') {
        window.HostFaceSvg = HostFaceSvg;
    }
})();
```

- [ ] **Step 2: Criar o script de teste standalone**

```javascript
// protected/tests/js/test_host_face_svg.js
// Roda com: node protected/tests/js/test_host_face_svg.js
var fs = require('fs');
var path = require('path');
var assert = require('assert');
var HostFaceSvg = require('../../../js/hostFaceSvg.js');

var failures = 0;

function check(label, fn) {
    try {
        fn();
        console.log('OK   ' + label);
    } catch (err) {
        console.log('FAIL ' + label + ': ' + err.message);
        failures++;
    }
}

// 1. Round-trip sintético: montar e reler deve dar os mesmos dados de volta.
check('round-trip sintetico', function () {
    var built = HostFaceSvg.buildSvgFromPorts(
        'data:image/png;base64,AAAA',
        100,
        50,
        [
            { id: '1', x: 10, y: 5, width: 20, height: 15 },
            { id: '2', x: 35, y: 5, width: 20, height: 15 }
        ]
    );
    var parsed = HostFaceSvg.parseSvgToPorts(built);
    assert.ok(parsed, 'parse não deveria retornar null');
    assert.strictEqual(parsed.imageDataUri, 'data:image/png;base64,AAAA');
    assert.strictEqual(parsed.imageWidth, 100);
    assert.strictEqual(parsed.imageHeight, 50);
    assert.strictEqual(parsed.ports.length, 2);
    assert.strictEqual(parsed.ports[0].id, '1');
    assert.strictEqual(parsed.ports[0].x, 10);
    assert.strictEqual(parsed.ports[1].id, '2');
});

// 2. SVG sem imagem válida deve falhar o parse (retornar null), não lançar exceção.
check('svg sem imagem retorna null', function () {
    var parsed = HostFaceSvg.parseSvgToPorts('<svg width="10" height="10"></svg>');
    assert.strictEqual(parsed, null);
});

// 3. String que não é SVG retorna null.
check('string vazia retorna null', function () {
    assert.strictEqual(HostFaceSvg.parseSvgToPorts(''), null);
    assert.strictEqual(HostFaceSvg.parseSvgToPorts('não é svg'), null);
});

// 4. Parse contra as 6 faces reais já cadastradas em produção (fixtures no repo).
var fixturesDir = path.join(__dirname, '..', 'fixtures', 'host_faces');
var expectedPortCount = {
    '3Com_Baseline_Switch_2928-SFP.svg': 28,
    'HP_V1910-48G.svg': 52,
    'H3C_S5500EI.svg': 32,
    'Procurve_J9279A.svg': 24,
    'Procurve_J9280A.svg': 48,
    'HUAWEI_S5700.svg': 28
};

Object.keys(expectedPortCount).forEach(function (file) {
    check('fixture real: ' + file, function () {
        var svg = fs.readFileSync(path.join(fixturesDir, file), 'utf8');
        var result = HostFaceSvg.parseSvgToPorts(svg);
        assert.ok(result, 'parse retornou null pra ' + file);
        assert.strictEqual(result.ports.length, expectedPortCount[file],
            'esperava ' + expectedPortCount[file] + ' portas, achou ' + result.ports.length);
        assert.strictEqual(result.imageDataUri.indexOf('data:image/'), 0,
            'imageDataUri não parece um data URI de imagem');
    });
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
```

- [ ] **Step 3: Rodar o teste e confirmar que passa**

```bash
node protected/tests/js/test_host_face_svg.js
```

Esperado: todas as linhas `OK`, terminando com "Todos os testes passaram." e exit code 0. **Isto é o requisito real da task** — se alguma das 6 fixtures reais falhar o parse, ajuste a lógica de `parseSvgToPorts` (não as fixtures, que são dados reais de produção) até bater. Rode `echo $?` depois pra confirmar o exit code 0.

- [ ] **Step 4: Verificar sintaxe JS**

```bash
node -c js/hostFaceSvg.js
node -c protected/tests/js/test_host_face_svg.js
```
Esperado: nenhuma saída (sintaxe OK) nos dois.

- [ ] **Step 5: Commit**

```bash
git add js/hostFaceSvg.js protected/tests/js/test_host_face_svg.js
git -c user.name="Marcelo Matos" -c user.email="contato@marcelomatos.dev" commit -m "Adicionar funções puras de montar/interpretar SVG de Host Face"
```

**IMPORTANTE sobre autoria do commit:** este projeto tem uma regra global: commits NUNCA podem ter trailer `Co-Authored-By` nem usar o email da conta Claude. Use exatamente o comando acima (identidade `Marcelo Matos <contato@marcelomatos.dev>`), sem trailer de coautoria.

---

### Task 2: `HostFaceController::actionFetchImage()` — baixar imagem por URL no servidor

**Files:**
- Modify: `protected/controllers/HostFaceController.php`
- Create: `protected/views/hostFace/jsonFetchImage.php`
- Create: `protected/views/hostFace/jsonError.php`

- [ ] **Step 1: Adicionar `actionFetchImage()` ao controller**

Em `protected/controllers/HostFaceController.php`, adicionar este método (pode ficar logo depois de `actionDelete()`):

```php
	/**
	 * Baixa uma imagem a partir de uma URL informada pelo usuário e devolve
	 * como data URI base64 — feito no servidor (não no navegador) porque o
	 * navegador bloquearia por CORS ao tentar isso direto num domínio de
	 * terceiro (ex.: site do fabricante do switch).
	 */
	public function actionFetchImage()
	{
		$this->layout = '//layouts/json';

		$url = isset($_GET['url']) ? trim($_GET['url']) : '';

		if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
			$this->render('jsonError', array('error' => 'URL inválida.'));
			return;
		}

		$context = stream_context_create(array(
			'http' => array('timeout' => 10, 'follow_location' => 1, 'max_redirects' => 3),
			'https' => array('timeout' => 10),
		));

		$imageData = @file_get_contents($url, false, $context);

		if ($imageData === false) {
			$this->render('jsonError', array('error' => 'Não foi possível baixar a imagem dessa URL.'));
			return;
		}

		if (strlen($imageData) > 5 * 1024 * 1024) {
			$this->render('jsonError', array('error' => 'A imagem tem mais de 5MB.'));
			return;
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mimeType = $finfo->buffer($imageData);

		if (strpos($mimeType, 'image/') !== 0) {
			$this->render('jsonError', array('error' => 'Essa URL não aponta para uma imagem (tipo detectado: ' . $mimeType . ').'));
			return;
		}

		$dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

		$this->render('jsonFetchImage', array('dataUri' => $dataUri));
	}
```

- [ ] **Step 2: Adicionar `fetchImage` em `accessRules()`**

Em `protected/controllers/HostFaceController.php`, dentro de `accessRules()`, localizar:

```php
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
```

Substituir por:

```php
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update','fetchImage'),
				'users'=>array('@'),
			),
```

(`fetchImage` fica restrito a usuário autenticado — mesma regra de `create`/`update` — por ser um endpoint que faz requisição HTTP a partir do servidor a uma URL arbitrária; não deve ficar público.)

- [ ] **Step 3: Criar as duas views JSON**

`protected/views/hostFace/jsonFetchImage.php`:

```php
<?php
echo CJSON::encode(array('dataUri' => $dataUri));
```

`protected/views/hostFace/jsonError.php`:

```php
<?php
echo CJSON::encode(array('error' => $error));
```

- [ ] **Step 4: Verificar sintaxe PHP**

```bash
php -l protected/controllers/HostFaceController.php
php -l protected/views/hostFace/jsonFetchImage.php
php -l protected/views/hostFace/jsonError.php
```
Esperado: `No syntax errors detected` nos três.

Esta action ainda não é chamada por nenhuma tela (isso acontece na Task 4) — não há como testar via navegador ainda. Se quiser confirmar que responde, pode testar direto por linha de comando contra uma instância rodando (não obrigatório nesta task):
```bash
curl -s "http://localhost/index.php?r=hostFace/fetchImage&url=https://example.com/nao-e-imagem"
# esperado: {"error":"..."} (porque example.com não devolve uma imagem)
```

- [ ] **Step 5: Commit**

```bash
git add protected/controllers/HostFaceController.php protected/views/hostFace/jsonFetchImage.php protected/views/hostFace/jsonError.php
git -c user.name="Marcelo Matos" -c user.email="contato@marcelomatos.dev" commit -m "Adicionar endpoint hostFace/fetchImage para baixar imagem por URL no servidor"
```

---

### Task 3: Markup do editor em `_form.php` + CSS

**Files:**
- Modify: `protected/views/hostFace/_form.php`
- Modify: `css/main.css`

- [ ] **Step 1: Substituir o campo `svg` em `_form.php`**

Em `protected/views/hostFace/_form.php`, localizar:

```php
	<div class="row">
		<?php echo $form->labelEx($model,'svg'); ?>
		<?php echo $form->textArea($model,'svg',array('rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'svg'); ?>
	</div>
```

Substituir por:

```php
	<div class="row">
		<?php echo $form->labelEx($model,'svg'); ?>

		<div id="host-face-editor">
			<div class="host-face-editor-toolbar">
				<label>Host de origem (SNMP):
					<select id="hfe-host-select">
						<option value="">-- escolher --</option>
						<?php foreach (Host::model()->findAll() as $hostOption): ?>
							<option value="<?php echo $hostOption->id; ?>"><?php echo CHtml::encode($hostOption->name); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Tamanho da porta:
					<input type="number" id="hfe-port-width" value="22" min="1" style="width:60px" /> x
					<input type="number" id="hfe-port-height" value="18" min="1" style="width:60px" /> px
				</label>
			</div>

			<div class="host-face-editor-image-source">
				<label>Upload de imagem: <input type="file" id="hfe-image-upload" accept="image/*" /></label>
				<label>ou URL da imagem: <input type="text" id="hfe-image-url" style="width:300px" placeholder="https://..." /></label>
				<button type="button" id="hfe-image-url-load">Carregar</button>
				<span id="hfe-image-status"></span>
			</div>

			<div class="host-face-editor-body">
				<div id="hfe-canvas" class="host-face-editor-canvas">
					<p class="host-face-editor-empty-hint">Envie ou cole a URL de uma imagem do switch para começar.</p>
				</div>
				<div id="hfe-palette" class="host-face-editor-palette">
					<p class="host-face-editor-empty-hint">Escolha um host acima para carregar a lista de portas.</p>
				</div>
			</div>
		</div>

		<div id="hfe-fallback" class="host-face-editor-fallback" style="display:none">
			<p class="host-face-editor-empty-hint">Não foi possível interpretar o SVG existente desta face no editor visual. Você pode editar o SVG bruto abaixo (fica igual ao campo de texto antigo) — as mudanças aqui são salvas normalmente.</p>
			<textarea id="hfe-fallback-textarea" rows="10" style="width:100%"></textarea>
		</div>

		<?php echo $form->hiddenField($model,'svg',array('id'=>'hfe-svg-field')); ?>
		<?php echo $form->error($model,'svg'); ?>
	</div>

	<?php
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFaceSvg.js', CClientScript::POS_END);
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFaceEditor.js', CClientScript::POS_END);
	Yii::app()->clientScript->registerScript('host-face-editor-init', '
		HostFaceEditor.init({
			loadPortInfoUrlTemplate: ' . CJSON::encode(Yii::app()->createUrl('host/loadPortInfo/99999999')) . ',
			fetchImageUrl: ' . CJSON::encode(Yii::app()->createUrl('hostFace/fetchImage')) . ',
			existingSvg: ' . CJSON::encode($model->svg) . '
		});
	', CClientScript::POS_END);
	?>
```

Nota: `Yii::app()->createUrl('host/loadPortInfo/99999999')` usa `99999999` como ID fictício só pra pegar o formato de URL correto que o Yii gera (path ou query string, dependendo da config de `urlManager`) — o JS troca esse número pelo ID real do host escolhido antes de usar (isso é feito na Task 4). Não é pra "existir" um host com esse ID, é só um placeholder.

- [ ] **Step 2: Adicionar CSS do editor em `css/main.css`**

Adicionar ao final do arquivo:

```css
/* hostFace - editor visual */
.host-face-editor-toolbar,
.host-face-editor-image-source {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
    margin-bottom: 10px;
}

.host-face-editor-body {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.host-face-editor-canvas {
    flex: 3;
    border: 1px solid #ddd;
    padding: 10px;
    overflow: auto;
}

.host-face-editor-canvas-image-wrapper {
    position: relative;
    background-repeat: no-repeat;
}

.host-face-editor-port {
    position: absolute;
    border: 2px solid #4a9eff;
    background-color: rgba(74, 158, 255, 0.15);
    font-size: 9px;
    text-align: center;
    line-height: 1.6;
    cursor: move;
    color: #003366;
}

.host-face-editor-palette {
    flex: 1;
    min-width: 160px;
    border: 1px solid #ddd;
    padding: 10px;
    max-height: 400px;
    overflow: auto;
}

.host-face-editor-palette-item {
    padding: 4px 6px;
    margin-bottom: 4px;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: grab;
    font-size: 12px;
}

.host-face-editor-empty-hint {
    color: #999;
    font-size: 12px;
}

.host-face-editor-fallback {
    margin-top: 12px;
}
```

- [ ] **Step 3: Verificar sintaxe PHP**

```bash
php -l protected/views/hostFace/_form.php
```
Esperado: `No syntax errors detected`.

Nesta altura, a página de criar/editar Host Face vai referenciar `js/hostFaceSvg.js` (já existe, Task 1) e `js/hostFaceEditor.js` (**ainda não existe** — isso é a Task 4). Se alguém abrir a tela agora, o navegador vai dar 404 nesse segundo arquivo e o editor não vai funcionar. Isso é esperado nesta task — não crie um `hostFaceEditor.js` vazio só pra "tapar buraco", a Task 4 é a próxima e resolve isso.

- [ ] **Step 4: Commit**

```bash
git add protected/views/hostFace/_form.php css/main.css
git -c user.name="Marcelo Matos" -c user.email="contato@marcelomatos.dev" commit -m "Montar o markup do editor visual de Host Face em _form.php"
```

---

### Task 4: `js/hostFaceEditor.js` — interação completa

**Files:**
- Create: `js/hostFaceEditor.js`

- [ ] **Step 1: Criar `js/hostFaceEditor.js`**

```javascript
// js/hostFaceEditor.js
// Interação do editor visual de Host Face: escolher host, carregar lista de
// portas via AJAX, drag-and-drop pro canvas, mover/remover porta já
// posicionada, upload/URL de imagem, sincroniza o campo escondido com o
// SVG final. Depende de js/hostFaceSvg.js (funções puras de montar/ler SVG).

var HostFaceEditor = (function () {
    'use strict';

    var config = {};
    var state = {
        imageDataUri: null,
        imageWidth: 0,
        imageHeight: 0,
        allPorts: [],       // portas do host escolhido: [{ifIndex, ifDescr, ifAlias}]
        placedPorts: []     // portas já no canvas: [{id, x, y, width, height}]
    };

    function $(id) {
        return document.getElementById(id);
    }

    function init(options) {
        config = options;

        $('hfe-host-select').addEventListener('change', onHostChange);
        $('hfe-image-upload').addEventListener('change', onImageUpload);
        $('hfe-image-url-load').addEventListener('click', onImageUrlLoad);
        $('hfe-fallback-textarea').addEventListener('input', function (e) {
            $('hfe-svg-field').value = e.target.value;
        });

        if (config.existingSvg) {
            loadExistingSvg(config.existingSvg);
        }
    }

    function onHostChange(e) {
        var hostId = e.target.value;
        state.allPorts = [];
        state.placedPorts = [];
        redrawCanvas();

        if (!hostId) {
            renderPalette([]);
            return;
        }

        var url = config.loadPortInfoUrlTemplate.replace('99999999', hostId);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function () {
            if (xhr.status !== 200) {
                setStatus('Não foi possível carregar as portas desse host.');
                return;
            }
            var ports;
            try {
                ports = JSON.parse(xhr.responseText);
            } catch (err) {
                setStatus('Resposta inválida do servidor ao carregar portas.');
                return;
            }
            state.allPorts = ports;
            renderPalette(availablePorts());
        };
        xhr.onerror = function () {
            setStatus('Erro de rede ao carregar as portas desse host.');
        };
        xhr.send();
    }

    function availablePorts() {
        var placedIds = state.placedPorts.map(function (p) { return p.id; });
        return state.allPorts.filter(function (p) {
            return placedIds.indexOf(String(p.ifIndex)) === -1;
        });
    }

    function renderPalette(ports) {
        var palette = $('hfe-palette');
        palette.innerHTML = '';

        if (ports.length === 0) {
            var hint = document.createElement('p');
            hint.className = 'host-face-editor-empty-hint';
            hint.textContent = state.allPorts.length === 0
                ? 'Escolha um host acima para carregar a lista de portas.'
                : 'Todas as portas já foram posicionadas.';
            palette.appendChild(hint);
            return;
        }

        ports.forEach(function (port) {
            var item = document.createElement('div');
            item.className = 'host-face-editor-palette-item';
            item.setAttribute('draggable', 'true');
            item.textContent = '\u28FF port' + port.ifIndex + (port.ifDescr ? ' \u2014 ' + port.ifDescr : '');
            item.addEventListener('dragstart', function (e) {
                e.dataTransfer.setData('text/plain', String(port.ifIndex));
            });
            palette.appendChild(item);
        });
    }

    function onImageUpload(e) {
        var file = e.target.files[0];
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function (event) {
            var img = new Image();
            img.onload = function () {
                setImage(event.target.result, img.naturalWidth, img.naturalHeight);
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }

    function onImageUrlLoad() {
        var url = $('hfe-image-url').value.trim();
        if (!url) {
            return;
        }
        setStatus('Carregando imagem...');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', config.fetchImageUrl + '?url=' + encodeURIComponent(url), true);
        xhr.onload = function () {
            var result;
            try {
                result = JSON.parse(xhr.responseText);
            } catch (err) {
                setStatus('Resposta inválida do servidor.');
                return;
            }
            if (result.error) {
                setStatus(result.error);
                return;
            }
            var img = new Image();
            img.onload = function () {
                setImage(result.dataUri, img.naturalWidth, img.naturalHeight);
                setStatus('');
            };
            img.src = result.dataUri;
        };
        xhr.onerror = function () {
            setStatus('Erro de rede ao buscar a imagem.');
        };
        xhr.send();
    }

    function setStatus(message) {
        $('hfe-image-status').textContent = message;
    }

    function setImage(dataUri, width, height) {
        state.imageDataUri = dataUri;
        state.imageWidth = width;
        state.imageHeight = height;
        state.placedPorts = [];
        redrawCanvas();
        renderPalette(availablePorts());
        syncHiddenField();
    }

    function redrawCanvas() {
        var canvas = $('hfe-canvas');
        canvas.innerHTML = '';

        if (!state.imageDataUri) {
            var hint = document.createElement('p');
            hint.className = 'host-face-editor-empty-hint';
            hint.textContent = 'Envie ou cole a URL de uma imagem do switch para começar.';
            canvas.appendChild(hint);
            return;
        }

        var wrapper = document.createElement('div');
        wrapper.className = 'host-face-editor-canvas-image-wrapper';
        wrapper.style.width = state.imageWidth + 'px';
        wrapper.style.height = state.imageHeight + 'px';
        wrapper.style.backgroundImage = 'url(' + state.imageDataUri + ')';
        wrapper.style.backgroundSize = state.imageWidth + 'px ' + state.imageHeight + 'px';

        wrapper.addEventListener('dragover', function (e) {
            e.preventDefault();
        });
        wrapper.addEventListener('drop', onCanvasDrop);

        state.placedPorts.forEach(function (port) {
            wrapper.appendChild(buildPortElement(port));
        });

        canvas.appendChild(wrapper);
    }

    function buildPortElement(port) {
        var el = document.createElement('div');
        el.className = 'host-face-editor-port';
        el.style.left = port.x + 'px';
        el.style.top = port.y + 'px';
        el.style.width = port.width + 'px';
        el.style.height = port.height + 'px';
        el.title = 'port' + port.id + ' (arraste para mover, Alt+clique para remover)';
        el.textContent = port.id;
        el.setAttribute('draggable', 'true');

        el.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', 'move:' + port.id);
        });

        el.addEventListener('click', function (e) {
            if (e.altKey) {
                e.stopPropagation();
                removePort(port.id);
            }
        });

        return el;
    }

    function onCanvasDrop(e) {
        e.preventDefault();
        var data = e.dataTransfer.getData('text/plain');
        var wrapper = e.currentTarget;
        var rect = wrapper.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;

        if (data.indexOf('move:') === 0) {
            var movingId = data.substring(5);
            var port = state.placedPorts.filter(function (p) { return p.id === movingId; })[0];
            if (port) {
                port.x = Math.round(x - port.width / 2);
                port.y = Math.round(y - port.height / 2);
            }
        } else {
            var portId = data;
            var width = parseInt($('hfe-port-width').value, 10) || 22;
            var height = parseInt($('hfe-port-height').value, 10) || 18;
            state.placedPorts.push({
                id: portId,
                x: Math.round(x - width / 2),
                y: Math.round(y - height / 2),
                width: width,
                height: height
            });
        }

        redrawCanvas();
        renderPalette(availablePorts());
        syncHiddenField();
    }

    function removePort(portId) {
        state.placedPorts = state.placedPorts.filter(function (p) { return p.id !== portId; });
        redrawCanvas();
        renderPalette(availablePorts());
        syncHiddenField();
    }

    function syncHiddenField() {
        if (!state.imageDataUri) {
            $('hfe-svg-field').value = '';
            return;
        }
        $('hfe-svg-field').value = HostFaceSvg.buildSvgFromPorts(
            state.imageDataUri,
            state.imageWidth,
            state.imageHeight,
            state.placedPorts
        );
    }

    function loadExistingSvg(svgString) {
        var parsed = HostFaceSvg.parseSvgToPorts(svgString);
        if (!parsed) {
            showFallback(svgString);
            return;
        }
        state.imageDataUri = parsed.imageDataUri;
        state.imageWidth = parsed.imageWidth;
        state.imageHeight = parsed.imageHeight;
        state.placedPorts = parsed.ports;
        redrawCanvas();
        syncHiddenField();
    }

    function showFallback(svgString) {
        $('host-face-editor').style.display = 'none';
        $('hfe-fallback').style.display = 'block';
        $('hfe-fallback-textarea').value = svgString;
        $('hfe-svg-field').value = svgString;
    }

    return {
        init: init
    };
})();
```

- [ ] **Step 2: Verificar sintaxe JS**

```bash
node -c js/hostFaceEditor.js
```
Esperado: nenhuma saída (sintaxe OK).

- [ ] **Step 3: Verificação manual — sem navegador disponível pro subagente**

Você não tem navegador disponível pra testar visualmente. Confira lendo o código:
- `HostFaceEditor.init()` é chamado pelo script inline registrado na Task 3, com `loadPortInfoUrlTemplate`, `fetchImageUrl` e `existingSvg` batendo com as chaves que este arquivo espera em `config`.
- Todos os elementos DOM referenciados por `$('...')` (`hfe-host-select`, `hfe-image-upload`, `hfe-image-url-load`, `hfe-image-url`, `hfe-image-status`, `hfe-canvas`, `hfe-palette`, `hfe-port-width`, `hfe-port-height`, `hfe-svg-field`, `host-face-editor`, `hfe-fallback`, `hfe-fallback-textarea`) existem no markup criado na Task 3 — confira com `grep` se quiser.
- Registre no relatório final que a verificação visual (arrastar de verdade, ver o SVG final renderizar) fica pendente pra Task 5.

- [ ] **Step 4: Commit**

```bash
git add js/hostFaceEditor.js
git -c user.name="Marcelo Matos" -c user.email="contato@marcelomatos.dev" commit -m "Adicionar interação completa do editor visual de Host Face"
```

---

### Task 5: Integração final — verificação manual completa

**Files:** nenhum arquivo novo — esta task é só verificação end-to-end do que as Tasks 1-4 já produziram.

- [ ] **Step 1: Rodar de novo o teste standalone (garantia de que nada quebrou)**

```bash
node protected/tests/js/test_host_face_svg.js
echo "exit code: $?"
```
Esperado: todos `OK`, exit code 0.

- [ ] **Step 2: `php -l` em todos os arquivos PHP tocados no plano inteiro**

```bash
php -l protected/controllers/HostFaceController.php
php -l protected/views/hostFace/_form.php
php -l protected/views/hostFace/jsonFetchImage.php
php -l protected/views/hostFace/jsonError.php
```
Esperado: `No syntax errors detected` em todos.

- [ ] **Step 3: `node -c` em todos os arquivos JS**

```bash
node -c js/hostFaceSvg.js
node -c js/hostFaceEditor.js
```
Esperado: nenhuma saída nos dois.

- [ ] **Step 4: Checklist manual no navegador**

Abrir `hostFace/create` e `hostFace/update/<id>` (de uma das 6 faces existentes) num ambiente com a aplicação rodando de verdade, e confirmar:

1. **Criar face nova**: escolher um host real com SNMP configurado no dropdown → lista lateral carrega as portas dele. Fazer upload de uma imagem qualquer (PNG/JPG) → aparece no canvas. Arrastar 2-3 portas da lista pra cima da imagem → retângulos aparecem nas posições soltas, numerados, e somem da lista lateral.
2. **Mover porta**: arrastar um retângulo já posicionado no canvas pra outro lugar → ele se move, não duplica.
3. **Remover porta**: Alt+clique num retângulo posicionado → ele some do canvas e volta a aparecer na lista lateral.
4. **Colar URL de imagem**: colar a URL de uma imagem pública qualquer (ex.: um PNG de um site qualquer) e clicar "Carregar" → a imagem aparece no canvas (confirma que `hostFace/fetchImage` funcionou e não caiu em erro de CORS/tipo).
5. **Salvar**: clicar em "Create"/"Save" → confirma que a Host Face foi criada com o SVG certo (abrir de novo em modo `update` e ver se as portas aparecem nas mesmas posições).
6. **Reabrir face antiga**: abrir `hostFace/update` de cada uma das 6 faces já existentes (3Com, HP V1910-48G, H3C S5500EI, Procurve J9279A, Procurve J9280A, HUAWEI_S5700) e confirmar que a foto e as portas aparecem certinho no canvas, sem cair no aviso de "não foi possível interpretar".
7. **Tamanho da porta**: mudar os campos de largura/altura antes de arrastar uma nova porta → o próximo retângulo criado usa o novo tamanho; os já posicionados não mudam.

- [ ] **Step 5: Reportar resultado**

Se algum item do checklist falhar, corrija antes de considerar a task concluída (volte pro arquivo relevante das Tasks 1-4, não crie um arquivo novo pra "consertar por cima"). Se tudo passar, não é necessário commit nesta task (nenhum arquivo muda) — só reporte o resultado do checklist.
