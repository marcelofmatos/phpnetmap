// js/hostFacePortStatus.js
// Funções puras (sem DOM) para aplicar o status SNMP ao vivo de uma porta
// (ifOperStatus/ifAdminStatus/dot1dStpPortState) como classes CSS — mesmo
// padrão já usado em host/_ports.php (SVG do host/view), reaproveitando as
// mesmas classes/cores já definidas em css/map.css (.ifOperStatus1 = verde,
// .ifOperStatus2 = preto, .ifAdminStatus2 = vermelho escuro,
// .ifOperStatus1.dot1dStpPortState2 = amarelo/STP block).

(function () {
    'use strict';

    var STATUS_CLASS_RE = /^(ifOperStatus|ifAdminStatus|dot1dStpPortState)\d+$/;

    /**
     * @param {{ifOperStatus?:number,ifAdminStatus?:number,dot1dStpPortState?:number}|null} status
     * @returns {string[]} classes de status a aplicar (vazio se sem dado)
     */
    function statusClassNames(status) {
        if (!status) {
            return [];
        }
        var classes = [];
        if (status.ifOperStatus) {
            classes.push('ifOperStatus' + status.ifOperStatus);
        }
        if (status.ifAdminStatus) {
            classes.push('ifAdminStatus' + status.ifAdminStatus);
        }
        if (status.dot1dStpPortState) {
            classes.push('dot1dStpPortState' + status.dot1dStpPortState);
        }
        return classes;
    }

    /**
     * Troca as classes de status antigas (se houver) pelas novas, preservando
     * qualquer outra classe já presente (ex.: host-face-editor-port).
     * @param {string} currentClassName
     * @param {{ifOperStatus?:number,ifAdminStatus?:number,dot1dStpPortState?:number}|null} status
     * @returns {string}
     */
    function withStatusClasses(currentClassName, status) {
        var kept = (currentClassName || '')
            .split(/\s+/)
            .filter(function (c) { return c && !STATUS_CLASS_RE.test(c); });
        return kept.concat(statusClassNames(status)).join(' ');
    }

    var HostFacePortStatus = {
        statusClassNames: statusClassNames,
        withStatusClasses: withStatusClasses
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = HostFacePortStatus;
    } else if (typeof window !== 'undefined') {
        window.HostFacePortStatus = HostFacePortStatus;
    }
})();
