// js/hostFaceSvg.js
// Funções puras (sem DOM) para montar e interpretar o SVG de uma Host Face.
// Funciona tanto no navegador (expõe window.HostFaceSvg) quanto no Node
// (module.exports), pra dar pra testar sem navegador.

(function () {
    'use strict';

    function extractAttr(tagText, attrName) {
        // (?:^|\s) evita casar o final de outro atributo (ex.: buscar "x"
        // não pode casar dentro de "rx=" ou "dx=" quando esses vierem antes
        // do atributo "x" de verdade na mesma tag).
        var re = new RegExp('(?:^|\\s)' + attrName + '="([^"]*)"');
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

            if (!id || id.indexOf('port') !== 0) {
                // rect com class="port" mas sem id no formato "port<N>" — sobra
                // de edição manual no Inkscape (ex.: id="rect3010"). Não é uma
                // porta de verdade, ignora sem falhar o parse inteiro.
                continue;
            }

            var x = parseFloat(extractAttr(tag, 'x'));
            var y = parseFloat(extractAttr(tag, 'y'));
            var width = parseFloat(extractAttr(tag, 'width'));
            var height = parseFloat(extractAttr(tag, 'height'));

            if (isNaN(x) || isNaN(y) || isNaN(width) || isNaN(height)) {
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
