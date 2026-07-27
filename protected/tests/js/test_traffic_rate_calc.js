// protected/tests/js/test_traffic_rate_calc.js
// Roda com: node protected/tests/js/test_traffic_rate_calc.js
//
// Reproduz o cálculo de taxa de _traffic.php (setNewData) como funções
// puras, pra testar sem precisar de DOM/d3. O bug real: um roteador Juniper
// (sirius) só atualiza seus contadores de interface a cada ~6s, mais devagar
// que o poll de 3s da página — metade das leituras chega com o contador
// idêntico à anterior. Confirmado ao vivo via SNMP direto (sem cache):
// mesmo valor por 2 polls seguidos, depois salta pro próximo.
var assert = require('assert');

function octetsIn(p) { return (p.ifHCInOctets !== undefined && p.ifHCInOctets !== null) ? p.ifHCInOctets : p.ifInOctets; }

// Algoritmo ANTIGO (bugado): recalcula a taxa a cada poll usando sempre o
// tempo decorrido desde o poll anterior, mesmo quando o contador não mudou.
function oldSetNewData(portData, newSample) {
    var prevIn = octetsIn(portData);
    var newIn = octetsIn(newSample);
    var trafficIn = prevIn ? (newIn - prevIn) * 8 / (newSample.time - portData.time) : 0;
    return {
        ifHCInOctets: newSample.ifHCInOctets,
        time: newSample.time,
        measures: [Math.max(0, trafficIn), 0]
    };
}

// Algoritmo NOVO (corrigido): só recalcula quando o contador realmente muda,
// usando o tempo decorrido desde a última mudança de verdade (sampleTime).
function newSetNewData(portData, newSample) {
    if (portData.sampleTime === undefined) {
        portData.sampleTime = portData.time;
    }
    var prevIn = octetsIn(portData);
    var newIn = octetsIn(newSample);
    var changed = prevIn !== newIn;

    var measures = portData.measures || [0, 0];
    var sampleTime = portData.sampleTime;

    if (changed) {
        var elapsed = newSample.time - portData.sampleTime;
        var trafficIn = (prevIn && elapsed > 0) ? (newIn - prevIn) * 8 / elapsed : 0;
        measures = [Math.max(0, trafficIn), 0];
        sampleTime = newSample.time;
    }

    return {
        ifHCInOctets: newSample.ifHCInOctets,
        time: newSample.time,
        sampleTime: sampleTime,
        measures: measures
    };
}

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

// Simula exatamente o padrao observado ao vivo no sirius: contador do
// roteador avanca ~576Mbps de trafego real a cada 6s (poll a cada 3s, então
// soluca: mesmo valor, mesmo valor, salto, salto...).
var BYTES_PER_6S = Math.round(576000000 * 6 / 8); // ~576 Mbps reais
var stutterSamples = [
    {ifHCInOctets: 1000000, time: 0},
    {ifHCInOctets: 1000000, time: 3},                      // agente ainda não atualizou
    {ifHCInOctets: 1000000 + BYTES_PER_6S, time: 6},        // salto de 6s inteiro
    {ifHCInOctets: 1000000 + BYTES_PER_6S, time: 9},        // ainda não atualizou de novo
    {ifHCInOctets: 1000000 + BYTES_PER_6S * 2, time: 12},
    {ifHCInOctets: 1000000 + BYTES_PER_6S * 2, time: 15}
];

check('algoritmo ANTIGO oscila entre 0 e ~2x a taxa real (bug)', function () {
    var portData = {ifHCInOctets: stutterSamples[0].ifHCInOctets, time: stutterSamples[0].time, measures: [0, 0]};
    var mbpsSeries = [];
    for (var i = 1; i < stutterSamples.length; i++) {
        portData = oldSetNewData(portData, stutterSamples[i]);
        mbpsSeries.push(Math.round(portData.measures[0] / 1000000));
    }
    // esperado (bug): 0, ~2x576=1152, 0, ~1152, 0
    assert.deepStrictEqual(mbpsSeries, [0, 1152, 0, 1152, 0]);
});

check('algoritmo NOVO fica estável em ~576 Mbps (correto), sem zerar nem dobrar', function () {
    var portData = {ifHCInOctets: stutterSamples[0].ifHCInOctets, time: stutterSamples[0].time, sampleTime: stutterSamples[0].time, measures: [0, 0]};
    var mbpsSeries = [];
    for (var i = 1; i < stutterSamples.length; i++) {
        portData = newSetNewData(portData, stutterSamples[i]);
        mbpsSeries.push(Math.round(portData.measures[0] / 1000000));
    }
    // primeiro poll sem mudança: mantém o [0,0] inicial (não temos taxa
    // ainda). Depois disso, toda leitura com mudança real calcula ~576, e
    // toda leitura sem mudança MANTÉM o último valor (não zera).
    assert.deepStrictEqual(mbpsSeries, [0, 576, 576, 576, 576]);
});

check('contador subindo a cada poll (equipamento normal): taxa calculada a cada vez, sem regressão', function () {
    var samples = [
        {ifHCInOctets: 1000000, time: 0},
        {ifHCInOctets: 1000000 + 375000000, time: 3}, // 1 Gbps por 3s
        {ifHCInOctets: 1000000 + 750000000, time: 6},
        {ifHCInOctets: 1000000 + 1125000000, time: 9}
    ];
    var portData = {ifHCInOctets: samples[0].ifHCInOctets, time: samples[0].time, sampleTime: samples[0].time, measures: [0, 0]};
    var mbpsSeries = [];
    for (var i = 1; i < samples.length; i++) {
        portData = newSetNewData(portData, samples[i]);
        mbpsSeries.push(Math.round(portData.measures[0] / 1000000));
    }
    assert.deepStrictEqual(mbpsSeries, [1000, 1000, 1000]);
});

check('primeira leitura (sem history) nao calcula taxa (prevIn falsy/undefined)', function () {
    var portData = {ifHCInOctets: undefined, time: 0, sampleTime: 0, measures: [0, 0]};
    var result = newSetNewData(portData, {ifHCInOctets: 5000, time: 3});
    assert.strictEqual(result.measures[0], 0);
});

console.log('');
console.log(failures === 0 ? 'Todos os testes passaram.' : failures + ' teste(s) falharam.');
process.exit(failures > 0 ? 1 : 0);
