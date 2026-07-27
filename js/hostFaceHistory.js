// js/hostFaceHistory.js
// Pilha de undo/redo por snapshot, usada pelo editor de Host Face pra
// desfazer/refazer ações no canvas (colocar/mover/remover porta,
// preenchimento em área, trocar imagem). Não sabe nada de DOM: só guarda
// os snapshots que o chamador dá e devolve o snapshot certo pra reaplicar.

(function () {
    'use strict';

    function createHistory() {
        var undoStack = [];
        var redoStack = [];

        function record(snapshot) {
            undoStack.push(snapshot);
            redoStack = [];
        }

        function canUndo() {
            return undoStack.length > 0;
        }

        function canRedo() {
            return redoStack.length > 0;
        }

        function undo(current) {
            if (undoStack.length === 0) {
                return null;
            }
            redoStack.push(current);
            return undoStack.pop();
        }

        function redo(current) {
            if (redoStack.length === 0) {
                return null;
            }
            undoStack.push(current);
            return redoStack.pop();
        }

        return {
            record: record,
            canUndo: canUndo,
            canRedo: canRedo,
            undo: undo,
            redo: redo
        };
    }

    var HostFaceHistory = {
        createHistory: createHistory
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = HostFaceHistory;
    } else if (typeof window !== 'undefined') {
        window.HostFaceHistory = HostFaceHistory;
    }
})();
