// js/portCombobox.js
// Combobox de porta reutilizável (texto + lista clicável que filtra ao
// digitar), plugado num <input> de texto já existente na página. Usado
// tanto no editor de Host Face (modal de editar porta) quanto no
// formulário de Connection (escolher porta de origem/destino). Depende de
// js/hostFacePortFilter.js pro filtro por substring.

(function () {
    'use strict';

    function defaultFormatLabel(port) {
        return 'port' + port.ifIndex + (port.ifDescr ? ' — ' + port.ifDescr : '');
    }

    /**
     * @param {HTMLElement} container elemento já no DOM com a classe
     *   "port-combobox" (position:relative) e o input já dentro dele — a
     *   lista dropdown é ancorada nesse container, não direto no body, pra
     *   funcionar dentro de qualquer layout (form comum, modal, etc.).
     * @param {HTMLInputElement} input campo de texto, filho de container.
     * @param {function(): Array} getPorts portas atuais, avaliado a cada
     *   abertura/tecla — [{ifIndex, ifDescr, ifAlias, disabled, disabledTitle}]
     * @param {function(Object)} onSelect chamado ao clicar numa opção
     *   habilitada, depois do valor já escrito no input.
     * @param {{formatLabel: function=, selectValue: function=}} [opts]
     *   formatLabel(port): texto de cada opção (default "port5 — dsc").
     *   selectValue(port): valor escrito no input ao clicar (default: o
     *   próprio label formatado — passe algo como `String(port.ifIndex)`
     *   quando o input for o valor real submetido, não só um rótulo).
     */
    function attach(container, input, getPorts, onSelect, opts) {
        opts = opts || {};
        var formatLabel = opts.formatLabel || defaultFormatLabel;
        var selectValue = opts.selectValue || formatLabel;

        var list = document.createElement('div');
        list.className = 'port-combobox-list';
        list.style.display = 'none';
        container.appendChild(list);
        input.classList.add('port-combobox-input');

        function renderOptions(query) {
            list.innerHTML = '';
            var ports = HostFacePortFilter.filterPortsByQuery(getPorts(), query);
            if (ports.length === 0) {
                var empty = document.createElement('div');
                empty.className = 'port-combobox-empty';
                empty.textContent = 'No matching ports.';
                list.appendChild(empty);
                return;
            }
            ports.forEach(function (port) {
                var optEl = document.createElement('div');
                optEl.className = 'port-combobox-option' + (port.disabled ? ' port-combobox-option-disabled' : '');
                optEl.textContent = formatLabel(port);
                if (port.disabled) {
                    if (port.disabledTitle) {
                        optEl.title = port.disabledTitle;
                    }
                } else {
                    optEl.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        input.value = selectValue(port);
                        list.style.display = 'none';
                        onSelect(port);
                    });
                }
                list.appendChild(optEl);
            });
        }

        input.addEventListener('focus', function () {
            // Ao abrir, mostra a lista inteira — o texto já preenchido é o
            // valor atual, não uma busca. Filtra só conforme o operador
            // digita algo depois disso.
            renderOptions('');
            list.style.display = 'block';
            input.select();
        });
        input.addEventListener('input', function () {
            renderOptions(input.value);
            list.style.display = 'block';
        });
        input.addEventListener('blur', function () {
            // Atraso pra dar tempo do mousedown da opção rodar primeiro.
            setTimeout(function () { list.style.display = 'none'; }, 150);
        });
    }

    var PortCombobox = {
        attach: attach
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = PortCombobox;
    } else if (typeof window !== 'undefined') {
        window.PortCombobox = PortCombobox;
    }
})();
