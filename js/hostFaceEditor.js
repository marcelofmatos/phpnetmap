// js/hostFaceEditor.js
// Interação do editor visual de Host Face: escolher host, carregar lista de
// portas via AJAX, drag-and-drop pro canvas, mover/remover porta já
// posicionada, upload/URL de imagem, sincroniza o campo escondido com o
// SVG final. Depende de js/hostFaceSvg.js (funções puras de montar/ler SVG),
// js/hostFaceHistory.js (pilha de undo/redo, Ctrl+Z / Ctrl+Y),
// js/hostFacePortFilter.js (filtro de porta por texto, usado na paleta e
// no combo do modal de editar) e js/portCombobox.js (o combo em si,
// compartilhado com o formulário de Connection).

var HostFaceEditor = (function () {
    'use strict';

    var config = {};
    var state = {
        imageDataUri: null,
        imageWidth: 0,
        imageHeight: 0,
        allPorts: [],       // portas do host escolhido: [{ifIndex, ifDescr, ifAlias}]
        placedPorts: [],    // portas já no canvas: [{id, x, y, width, height}]
        hostPortsLoaded: false // true só depois de allPorts vir com sucesso de um host escolhido
    };
    var fillDrag = null;
    var history = HostFaceHistory.createHistory();
    var editPanelEl = null;
    var editingPortId = null;
    var paletteQuery = '';
    var selectedHostId = ''; // fonte da verdade do host escolhido — o campo
    // de texto do combobox só mostra o nome, não dá pra usar seu .value
    // como id (ver setupHostCombobox/loadHostPorts).

    // Campos de configuração do editor (tamanho de porta única + linhas/
    // colunas/ordem do preenchimento por área) que ficam salvos por modelo
    // de face no localStorage, pra lembrar a última configuração usada.
    var SETTINGS_FIELD_IDS = ['hfe-port-width', 'hfe-port-height', 'hfe-fill-rows', 'hfe-fill-cols', 'hfe-fill-order', 'hfe-fill-parity'];
    var SETTINGS_STORAGE_PREFIX = 'hostFaceEditor:portSettings:';

    function $(id) {
        return document.getElementById(id);
    }

    function init(options) {
        config = options;

        setupHostCombobox();

        // Vindo do link "create new" na aba Overview do host (hostId na URL
        // de hostFace/create) — já carrega as portas desse host e o painel
        // de modelo/busca de imagem, sem precisar escolher de novo.
        if (config.preselectedHostId) {
            var preselected = (config.hosts || []).filter(function (h) {
                return String(h.id) === String(config.preselectedHostId);
            })[0];
            if (preselected) {
                $('hfe-host-select').value = preselected.name;
                loadHostPorts(preselected.id);
            }
        }

        $('hfe-image-upload').addEventListener('change', onImageUpload);
        $('hfe-image-url-load').addEventListener('click', onImageUrlLoad);
        $('hfe-svg-export').addEventListener('click', onSvgExport);
        $('hfe-svg-import').addEventListener('change', onSvgImport);
        $('hfe-palette-filter').addEventListener('input', function (e) {
            paletteQuery = e.target.value;
            applyPaletteFilter();
        });
        $('hfe-fallback-textarea').addEventListener('input', function (e) {
            $('hfe-svg-field').value = e.target.value;
        });

        $('HostFace_name').addEventListener('change', function (e) {
            loadSettingsForName(e.target.value.trim());
        });
        if ($('HostFace_name').value.trim()) {
            loadSettingsForName($('HostFace_name').value.trim());
        }
        SETTINGS_FIELD_IDS.forEach(function (id) {
            $(id).addEventListener('change', saveSettingsForCurrentName);
        });

        document.addEventListener('keydown', onHistoryKeyDown);

        if (config.existingSvg) {
            loadExistingSvg(config.existingSvg);
        }
    }

    // Snapshot do que o undo/redo restaura: imagem + portas colocadas.
    // state.allPorts (lista de portas do host escolhido) fica de fora de
    // propósito — trocar de host não entra no histórico de undo, só as
    // ações feitas em cima da face (colocar/mover/remover porta, área de
    // preenchimento, trocar imagem).
    function clonePlacedPorts(ports) {
        return ports.map(function (p) {
            return {id: p.id, x: p.x, y: p.y, width: p.width, height: p.height};
        });
    }

    function snapshotState() {
        return {
            imageDataUri: state.imageDataUri,
            imageWidth: state.imageWidth,
            imageHeight: state.imageHeight,
            placedPorts: clonePlacedPorts(state.placedPorts)
        };
    }

    function applyState(snapshot) {
        state.imageDataUri = snapshot.imageDataUri;
        state.imageWidth = snapshot.imageWidth;
        state.imageHeight = snapshot.imageHeight;
        state.placedPorts = clonePlacedPorts(snapshot.placedPorts);
        redrawCanvas();
        renderPalette();
        syncHiddenField();
    }

    function recordHistory() {
        history.record(snapshotState());
    }

    function undo() {
        var previous = history.undo(snapshotState());
        if (previous) {
            applyState(previous);
        }
    }

    function redo() {
        var next = history.redo(snapshotState());
        if (next) {
            applyState(next);
        }
    }

    function onHistoryKeyDown(e) {
        var tag = (e.target && e.target.tagName) || '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            return;
        }
        if (!(e.ctrlKey || e.metaKey)) {
            return;
        }
        var key = e.key ? e.key.toLowerCase() : '';
        if (key === 'z' && !e.shiftKey) {
            e.preventDefault();
            undo();
        } else if (key === 'y' || (key === 'z' && e.shiftKey)) {
            e.preventDefault();
            redo();
        }
    }

    function settingsStorageKey(name) {
        return SETTINGS_STORAGE_PREFIX + name;
    }

    function saveSettingsForCurrentName() {
        var name = $('HostFace_name').value.trim();
        if (!name) {
            return;
        }
        var settings = {};
        SETTINGS_FIELD_IDS.forEach(function (id) {
            settings[id] = $(id).value;
        });
        try {
            localStorage.setItem(settingsStorageKey(name), JSON.stringify(settings));
        } catch (err) {
            // localStorage indisponível (modo privado, cota excedida, etc.)
            // — não é motivo pra travar o editor, só não salva a preferência.
        }
    }

    function loadSettingsForName(name) {
        if (!name) {
            return;
        }
        var raw;
        try {
            raw = localStorage.getItem(settingsStorageKey(name));
        } catch (err) {
            return;
        }
        if (!raw) {
            return;
        }
        var settings;
        try {
            settings = JSON.parse(raw);
        } catch (err) {
            return;
        }
        SETTINGS_FIELD_IDS.forEach(function (id) {
            if (settings[id] !== undefined) {
                $(id).value = settings[id];
            }
        });
    }

    // Combo filtrável do host de referência (texto + lista clicável que
    // filtra ao digitar, ver js/portCombobox.js) no lugar de um <select>
    // nativo — útil com muitos hosts cadastrados. O campo de texto só
    // mostra o nome escolhido; selectedHostId é a fonte da verdade.
    function setupHostCombobox() {
        var container = $('hfe-host-select-combobox');
        var input = $('hfe-host-select');
        var items = [{id: '', name: '-- clear --', type: ''}].concat(config.hosts || []);

        PortCombobox.attach(container, input, function () { return items; }, function (item) {
            loadHostPorts(item.id);
        }, {
            formatLabel: function (item) {
                return item.id && item.type ? item.name + ' (' + item.type + ')' : item.name;
            },
            selectValue: function (item) {
                return item.id ? item.name : '';
            },
            filterItems: function (allItems, query) {
                var q = (query || '').trim().toLowerCase();
                if (!q) {
                    return allItems;
                }
                return allItems.filter(function (item) {
                    return item.name.toLowerCase().indexOf(q) !== -1;
                });
            }
        });
    }

    function loadHostPorts(hostId) {
        selectedHostId = hostId;
        // As portas já colocadas ficam — trocar a referência (ex.: repetir o
        // mesmo layout físico pra um switch parecido) não deve apagar o
        // trabalho já feito. As que não existirem na lista do novo host
        // ficam marcadas com borda vermelha (ver isPortUnmatched), pra dar
        // pra corrigir uma a uma clicando pra editar.
        state.allPorts = [];
        state.hostPortsLoaded = false;
        redrawCanvas();
        syncHiddenField();
        $('hfe-host-info').style.display = 'none';

        if (!hostId) {
            renderPalette();
            return;
        }

        var url = config.loadPortInfoUrlTemplate.replace('99999999', hostId);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function () {
            // Se o usuário já trocou de host de novo antes desta resposta
            // chegar, ela não corresponde mais à seleção atual — descarta,
            // pra não sobrescrever com a lista de portas do host errado.
            if (selectedHostId !== hostId) {
                return;
            }

            if (xhr.status !== 200) {
                setStatus('Could not load ports for this host.');
                return;
            }
            var result;
            try {
                result = JSON.parse(xhr.responseText);
            } catch (err) {
                setStatus('Invalid server response while loading ports.');
                return;
            }
            if (!result || result.error) {
                setStatus(result && result.error ? result.error : 'Could not load ports for this host.');
                return;
            }
            state.allPorts = result;
            state.hostPortsLoaded = true;
            redrawCanvas();
            renderPalette();
        };
        xhr.onerror = function () {
            if (selectedHostId !== hostId) {
                return;
            }
            setStatus('Network error while loading ports for this host.');
        };
        xhr.send();

        // Segunda requisição, em paralelo, só de referência (não bloqueia nem
        // reporta erro na área de status — se falhar, o painel simplesmente
        // não aparece, sem competir com a mensagem de erro da lista de portas).
        var infoUrl = config.loadSystemInfoUrlTemplate.replace('99999999', hostId);
        var infoXhr = new XMLHttpRequest();
        infoXhr.open('GET', infoUrl, true);
        infoXhr.onload = function () {
            if (selectedHostId !== hostId || infoXhr.status !== 200) {
                return;
            }
            var result;
            try {
                result = JSON.parse(infoXhr.responseText);
            } catch (err) {
                return;
            }
            if (!result || result.error) {
                return;
            }
            renderHostInfo(result);
        };
        infoXhr.send();
    }

    function renderHostInfo(info) {
        var fallback = 'not reported via SNMP';
        $('hfe-host-info-sysname').textContent = info.sysName || fallback;
        $('hfe-host-info-sysdescr').textContent = info.sysDescr || fallback;
        $('hfe-host-info-sysdescr').title = info.sysDescr || '';
        $('hfe-host-info-ip').textContent = info.ip || fallback;

        var searchLink = $('hfe-host-info-search');
        if (info.sysDescr) {
            // Termos extras pra puxar foto de produto (não uma tela de
            // gerenciamento ou diagrama), de frente (o lado com as portas,
            // que é o que vai virar o fundo da Host Face) e em resolução alta
            // o bastante pra recortar as portas com nitidez.
            var searchQuery = info.sysDescr + ' front panel product photo high resolution';
            searchLink.href = 'https://www.google.com/search?tbm=isch&q=' + encodeURIComponent(searchQuery);
            searchLink.style.display = '';
        } else {
            searchLink.style.display = 'none';
        }

        $('hfe-host-info').style.display = 'block';
    }

    function availablePorts() {
        var placedIds = state.placedPorts.map(function (p) { return p.id; });
        return state.allPorts.filter(function (p) {
            return placedIds.indexOf(String(p.ifIndex)) === -1;
        });
    }

    function formatPortLabel(port) {
        return 'port' + port.ifIndex + (port.ifDescr ? ' — ' + port.ifDescr : '');
    }

    // Reconstrói a lista inteira de portas disponíveis — chamado sempre que
    // esse CONJUNTO muda (troca de host, soltar/mover/remover porta, aplicar
    // edição, undo/redo). O filtro de texto não passa por aqui: ele só
    // mostra/esconde os itens já montados (applyPaletteFilter), recolhendo/
    // expandindo com transição em vez de reconstruir tudo a cada tecla —
    // mesmo efeito do filtro de hosts do mapa (lá via jQuery slideUp/
    // slideDown; aqui via classe CSS, já que este arquivo é JS puro).
    function renderPalette() {
        var palette = $('hfe-palette');
        palette.innerHTML = '';

        var available = availablePorts();

        if (available.length === 0) {
            var hint = document.createElement('p');
            hint.className = 'host-face-editor-empty-hint';
            hint.textContent = state.allPorts.length === 0
                ? 'Choose a host above to load the port list.'
                : 'All ports have already been placed.';
            palette.appendChild(hint);
            return;
        }

        var noMatchHint = document.createElement('p');
        noMatchHint.className = 'host-face-editor-empty-hint';
        noMatchHint.id = 'hfe-palette-no-match-hint';
        noMatchHint.textContent = 'No ports match your filter.';
        noMatchHint.style.display = 'none';
        palette.appendChild(noMatchHint);

        available.forEach(function (port) {
            var item = document.createElement('div');
            item.className = 'host-face-editor-palette-item';
            item.setAttribute('draggable', 'true');
            item.setAttribute('data-search', HostFacePortFilter.buildSearchText(port));
            item.textContent = '⣿ ' + formatPortLabel(port);
            item.addEventListener('dragstart', function (e) {
                e.dataTransfer.setData('text/plain', String(port.ifIndex));
            });
            palette.appendChild(item);
        });

        applyPaletteFilter();
    }

    // Só alterna a classe que recolhe/expande cada item já montado (ver
    // renderPalette) conforme paletteQuery — não toca no resto do DOM, pra
    // a transição CSS do recolher/expandir aparecer.
    function applyPaletteFilter() {
        var items = document.querySelectorAll('#hfe-palette .host-face-editor-palette-item');
        if (items.length === 0) {
            return;
        }
        var q = paletteQuery.trim().toLowerCase();
        var visibleCount = 0;
        items.forEach(function (item) {
            var matches = !q || item.getAttribute('data-search').indexOf(q) !== -1;
            item.classList.toggle('host-face-editor-palette-item-hidden', !matches);
            if (matches) {
                visibleCount++;
            }
        });
        var noMatchHint = $('hfe-palette-no-match-hint');
        if (noMatchHint) {
            noMatchHint.style.display = visibleCount === 0 ? 'block' : 'none';
        }
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
            img.onerror = function () {
                setStatus('This file is not an image the browser can display.');
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
        setStatus('Loading image...');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', config.fetchImageUrl + '?url=' + encodeURIComponent(url), true);
        xhr.onload = function () {
            var result;
            try {
                result = JSON.parse(xhr.responseText);
            } catch (err) {
                setStatus('Invalid server response.');
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
            img.onerror = function () {
                setStatus('This image was downloaded, but the browser could not display it.');
            };
            img.src = result.dataUri;
        };
        xhr.onerror = function () {
            setStatus('Network error while fetching the image.');
        };
        xhr.send();
    }

    function setStatus(message) {
        $('hfe-image-status').textContent = message;
    }

    function setImage(dataUri, width, height) {
        recordHistory();
        state.imageDataUri = dataUri;
        state.imageWidth = width;
        state.imageHeight = height;
        state.placedPorts = [];
        redrawCanvas();
        renderPalette();
        syncHiddenField();
    }

    function setSvgImportStatus(message) {
        $('hfe-svg-import-status').textContent = message;
    }

    function sanitizeFilename(name) {
        var cleaned = name.replace(/[^a-z0-9_-]+/gi, '_').replace(/^_+|_+$/g, '');
        return cleaned || 'host-face';
    }

    // Baixa o SVG completo (foto + portas) — o mesmo texto já sincronizado
    // no campo escondido — pra edição fora daqui (ex.: Inkscape).
    function onSvgExport() {
        var svg = $('hfe-svg-field').value;
        if (!svg) {
            setSvgImportStatus('Nothing to export yet — upload an image first.');
            return;
        }
        setSvgImportStatus('');
        var blob = new Blob([svg], {type: 'image/svg+xml'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = sanitizeFilename($('HostFace_name').value.trim()) + '.svg';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    // Reimporta um SVG editado fora daqui — precisa seguir o mesmo formato
    // que exportamos (uma <image> de fundo + <rect class="port" id="portN">
    // por porta, ver js/hostFaceSvg.js). Entra no undo/redo como qualquer
    // outra ação; se não bater o formato, mostra o erro e não mexe no que já
    // estava editado.
    function onSvgImport(e) {
        var file = e.target.files[0];
        e.target.value = ''; // permite reimportar o mesmo arquivo de novo depois
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function (event) {
            var parsed = HostFaceSvg.parseSvgToPorts(event.target.result);
            if (!parsed) {
                setSvgImportStatus('This SVG doesn’t match the expected format (an <image> plus rect.port elements) — could not import.');
                return;
            }
            recordHistory();
            state.imageDataUri = parsed.imageDataUri;
            state.imageWidth = parsed.imageWidth;
            state.imageHeight = parsed.imageHeight;
            state.placedPorts = parsed.ports;
            redrawCanvas();
            renderPalette();
            syncHiddenField();
            setSvgImportStatus('');
        };
        reader.onerror = function () {
            setSvgImportStatus('Could not read this file.');
        };
        reader.readAsText(file);
    }

    function redrawCanvas() {
        // Fecha qualquer painel de edição aberto — o canvas vai ser
        // reconstruído do zero, então a porta que ele estava editando pode
        // nem existir mais nesse novo estado (undo/redo, troca de host, etc.).
        closeEditPanel();

        var canvas = $('hfe-canvas');
        canvas.innerHTML = '';

        if (!state.imageDataUri) {
            var hint = document.createElement('p');
            hint.className = 'host-face-editor-empty-hint';
            hint.textContent = 'Upload or paste the URL of a switch image to get started.';
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
        wrapper.addEventListener('mousedown', function (e) {
            onFillMouseDown(e, wrapper);
        });

        state.placedPorts.forEach(function (port) {
            wrapper.appendChild(buildPortElement(port));
        });

        canvas.appendChild(wrapper);
    }

    // Uma porta colocada é "sem referência" quando já sabemos as portas reais
    // do host escolhido (hostPortsLoaded) e o ifIndex dela não está mais
    // nessa lista — ex.: trocou a referência pra outro switch com menos
    // portas, ou a numeração mudou. Sem host escolhido ainda, não dá pra
    // afirmar nada, então nenhuma porta é marcada.
    function isPortUnmatched(portId) {
        if (!state.hostPortsLoaded) {
            return false;
        }
        return !state.allPorts.some(function (p) { return String(p.ifIndex) === portId; });
    }

    function buildPortElement(port) {
        var unmatched = isPortUnmatched(port.id);

        var el = document.createElement('div');
        el.className = 'host-face-editor-port' + (unmatched ? ' host-face-editor-port-unmatched' : '');
        el.style.left = port.x + 'px';
        el.style.top = port.y + 'px';
        el.style.width = port.width + 'px';
        el.style.height = port.height + 'px';
        el.title = 'port' + port.id + ' (drag to move, click to edit, Alt+click to remove)' +
            (unmatched ? ' — no port with this ifIndex on the selected switch' : '');
        el.textContent = port.id;
        el.setAttribute('draggable', 'true');

        el.addEventListener('dragstart', function (e) {
            // Guarda o ponto exato onde o usuário clicou dentro da caixa,
            // pra na hora do drop reposicionar mantendo esse mesmo ponto sob
            // o mouse — igual ao "fantasma" nativo do navegador mostra
            // durante o arraste. Sem isso, a caixa "pula" pro centro do
            // mouse ao soltar, descolando do que a sombra indicava.
            // offsetX/offsetY são relativos à borda do padding (dentro da
            // border-box), mas o fantasma do navegador renderiza a
            // border-box inteira — soma a espessura da borda de volta pra
            // bater exatamente com o que a sombra mostra.
            var style = getComputedStyle(el);
            var grabOffsetX = e.offsetX + (parseFloat(style.borderLeftWidth) || 0);
            var grabOffsetY = e.offsetY + (parseFloat(style.borderTopWidth) || 0);
            e.dataTransfer.setData('text/plain', 'move:' + port.id + ':' + grabOffsetX + ':' + grabOffsetY);
        });

        el.addEventListener('click', function (e) {
            e.stopPropagation();
            if (e.altKey) {
                removePort(port.id);
            } else {
                openEditPanel(port);
            }
        });

        return el;
    }

    // Opções do combo de porta ao editar: a porta atual (mesmo que o host
    // escolhido não a reconheça mais) primeiro, depois as ainda disponíveis
    // do host escolhido — nunca lista uma porta já usada por OUTRA caixa.
    // Devolve porta "crua" ({ifIndex, ifDescr, ...}), mesmo formato usado na
    // paleta, pra reaproveitar formatPortLabel/HostFacePortFilter.
    function buildPortEditOptions(currentPortId) {
        var options = [];
        var seen = {};

        var currentInfo = state.allPorts.filter(function (p) { return String(p.ifIndex) === currentPortId; })[0];
        options.push(currentInfo || {ifIndex: currentPortId});
        seen[currentPortId] = true;

        availablePorts().forEach(function (p) {
            var id = String(p.ifIndex);
            if (seen[id]) {
                return;
            }
            seen[id] = true;
            options.push(p);
        });

        options.sort(function (a, b) { return parseInt(a.ifIndex, 10) - parseInt(b.ifIndex, 10); });
        return options;
    }

    // Combobox de porta (texto + lista clicável que filtra ao digitar), no
    // lugar de um <select> nativo — usa js/portCombobox.js, compartilhado
    // com o formulário de Connection (Host Src/Dst Port).
    function buildPortCombobox(portOptions, currentPortId) {
        var wrapper = document.createElement('div');
        wrapper.className = 'port-combobox';

        var input = document.createElement('input');
        input.type = 'text';
        var currentInfo = portOptions.filter(function (p) { return String(p.ifIndex) === currentPortId; })[0];
        input.value = formatPortLabel(currentInfo || {ifIndex: currentPortId});
        wrapper.appendChild(input);

        var selectedId = currentPortId;
        PortCombobox.attach(wrapper, input, function () { return portOptions; }, function (port) {
            selectedId = String(port.ifIndex);
        }, {formatLabel: formatPortLabel});

        return {
            el: wrapper,
            getSelectedId: function () { return selectedId; }
        };
    }

    function closeEditPanel() {
        if (editPanelEl) {
            editPanelEl.remove();
        }
        editPanelEl = null;
        editingPortId = null;
    }

    // Um modal fixo (fora da imagem, com fundo escurecido) em vez de um
    // painel encaixado perto da porta clicada — numa foto de switch com
    // portas pequenas e coladas, um painel ali por cima ficava atrás/
    // misturado com as outras portas.
    function openEditPanel(port) {
        closeEditPanel();
        editingPortId = port.id;

        var backdrop = document.createElement('div');
        backdrop.className = 'host-face-editor-edit-modal-backdrop';
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) {
                closeEditPanel();
            }
        });

        var panel = document.createElement('div');
        panel.className = 'host-face-editor-edit-panel';

        var title = document.createElement('h4');
        title.textContent = 'Edit port';
        panel.appendChild(title);

        var portLabel = document.createElement('label');
        portLabel.textContent = 'Port:';
        var portCombobox = buildPortCombobox(buildPortEditOptions(port.id), port.id);
        portLabel.appendChild(portCombobox.el);

        var widthLabel = document.createElement('label');
        widthLabel.textContent = 'Width (px):';
        var widthInput = document.createElement('input');
        widthInput.type = 'number';
        widthInput.min = '1';
        widthInput.value = port.width;
        widthLabel.appendChild(widthInput);

        var heightLabel = document.createElement('label');
        heightLabel.textContent = 'Height (px):';
        var heightInput = document.createElement('input');
        heightInput.type = 'number';
        heightInput.min = '1';
        heightInput.value = port.height;
        heightLabel.appendChild(heightInput);

        var buttonRow = document.createElement('div');
        buttonRow.className = 'host-face-editor-edit-panel-buttons';

        var applyBtn = document.createElement('button');
        applyBtn.type = 'button';
        applyBtn.textContent = 'Apply';
        applyBtn.addEventListener('click', function () {
            applyPortEdit(port.id, portCombobox.getSelectedId(), widthInput.value, heightInput.value);
        });

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.textContent = 'Cancel';
        cancelBtn.addEventListener('click', closeEditPanel);

        buttonRow.appendChild(applyBtn);
        buttonRow.appendChild(cancelBtn);

        panel.appendChild(portLabel);
        panel.appendChild(widthLabel);
        panel.appendChild(heightLabel);
        panel.appendChild(buttonRow);

        backdrop.appendChild(panel);
        document.body.appendChild(backdrop);
        editPanelEl = backdrop;
    }

    function applyPortEdit(originalId, newPortId, newWidthValue, newHeightValue) {
        var width = parseInt(newWidthValue, 10);
        var height = parseInt(newHeightValue, 10);
        if (!width || width < 1 || !height || height < 1) {
            return;
        }
        var port = state.placedPorts.filter(function (p) { return p.id === originalId; })[0];
        if (!port) {
            closeEditPanel();
            return;
        }

        recordHistory();
        port.id = newPortId;
        port.width = width;
        port.height = height;

        redrawCanvas();
        renderPalette();
        syncHiddenField();
    }

    function onCanvasDrop(e) {
        e.preventDefault();
        recordHistory();
        var data = e.dataTransfer.getData('text/plain');
        var wrapper = e.currentTarget;
        var rect = wrapper.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;

        if (data.indexOf('move:') === 0) {
            var parts = data.split(':');
            var movingId = parts[1];
            var grabOffsetX = parseFloat(parts[2]);
            var grabOffsetY = parseFloat(parts[3]);
            var port = state.placedPorts.filter(function (p) { return p.id === movingId; })[0];
            if (port) {
                // Mantém o mesmo ponto de agarre do dragstart sob o mouse,
                // em vez de centralizar a caixa — assim a posição final bate
                // com o que a sombra do arraste mostrou.
                port.x = Math.round(x - grabOffsetX);
                port.y = Math.round(y - grabOffsetY);
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
        renderPalette();
        syncHiddenField();
    }

    function onFillMouseDown(e, wrapper) {
        if (e.target !== wrapper) {
            return;
        }
        e.preventDefault();
        var rect = wrapper.getBoundingClientRect();
        fillDrag = {
            wrapper: wrapper,
            startX: e.clientX - rect.left,
            startY: e.clientY - rect.top,
            boxEl: null,
            cellEls: [],
            lastBox: null
        };
        wrapper.style.userSelect = 'none';
        document.addEventListener('mousemove', onFillMouseMove);
        document.addEventListener('mouseup', onFillMouseUp);
        window.addEventListener('blur', onFillDragCancel);
    }

    function onFillMouseMove(e) {
        if (!fillDrag) {
            return;
        }
        var rect = fillDrag.wrapper.getBoundingClientRect();
        var curX = e.clientX - rect.left;
        var curY = e.clientY - rect.top;
        var box = {
            x: Math.min(fillDrag.startX, curX),
            y: Math.min(fillDrag.startY, curY),
            width: Math.abs(curX - fillDrag.startX),
            height: Math.abs(curY - fillDrag.startY)
        };
        renderFillPreview(box);
    }

    function onFillMouseUp() {
        if (!fillDrag) {
            return;
        }
        var box = fillDrag.lastBox;
        fillDrag.wrapper.style.userSelect = '';
        document.removeEventListener('mousemove', onFillMouseMove);
        document.removeEventListener('mouseup', onFillMouseUp);
        window.removeEventListener('blur', onFillDragCancel);
        clearFillPreview();
        fillDrag = null;

        if (box && box.width >= 4 && box.height >= 4) {
            commitFill(box);
        }
    }

    function onFillDragCancel() {
        if (!fillDrag) {
            return;
        }
        fillDrag.wrapper.style.userSelect = '';
        document.removeEventListener('mousemove', onFillMouseMove);
        document.removeEventListener('mouseup', onFillMouseUp);
        window.removeEventListener('blur', onFillDragCancel);
        clearFillPreview();
        fillDrag = null;
    }

    function readFillSettings() {
        return {
            rows: parseInt($('hfe-fill-rows').value, 10),
            cols: parseInt($('hfe-fill-cols').value, 10),
            order: $('hfe-fill-order').value,
            parity: $('hfe-fill-parity').value
        };
    }

    function renderFillPreview(box) {
        fillDrag.lastBox = box;
        clearFillPreview();

        var boxEl = document.createElement('div');
        boxEl.className = 'host-face-editor-fill-box';
        boxEl.style.left = box.x + 'px';
        boxEl.style.top = box.y + 'px';
        boxEl.style.width = box.width + 'px';
        boxEl.style.height = box.height + 'px';
        fillDrag.wrapper.appendChild(boxEl);
        fillDrag.boxEl = boxEl;

        var settings = readFillSettings();
        if (!settings.rows || !settings.cols || box.width < 1 || box.height < 1) {
            return;
        }
        var rects = HostFaceGridFill.computeCellRects(box, settings.rows, settings.cols, settings.order);
        if (!rects) {
            return;
        }
        var upcoming = HostFaceGridFill.filterPortsByParity(availablePorts(), settings.parity);

        for (var i = 0; i < rects.length && i < upcoming.length; i++) {
            var rect = rects[i];
            var cellEl = document.createElement('div');
            cellEl.className = 'host-face-editor-fill-cell';
            cellEl.style.left = rect.x + 'px';
            cellEl.style.top = rect.y + 'px';
            cellEl.style.width = rect.width + 'px';
            cellEl.style.height = rect.height + 'px';
            cellEl.textContent = upcoming[i].ifIndex;
            fillDrag.wrapper.appendChild(cellEl);
            fillDrag.cellEls.push(cellEl);
        }
    }

    function clearFillPreview() {
        if (!fillDrag) {
            return;
        }
        if (fillDrag.boxEl) {
            fillDrag.boxEl.remove();
        }
        fillDrag.cellEls.forEach(function (el) { el.remove(); });
        fillDrag.cellEls = [];
    }

    function commitFill(box) {
        setFillStatus('');
        var settings = readFillSettings();
        var rects = HostFaceGridFill.computeCellRects(box, settings.rows, settings.cols, settings.order);
        if (!rects) {
            setFillStatus('Set valid rows/columns and a fill order first.');
            return;
        }
        var upcoming = HostFaceGridFill.filterPortsByParity(availablePorts(), settings.parity);
        if (upcoming.length < rects.length) {
            setFillStatus('Only ' + upcoming.length + ' port(s) left, cannot fill ' + rects.length + '.');
            return;
        }

        var newPorts = rects.map(function (rect, i) {
            return {
                id: String(upcoming[i].ifIndex),
                x: rect.x,
                y: rect.y,
                width: rect.width,
                height: rect.height
            };
        });

        recordHistory();
        state.placedPorts = state.placedPorts.concat(newPorts);
        redrawCanvas();
        renderPalette();
        syncHiddenField();
    }

    function setFillStatus(message) {
        $('hfe-fill-status').textContent = message;
    }

    function removePort(portId) {
        recordHistory();
        state.placedPorts = state.placedPorts.filter(function (p) { return p.id !== portId; });
        redrawCanvas();
        renderPalette();
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
