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

check('rows*cols acima do limite (MAX_CELLS) retorna null', function () {
    assert.strictEqual(HostFaceGridFill.computeGridPositions(200, 200, HostFaceGridFill.ROW_MAJOR), null); // 40000 > 10000
    assert.ok(HostFaceGridFill.computeGridPositions(100, 100, HostFaceGridFill.ROW_MAJOR) !== null); // 10000, no limite, ainda funciona
});

check('computeCellRects: column-major 2x6 sobre box conhecido dá um retângulo por porta', function () {
    var box = {x: 10, y: 20, width: 300, height: 100};
    var rects = HostFaceGridFill.computeCellRects(box, 2, 6, HostFaceGridFill.COLUMN_MAJOR);
    assert.strictEqual(rects.length, 12);
    // cellWidth = 300/6 = 50, cellHeight = 100/2 = 50
    assert.deepStrictEqual(rects[0], {x: 10, y: 20, width: 50, height: 50}); // porta 1: topo, coluna 1
    assert.deepStrictEqual(rects[1], {x: 10, y: 70, width: 50, height: 50}); // porta 2: embaixo, mesma coluna
    assert.deepStrictEqual(rects[2], {x: 60, y: 20, width: 50, height: 50}); // porta 3: topo, próxima coluna
});

check('computeCellRects: rows/cols/order invalidos retornam null', function () {
    var box = {x: 0, y: 0, width: 300, height: 100};
    assert.strictEqual(HostFaceGridFill.computeCellRects(box, 0, 5, HostFaceGridFill.ROW_MAJOR), null);
    assert.strictEqual(HostFaceGridFill.computeCellRects(box, 5, -1, HostFaceGridFill.ROW_MAJOR), null);
    assert.strictEqual(HostFaceGridFill.computeCellRects(box, 2.5, 5, HostFaceGridFill.ROW_MAJOR), null);
    assert.strictEqual(HostFaceGridFill.computeCellRects(box, '2', 5, HostFaceGridFill.ROW_MAJOR), null);
    assert.strictEqual(HostFaceGridFill.computeCellRects(box, 2, 6, 'diagonal'), null);
});

check('filterPortsByParity: "all" retorna a lista inteira, na mesma ordem', function () {
    var ports = [{ifIndex: 1}, {ifIndex: 2}, {ifIndex: 3}, {ifIndex: 4}];
    var result = HostFaceGridFill.filterPortsByParity(ports, 'all');
    assert.deepStrictEqual(result, ports);
});

check('filterPortsByParity: "odd" mantém só ifIndex ímpar, preservando a ordem', function () {
    var ports = [{ifIndex: 1}, {ifIndex: 2}, {ifIndex: 3}, {ifIndex: 4}, {ifIndex: 5}];
    var result = HostFaceGridFill.filterPortsByParity(ports, 'odd');
    assert.deepStrictEqual(result.map(function (p) { return p.ifIndex; }), [1, 3, 5]);
});

check('filterPortsByParity: "even" mantém só ifIndex par, preservando a ordem', function () {
    var ports = [{ifIndex: 1}, {ifIndex: 2}, {ifIndex: 3}, {ifIndex: 4}, {ifIndex: 5}];
    var result = HostFaceGridFill.filterPortsByParity(ports, 'even');
    assert.deepStrictEqual(result.map(function (p) { return p.ifIndex; }), [2, 4]);
});

check('filterPortsByParity: ifIndex como string (vindo de SNMP/DOM) também funciona', function () {
    var ports = [{ifIndex: '1'}, {ifIndex: '2'}, {ifIndex: '3'}];
    assert.deepStrictEqual(HostFaceGridFill.filterPortsByParity(ports, 'odd').map(function (p) { return p.ifIndex; }), ['1', '3']);
    assert.deepStrictEqual(HostFaceGridFill.filterPortsByParity(ports, 'even').map(function (p) { return p.ifIndex; }), ['2']);
});

check('filterPortsByParity: lista vazia ou parity desconhecida não quebram', function () {
    assert.deepStrictEqual(HostFaceGridFill.filterPortsByParity([], 'odd'), []);
    assert.deepStrictEqual(HostFaceGridFill.filterPortsByParity([{ifIndex: 1}, {ifIndex: 2}], 'diagonal'), [{ifIndex: 1}, {ifIndex: 2}]);
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
