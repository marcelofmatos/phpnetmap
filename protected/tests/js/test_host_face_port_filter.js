// protected/tests/js/test_host_face_port_filter.js
// Roda com: node protected/tests/js/test_host_face_port_filter.js
var assert = require('assert');
var HostFacePortFilter = require('../../../js/hostFacePortFilter.js');

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

var PORTS = [
    {ifIndex: 4, ifDescr: 'lsi', ifAlias: ''},
    {ifIndex: 5, ifDescr: 'dsc', ifAlias: ''},
    {ifIndex: 6, ifDescr: 'lo0', ifAlias: 'loopback'},
    {ifIndex: 21, ifDescr: 'lo0.16384', ifAlias: ''}
];

check('query vazia devolve a lista inteira, mesma ordem', function () {
    assert.deepStrictEqual(HostFacePortFilter.filterPortsByQuery(PORTS, ''), PORTS);
    assert.deepStrictEqual(HostFacePortFilter.filterPortsByQuery(PORTS, '   '), PORTS);
    assert.deepStrictEqual(HostFacePortFilter.filterPortsByQuery(PORTS, undefined), PORTS);
});

check('bate por substring do ifDescr, case-insensitive', function () {
    var result = HostFacePortFilter.filterPortsByQuery(PORTS, 'DSC');
    assert.deepStrictEqual(result.map(function (p) { return p.ifIndex; }), [5]);
});

check('bate por substring do ifIndex (via "port<N>")', function () {
    var result = HostFacePortFilter.filterPortsByQuery(PORTS, 'port21');
    assert.deepStrictEqual(result.map(function (p) { return p.ifIndex; }), [21]);
});

check('bate por substring do ifAlias', function () {
    var result = HostFacePortFilter.filterPortsByQuery(PORTS, 'loopback');
    assert.deepStrictEqual(result.map(function (p) { return p.ifIndex; }), [6]);
});

check('"lo0" bate os dois (lo0 e lo0.16384), preservando ordem', function () {
    var result = HostFacePortFilter.filterPortsByQuery(PORTS, 'lo0');
    assert.deepStrictEqual(result.map(function (p) { return p.ifIndex; }), [6, 21]);
});

check('sem nenhum match devolve lista vazia', function () {
    assert.deepStrictEqual(HostFacePortFilter.filterPortsByQuery(PORTS, 'xyz-nao-existe'), []);
});

check('porta sem ifDescr/ifAlias não quebra o filtro', function () {
    var ports = [{ifIndex: 1}];
    assert.deepStrictEqual(HostFacePortFilter.filterPortsByQuery(ports, ''), ports);
    assert.deepStrictEqual(HostFacePortFilter.filterPortsByQuery(ports, 'port1'), ports);
    assert.deepStrictEqual(HostFacePortFilter.filterPortsByQuery(ports, 'nada'), []);
});

check('buildSearchText: minúsculo e inclui ifIndex/ifDescr/ifAlias', function () {
    assert.strictEqual(HostFacePortFilter.buildSearchText({ifIndex: 5, ifDescr: 'DSC', ifAlias: 'Uplink'}), 'port5 dsc uplink');
});

check('buildSearchText: porta sem ifDescr/ifAlias não quebra', function () {
    assert.strictEqual(HostFacePortFilter.buildSearchText({ifIndex: 7}), 'port7  ');
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
