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

    var HostFaceGridFill = {
        ROW_MAJOR: ROW_MAJOR,
        COLUMN_MAJOR: COLUMN_MAJOR,
        computeGridPositions: computeGridPositions
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = HostFaceGridFill;
    } else if (typeof window !== 'undefined') {
        window.HostFaceGridFill = HostFaceGridFill;
    }
})();
