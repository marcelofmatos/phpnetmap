// js/portCombobox.js
// Combobox reutilizável (texto + lista clicável que filtra ao digitar),
// plugado num <input> de texto já existente na página. Apesar do nome
// (nasceu pra escolher porta), serve pra filtrar qualquer lista pequena de
// itens — usado no editor de Host Face (modal de editar porta e no combo
// de host de referência), no formulário de Connection (escolher porta de
// origem/destino) e em qualquer outro lugar que precise do mesmo padrão.
// Por padrão filtra portas via js/hostFacePortFilter.js; passe
// opts.filterItems pra filtrar outro tipo de item (ex.: hosts por nome).

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
     * @param {function(): Array} getItems itens atuais, avaliado a cada
     *   abertura/tecla — por padrão portas [{ifIndex, ifDescr, ifAlias,
     *   disabled, disabledTitle}], filtradas via hostFacePortFilter.js; pra
     *   filtrar outro tipo de item (ex.: hosts), passe opts.filterItems.
     * @param {function(Object)} onSelect chamado ao clicar numa opção
     *   habilitada, depois do valor já escrito no input.
     * @param {{formatLabel: function=, selectValue: function=, filterItems: function=}} [opts]
     *   formatLabel(item): texto de cada opção (default "port5 — dsc").
     *   selectValue(item): valor escrito no input ao clicar (default: o
     *   próprio label formatado — passe algo como `String(port.ifIndex)`
     *   quando o input for o valor real submetido, não só um rótulo).
     *   filterItems(items, query): filtra a lista pela busca digitada
     *   (default: HostFacePortFilter.filterPortsByQuery, assume formato de
     *   porta — obrigatório informar pra qualquer outro tipo de item).
     */
    function attach(container, input, getItems, onSelect, opts) {
        opts = opts || {};
        var formatLabel = opts.formatLabel || defaultFormatLabel;
        var selectValue = opts.selectValue || formatLabel;
        var filterItems = opts.filterItems || HostFacePortFilter.filterPortsByQuery;

        var list = document.createElement('div');
        list.className = 'port-combobox-list';
        list.style.display = 'none';
        container.appendChild(list);
        input.classList.add('port-combobox-input');

        function renderOptions(query) {
            list.innerHTML = '';
            var items = filterItems(getItems(), query);
            if (items.length === 0) {
                var empty = document.createElement('div');
                empty.className = 'port-combobox-empty';
                empty.textContent = 'No matching options.';
                list.appendChild(empty);
                return;
            }
            items.forEach(function (item) {
                var optEl = document.createElement('div');
                optEl.className = 'port-combobox-option' + (item.disabled ? ' port-combobox-option-disabled' : '');
                optEl.textContent = formatLabel(item);
                if (item.disabled) {
                    if (item.disabledTitle) {
                        optEl.title = item.disabledTitle;
                    }
                } else {
                    optEl.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        input.value = selectValue(item);
                        list.style.display = 'none';
                        onSelect(item);
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
