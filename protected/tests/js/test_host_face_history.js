// protected/tests/js/test_host_face_history.js
// Roda com: node protected/tests/js/test_host_face_history.js
var assert = require('assert');
var HostFaceHistory = require('../../../js/hostFaceHistory.js');

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

check('histórico novo não tem o que desfazer nem refazer', function () {
    var history = HostFaceHistory.createHistory();
    assert.strictEqual(history.canUndo(), false);
    assert.strictEqual(history.canRedo(), false);
});

check('record() habilita canUndo()', function () {
    var history = HostFaceHistory.createHistory();
    history.record('estado A');
    assert.strictEqual(history.canUndo(), true);
    assert.strictEqual(history.canRedo(), false);
});

check('undo() devolve o snapshot gravado e guarda o atual pro redo', function () {
    var history = HostFaceHistory.createHistory();
    history.record('estado A');
    var previous = history.undo('estado B');
    assert.strictEqual(previous, 'estado A');
    assert.strictEqual(history.canUndo(), false);
    assert.strictEqual(history.canRedo(), true);
});

check('redo() devolve o estado desfeito e guarda o atual pro undo de novo', function () {
    var history = HostFaceHistory.createHistory();
    history.record('estado A');
    history.undo('estado B');
    var next = history.redo('estado A');
    assert.strictEqual(next, 'estado B');
    assert.strictEqual(history.canUndo(), true);
    assert.strictEqual(history.canRedo(), false);
});

check('undo()/redo() em pilha vazia devolve null e não quebra', function () {
    var history = HostFaceHistory.createHistory();
    assert.strictEqual(history.undo('estado atual'), null);
    assert.strictEqual(history.redo('estado atual'), null);
});

check('múltiplos record() desfazem em ordem LIFO', function () {
    var history = HostFaceHistory.createHistory();
    history.record('estado A');
    history.record('estado B');
    var current = 'estado C';
    current = history.undo(current);
    assert.strictEqual(current, 'estado B');
    current = history.undo(current);
    assert.strictEqual(current, 'estado A');
    assert.strictEqual(history.canUndo(), false);
});

check('record() depois de um undo descarta o redo pendente', function () {
    var history = HostFaceHistory.createHistory();
    history.record('estado A');
    history.undo('estado B');
    assert.strictEqual(history.canRedo(), true);
    history.record('estado C');
    assert.strictEqual(history.canRedo(), false);
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
