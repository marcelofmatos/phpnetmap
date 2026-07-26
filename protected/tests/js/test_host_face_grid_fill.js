// protected/tests/js/test_host_face_grid_fill.js
// Roda com: node protected/tests/js/test_host_face_grid_fill.js
var assert = require('assert');
var HostFaceGridFill = require('../../../js/hostFaceGridFill.js');

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

check('row-major 2x6 preenche linha inteira antes de descer', function () {
    var positions = HostFaceGridFill.computeGridPositions(2, 6, HostFaceGridFill.ROW_MAJOR);
    assert.strictEqual(positions.length, 12);
    assert.deepStrictEqual(positions[0], {row: 0, col: 0});
    assert.deepStrictEqual(positions[5], {row: 0, col: 5});
    assert.deepStrictEqual(positions[6], {row: 1, col: 0});
    assert.deepStrictEqual(positions[11], {row: 1, col: 5});
});

check('column-major 2x6 preenche coluna inteira antes de avançar (padrão Procurve)', function () {
    var positions = HostFaceGridFill.computeGridPositions(2, 6, HostFaceGridFill.COLUMN_MAJOR);
    assert.strictEqual(positions.length, 12);
    assert.deepStrictEqual(positions[0], {row: 0, col: 0}); // porta 1: topo, coluna 1
    assert.deepStrictEqual(positions[1], {row: 1, col: 0}); // porta 2: embaixo, mesma coluna
    assert.deepStrictEqual(positions[2], {row: 0, col: 1}); // porta 3: topo, próxima coluna
    assert.deepStrictEqual(positions[3], {row: 1, col: 1});
    assert.deepStrictEqual(positions[11], {row: 1, col: 5});
});

check('1 linha x N colunas: row-major e column-major dão o mesmo resultado', function () {
    var rowMajor = HostFaceGridFill.computeGridPositions(1, 5, HostFaceGridFill.ROW_MAJOR);
    var colMajor = HostFaceGridFill.computeGridPositions(1, 5, HostFaceGridFill.COLUMN_MAJOR);
    assert.deepStrictEqual(rowMajor, colMajor);
    assert.strictEqual(rowMajor.length, 5);
});

check('N linhas x 1 coluna: row-major e column-major dão o mesmo resultado', function () {
    var rowMajor = HostFaceGridFill.computeGridPositions(5, 1, HostFaceGridFill.ROW_MAJOR);
    var colMajor = HostFaceGridFill.computeGridPositions(5, 1, HostFaceGridFill.COLUMN_MAJOR);
    assert.deepStrictEqual(rowMajor, colMajor);
    assert.strictEqual(rowMajor.length, 5);
});

check('rows/cols invalidos (zero, negativo, fracionario, nao-numero) retornam null', function () {
    assert.strictEqual(HostFaceGridFill.computeGridPositions(0, 5, HostFaceGridFill.ROW_MAJOR), null);
    assert.strictEqual(HostFaceGridFill.computeGridPositions(5, -1, HostFaceGridFill.ROW_MAJOR), null);
    assert.strictEqual(HostFaceGridFill.computeGridPositions(2.5, 5, HostFaceGridFill.ROW_MAJOR), null);
    assert.strictEqual(HostFaceGridFill.computeGridPositions('2', 5, HostFaceGridFill.ROW_MAJOR), null);
});

check('order desconhecida retorna null', function () {
    assert.strictEqual(HostFaceGridFill.computeGridPositions(2, 6, 'diagonal'), null);
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
