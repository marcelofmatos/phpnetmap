// protected/tests/js/test_host_face_port_status.js
// Roda com: node protected/tests/js/test_host_face_port_status.js
var assert = require('assert');
var HostFacePortStatus = require('../../../js/hostFacePortStatus.js');

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

check('statusClassNames: sem status devolve lista vazia', function () {
    assert.deepStrictEqual(HostFacePortStatus.statusClassNames(null), []);
    assert.deepStrictEqual(HostFacePortStatus.statusClassNames(undefined), []);
    assert.deepStrictEqual(HostFacePortStatus.statusClassNames({}), []);
});

check('statusClassNames: porta up vira ifOperStatus1', function () {
    assert.deepStrictEqual(
        HostFacePortStatus.statusClassNames({ ifOperStatus: 1 }),
        ['ifOperStatus1']
    );
});

check('statusClassNames: combina ifOperStatus/ifAdminStatus/dot1dStpPortState', function () {
    assert.deepStrictEqual(
        HostFacePortStatus.statusClassNames({ ifOperStatus: 1, ifAdminStatus: 1, dot1dStpPortState: 2 }),
        ['ifOperStatus1', 'ifAdminStatus1', 'dot1dStpPortState2']
    );
});

check('withStatusClasses: preserva classes que não são de status', function () {
    var result = HostFacePortStatus.withStatusClasses('host-face-editor-port', { ifOperStatus: 1 });
    assert.strictEqual(result, 'host-face-editor-port ifOperStatus1');
});

check('withStatusClasses: troca status antigo pelo novo, sem duplicar nem acumular', function () {
    var afterFirstPoll = HostFacePortStatus.withStatusClasses('host-face-editor-port ifOperStatus2', { ifOperStatus: 1 });
    assert.strictEqual(afterFirstPoll, 'host-face-editor-port ifOperStatus1');
});

check('withStatusClasses: sem status novo remove qualquer status antigo', function () {
    var result = HostFacePortStatus.withStatusClasses('host-face-editor-port ifOperStatus2 ifAdminStatus1', null);
    assert.strictEqual(result, 'host-face-editor-port');
});

check('withStatusClasses: mantém a marcação host-face-editor-port-unmatched intacta', function () {
    var result = HostFacePortStatus.withStatusClasses(
        'host-face-editor-port host-face-editor-port-unmatched',
        { ifAdminStatus: 2 }
    );
    assert.strictEqual(result, 'host-face-editor-port host-face-editor-port-unmatched ifAdminStatus2');
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
