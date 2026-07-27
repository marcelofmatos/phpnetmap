// js/hostFacePortFilter.js
// Filtra uma lista de portas (formato usado no editor de Host Face:
// [{ifIndex, ifDescr, ifAlias}]) por uma substring digitada pelo operador,
// batendo contra o mesmo texto que aparece na lista (ex.: "port5 — dsc").

(function () {
    'use strict';

    function buildSearchText(port) {
        return ('port' + port.ifIndex + ' ' +
            (port.ifDescr || '') + ' ' + (port.ifAlias || '')).toLowerCase();
    }

    function filterPortsByQuery(ports, query) {
        var q = (query || '').trim().toLowerCase();
        if (!q) {
            return ports.slice();
        }
        return ports.filter(function (port) {
            return buildSearchText(port).indexOf(q) !== -1;
        });
    }

    var HostFacePortFilter = {
        buildSearchText: buildSearchText,
        filterPortsByQuery: filterPortsByQuery
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = HostFacePortFilter;
    } else if (typeof window !== 'undefined') {
        window.HostFacePortFilter = HostFacePortFilter;
    }
})();
