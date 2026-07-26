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
        syncHiddenField();
        $('hfe-host-info').style.display = 'none';

        if (!hostId) {
            renderPalette([]);
            return;
        }

        var url = config.loadPortInfoUrlTemplate.replace('99999999', hostId);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function () {
            // Se o usuário já trocou de host de novo antes desta resposta
            // chegar, ela não corresponde mais à seleção atual — descarta,
            // pra não sobrescrever com a lista de portas do host errado.
            if ($('hfe-host-select').value !== hostId) {
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
            renderPalette(availablePorts());
        };
        xhr.onerror = function () {
            if ($('hfe-host-select').value !== hostId) {
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
            if ($('hfe-host-select').value !== hostId || infoXhr.status !== 200) {
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
        $('hfe-host-info-ip').textContent = info.ip || fallback;

        var searchLink = $('hfe-host-info-search');
        if (info.sysDescr) {
            searchLink.href = 'https://www.google.com/search?tbm=isch&q=' + encodeURIComponent(info.sysDescr);
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

    function renderPalette(ports) {
        var palette = $('hfe-palette');
        palette.innerHTML = '';

        if (ports.length === 0) {
            var hint = document.createElement('p');
            hint.className = 'host-face-editor-empty-hint';
            hint.textContent = state.allPorts.length === 0
                ? 'Choose a host above to load the port list.'
                : 'All ports have already been placed.';
            palette.appendChild(hint);
            return;
        }

        ports.forEach(function (port) {
            var item = document.createElement('div');
            item.className = 'host-face-editor-palette-item';
            item.setAttribute('draggable', 'true');
            item.textContent = '⣿ port' + port.ifIndex + (port.ifDescr ? ' — ' + port.ifDescr : '');
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
        el.title = 'port' + port.id + ' (drag to move, Alt+click to remove)';
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
