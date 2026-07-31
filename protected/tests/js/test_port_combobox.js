// protected/tests/js/test_port_combobox.js
// Roda com: node protected/tests/js/test_port_combobox.js
var assert = require('assert');
var PortCombobox = require('../../../js/portCombobox.js');

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

check('buildGroupedList: sem groupOf devolve só itens, sem header nenhum', function () {
    var items = [{ name: 'a' }, { name: 'b' }];
    var result = PortCombobox.buildGroupedList(items, null);
    assert.deepStrictEqual(result, [
        { type: 'item', item: items[0] },
        { type: 'item', item: items[1] }
    ]);
});

check('buildGroupedList: groupOf que sempre devolve null/undefined não gera header', function () {
    var items = [{ name: 'a' }, { name: 'b' }];
    var result = PortCombobox.buildGroupedList(items, function () { return null; });
    assert.strictEqual(result.filter(function (e) { return e.type === 'header'; }).length, 0);
});

check('buildGroupedList: header antes do grupo, divisor logo depois que ele termina', function () {
    var clear = { id: '', name: '-- clear --' };
    var assocA = { id: '1', name: 'A', associated: true };
    var assocB = { id: '2', name: 'B', associated: true };
    var other = { id: '3', name: 'C', associated: false };
    var items = [clear, assocA, assocB, other];

    var result = PortCombobox.buildGroupedList(items, function (item) {
        return item.associated ? 'Associated with this face' : null;
    });

    assert.deepStrictEqual(result, [
        { type: 'item', item: clear },
        { type: 'header', label: 'Associated with this face' },
        { type: 'item', item: assocA },
        { type: 'item', item: assocB },
        { type: 'divider' },
        { type: 'item', item: other }
    ]);
});

check('buildGroupedList: grupo no fim da lista não ganha divisor (não há o que separar depois)', function () {
    var other = { group: null };
    var assocA = { group: 'X' };
    var items = [other, assocA];

    var result = PortCombobox.buildGroupedList(items, function (item) { return item.group; });

    assert.deepStrictEqual(result, [
        { type: 'item', item: other },
        { type: 'header', label: 'X' },
        { type: 'item', item: assocA }
    ]);
});

check('buildGroupedList: grupos diferentes e não-contíguos geram header+divisor pra cada', function () {
    var itemA1 = { group: 'X' };
    var itemB = { group: null };
    var itemA2 = { group: 'X' };
    var items = [itemA1, itemB, itemA2];

    var result = PortCombobox.buildGroupedList(items, function (item) { return item.group; });

    assert.deepStrictEqual(result, [
        { type: 'header', label: 'X' },
        { type: 'item', item: itemA1 },
        { type: 'divider' },
        { type: 'item', item: itemB },
        { type: 'header', label: 'X' },
        { type: 'item', item: itemA2 }
    ]);
});

check('buildGroupedList: lista vazia devolve lista vazia', function () {
    assert.deepStrictEqual(PortCombobox.buildGroupedList([], function () { return 'X'; }), []);
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
