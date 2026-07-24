// protected/tests/js/test_host_face_svg.js
// Roda com: node protected/tests/js/test_host_face_svg.js
var fs = require('fs');
var path = require('path');
var assert = require('assert');
var HostFaceSvg = require('../../../js/hostFaceSvg.js');

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

// 1. Round-trip sintético: montar e reler deve dar os mesmos dados de volta.
check('round-trip sintetico', function () {
    var built = HostFaceSvg.buildSvgFromPorts(
        'data:image/png;base64,AAAA',
        100,
        50,
        [
            { id: '1', x: 10, y: 5, width: 20, height: 15 },
            { id: '2', x: 35, y: 5, width: 20, height: 15 }
        ]
    );
    var parsed = HostFaceSvg.parseSvgToPorts(built);
    assert.ok(parsed, 'parse não deveria retornar null');
    assert.strictEqual(parsed.imageDataUri, 'data:image/png;base64,AAAA');
    assert.strictEqual(parsed.imageWidth, 100);
    assert.strictEqual(parsed.imageHeight, 50);
    assert.strictEqual(parsed.ports.length, 2);
    assert.strictEqual(parsed.ports[0].id, '1');
    assert.strictEqual(parsed.ports[0].x, 10);
    assert.strictEqual(parsed.ports[1].id, '2');
});

// 2. SVG sem imagem válida deve falhar o parse (retornar null), não lançar exceção.
check('svg sem imagem retorna null', function () {
    var parsed = HostFaceSvg.parseSvgToPorts('<svg width="10" height="10"></svg>');
    assert.strictEqual(parsed, null);
});

// 3. String que não é SVG retorna null.
check('string vazia retorna null', function () {
    assert.strictEqual(HostFaceSvg.parseSvgToPorts(''), null);
    assert.strictEqual(HostFaceSvg.parseSvgToPorts('não é svg'), null);
});

// 4. Parse contra as 6 faces reais já cadastradas em produção (fixtures no repo).
var fixturesDir = path.join(__dirname, '..', 'fixtures', 'host_faces');
var expectedPortCount = {
    '3Com_Baseline_Switch_2928-SFP.svg': 28,
    'HP_V1910-48G.svg': 52,
    'H3C_S5500EI.svg': 32,
    'Procurve_J9279A.svg': 24,
    'Procurve_J9280A.svg': 48,
    'HUAWEI_S5700.svg': 28
};

Object.keys(expectedPortCount).forEach(function (file) {
    check('fixture real: ' + file, function () {
        var svg = fs.readFileSync(path.join(fixturesDir, file), 'utf8');
        var result = HostFaceSvg.parseSvgToPorts(svg);
        assert.ok(result, 'parse retornou null pra ' + file);
        assert.strictEqual(result.ports.length, expectedPortCount[file],
            'esperava ' + expectedPortCount[file] + ' portas, achou ' + result.ports.length);
        assert.strictEqual(result.imageDataUri.indexOf('data:image/'), 0,
            'imageDataUri não parece um data URI de imagem');
    });
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
