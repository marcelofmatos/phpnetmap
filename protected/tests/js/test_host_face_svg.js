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

// 1b. Regressão: atributos como "rx"/"ry" (cantos arredondados, comuns em
// SVG exportado do Inkscape) não podem ser confundidos com "x"/"y" só
// porque terminam com a mesma letra e aparecem antes na tag.
check('nao confunde rx/ry com x/y', function () {
    var svgComRxRy = '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50">' +
        '<image xlink:href="data:image/png;base64,AAAA" x="0" y="0" width="50" height="50" />' +
        '<rect class="port" id="port1" ry="0.5" rx="0.3" width="20" height="15" x="10" y="5" />' +
        '</svg>';
    var parsed = HostFaceSvg.parseSvgToPorts(svgComRxRy);
    assert.ok(parsed, 'parse não deveria retornar null');
    assert.strictEqual(parsed.ports.length, 1);
    assert.strictEqual(parsed.ports[0].x, 10, 'x não pode pegar o valor de rx');
    assert.strictEqual(parsed.ports[0].y, 5, 'y não pode pegar o valor de ry');
});

// 1c. Regressão: SVGs desenhados no Inkscape costumam quebrar o base64 do
// xlink:href em várias linhas (indentado, terminado em \r\n). Esses espaços/
// quebras de linha não podem sobrar no imageDataUri extraído — se sobrarem,
// o editor visual usa esse valor em `background-image: url(...)` no CSS, e
// um url() sem aspas com espaço/quebra de linha dentro é inválido: o
// navegador ignora a declaração inteira e a foto do switch não aparece,
// mesmo com as portas posicionadas certinho (bug relatado pelo usuário ao
// reabrir uma face já cadastrada).
check('remove espacos/quebras de linha do data URI da imagem (base64 multi-linha)', function () {
    var svgComBase64Quebrado = '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50">' +
        '<image\n     xlink:href="data:image/png;base64,AAAA\n   BBBB\r\n   CCCC"\n     x="0"\n     y="0"\n     width="50"\n     height="50" />' +
        '<rect class="port" id="port1" x="10" y="5" width="20" height="15" />' +
        '</svg>';
    var parsed = HostFaceSvg.parseSvgToPorts(svgComBase64Quebrado);
    assert.ok(parsed, 'parse não deveria retornar null');
    assert.strictEqual(parsed.imageDataUri, 'data:image/png;base64,AAAABBBBCCCC',
        'espacos/quebras de linha dentro do base64 devem ser removidos');
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
        assert.ok(!/\s/.test(result.imageDataUri),
            'imageDataUri não pode conter espaços/quebras de linha (quebra o url() do CSS)');
    });
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
