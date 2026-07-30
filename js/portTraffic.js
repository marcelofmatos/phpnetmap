// Cálculo de taxa de tráfego (bits/s) a partir dos contadores de octeto SNMP
// (ifInOctets/ifOutOctets ou os HC de 64 bits) e da velocidade da porta
// (ifSpeed/ifHighSpeed). Compartilhado entre a página de Tráfego
// (host/_traffic.php, todas as portas) e o gráfico embutido no painel de
// porta (host/_ports.php, porta única) — ambos alimentam o mesmo bullet
// chart (js/d3/bullet.js) com o resultado deste cálculo.
(function (root) {

    // ifSpeed é um Gauge32 de 32 bits em bps — satura em ~4.29 Gbps (o
    // equipamento reporta o valor máximo como sentinela), então mostra escala
    // errada pra porta de 10G/40G/100G. ifHighSpeed (Mbps) não tem esse
    // limite. Os contadores de octeto têm a mesma limitação — os de 32 bits
    // (ifInOctets/ifOutOctets) dão a volta em poucos segundos numa porta
    // rápida saturada; os HC (64 bits) praticamente nunca dão. Prefere sempre
    // a versão sem esse limite, caindo pra clássica só se o equipamento não
    // reportar ifXTable.
    function octetsIn(p) {
        return (p.ifHCInOctets !== undefined && p.ifHCInOctets !== null) ? p.ifHCInOctets : p.ifInOctets;
    }

    function octetsOut(p) {
        return (p.ifHCOutOctets !== undefined && p.ifHCOutOctets !== null) ? p.ifHCOutOctets : p.ifOutOctets;
    }

    function speedBps(p) {
        return p.ifHighSpeed ? p.ifHighSpeed * 1000000 : (p.ifSpeed ? p.ifSpeed : 0);
    }

    // Atualiza `portData` (o datum do bullet chart) com a amostra mais nova
    // `portDataNew`, calculando a taxa em bits/s entre as duas leituras.
    //
    // Alguns equipamentos (confirmado num roteador Juniper) só atualizam os
    // próprios contadores de interface a cada ~6s, mais devagar que o poll de
    // 3s da página — metade das leituras chega com o contador exatamente
    // igual à anterior. Calcular a taxa a cada poll mesmo sem mudança real
    // faz ela oscilar entre 0 (nada mudou nesses 3s) e o dobro do valor real
    // (quando o contador salta, o delta de ~6s inteiro é dividido pelos 3s do
    // último poll). Em vez disso, guarda o timestamp da ÚLTIMA leitura que
    // realmente mudou (sampleTime) e só recalcula a taxa quando o contador se
    // move — usando o tempo decorrido desde essa última mudança de verdade,
    // não desde o poll anterior. Enquanto não muda, mantém a taxa exibida
    // como estava, sem zerar.
    function updateSample(portData, portDataNew, serverTime) {
        if (!portDataNew) {
            return portData;
        }

        var prevIn = octetsIn(portData);
        var prevOut = octetsOut(portData);
        var newIn = octetsIn(portDataNew);
        var newOut = octetsOut(portDataNew);

        if (portData.sampleTime === undefined) {
            portData.sampleTime = portData.time;
        }

        var changed = (prevIn !== newIn) || (prevOut !== newOut);

        if (changed) {
            var elapsed = serverTime - portData.sampleTime;
            var trafficIn = (prevIn && elapsed > 0) ? (newIn - prevIn) * 8 / elapsed : 0;
            var trafficOut = (prevOut && elapsed > 0) ? (newOut - prevOut) * 8 / elapsed : 0;
            portData.markers = portData.measures;
            portData.measures = [Math.max(0, trafficIn), Math.max(0, trafficOut)];
            portData.sampleTime = serverTime;
        }

        portData.ranges = [0, speedBps(portDataNew)];
        portData.time = serverTime;

        portData.ifInOctets = portDataNew.ifInOctets;
        portData.ifOutOctets = portDataNew.ifOutOctets;
        portData.ifHCInOctets = portDataNew.ifHCInOctets;
        portData.ifHCOutOctets = portDataNew.ifHCOutOctets;
        portData.ifSpeed = portDataNew.ifSpeed;
        portData.ifHighSpeed = portDataNew.ifHighSpeed;

        return portData;
    }

    var PortTraffic = {
        octetsIn: octetsIn,
        octetsOut: octetsOut,
        speedBps: speedBps,
        updateSample: updateSample
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = PortTraffic;
    } else {
        root.PortTraffic = PortTraffic;
    }

})(typeof window !== 'undefined' ? window : this);
