// js/hostFaceGridFill.js
// Função pura (sem DOM) que calcula, para um preenchimento em área do
// editor de Host Face, a lista ordenada de posições {row, col} que cada
// porta da sequência deve receber, dado um número de linhas/colunas e uma
// ordem de preenchimento. Funciona tanto no navegador
// (window.HostFaceGridFill) quanto no Node (module.exports), pra dar pra
// testar sem navegador. Ver js/hostFaceSvg.js para o mesmo padrão.

(function () {
    'use strict';

    var ROW_MAJOR = 'row-major';
    var COLUMN_MAJOR = 'column-major';
    var MAX_CELLS = 10000; // generoso pra qualquer switch real, mas evita
    // que um typo (ex.: "9999999999" num campo) trave a aba computando um
    // array gigante a cada mousemove durante o arraste.

    /**
     * @param {number} rows
     * @param {number} cols
     * @param {string} order ROW_MAJOR (preenche uma linha inteira antes de
     *   descer pra próxima) ou COLUMN_MAJOR (preenche uma coluna inteira
     *   antes de avançar pra próxima).
     * @returns {Array<{row:number,col:number}>|null} posições na ordem em
     *   que as portas da sequência devem ser colocadas, ou null se rows/cols
     *   não forem inteiros positivos ou order for desconhecida.
     */
    function computeGridPositions(rows, cols, order) {
        if (!isPositiveInteger(rows) || !isPositiveInteger(cols)) {
            return null;
        }
        if (order !== ROW_MAJOR && order !== COLUMN_MAJOR) {
            return null;
        }
        if (rows * cols > MAX_CELLS) {
            return null;
        }

        var positions = [];
        if (order === ROW_MAJOR) {
            for (var r = 0; r < rows; r++) {
                for (var c = 0; c < cols; c++) {
                    positions.push({row: r, col: c});
                }
            }
        } else {
            for (var c2 = 0; c2 < cols; c2++) {
                for (var r2 = 0; r2 < rows; r2++) {
                    positions.push({row: r2, col: c2});
                }
            }
        }
        return positions;
    }

    function isPositiveInteger(n) {
        return typeof n === 'number' && isFinite(n) && n > 0 && Math.floor(n) === n;
    }

    /**
     * @param {{x:number,y:number,width:number,height:number}} box
     * @param {number} rows
     * @param {number} cols
     * @param {string} order
     * @returns {Array<{x:number,y:number,width:number,height:number}>|null} um
     *   retângulo (já arredondado) por porta da sequência, na mesma ordem de
     *   computeGridPositions, ou null se rows/cols/order forem inválidos.
     */
    function computeCellRects(box, rows, cols, order) {
        var positions = computeGridPositions(rows, cols, order);
        if (!positions) {
            return null;
        }
        var cellWidth = box.width / cols;
        var cellHeight = box.height / rows;
        return positions.map(function (pos) {
            return {
                x: Math.round(box.x + pos.col * cellWidth),
                y: Math.round(box.y + pos.row * cellHeight),
                width: Math.round(cellWidth),
                height: Math.round(cellHeight)
            };
        });
    }

    var HostFaceGridFill = {
        ROW_MAJOR: ROW_MAJOR,
        COLUMN_MAJOR: COLUMN_MAJOR,
        MAX_CELLS: MAX_CELLS,
        computeGridPositions: computeGridPositions,
        computeCellRects: computeCellRects
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = HostFaceGridFill;
    } else if (typeof window !== 'undefined') {
        window.HostFaceGridFill = HostFaceGridFill;
    }
})();
