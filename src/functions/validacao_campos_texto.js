(function () {
    const caracteresProibidos = new Set([',', '/', '\\', '@', '<', '>', '#', '$', '%', '&', '*', '{', '}', '[', ']']);
    const tiposDeTexto = new Set(['text', 'search', 'url', 'tel']);

    function deveValidar(campo) {
        if (!(campo instanceof HTMLInputElement || campo instanceof HTMLTextAreaElement)) {
            return false;
        }

        if (campo instanceof HTMLInputElement && !tiposDeTexto.has(campo.type)) {
            return false;
        }

        return !/(email|senha|password)/i.test(campo.name);
    }

    function removerCaracteresProibidos(valor) {
        return Array.from(valor).filter((caractere) => !caracteresProibidos.has(caractere)).join('');
    }

    document.addEventListener('beforeinput', function (evento) {
        if (!deveValidar(evento.target) || typeof evento.data !== 'string') {
            return;
        }

        if (Array.from(evento.data).some((caractere) => caracteresProibidos.has(caractere))) {
            evento.preventDefault();
        }
    });

    document.addEventListener('input', function (evento) {
        const campo = evento.target;
        if (!deveValidar(campo)) {
            return;
        }

        const valorOriginal = campo.value;
        const valorLimpo = removerCaracteresProibidos(valorOriginal);
        if (valorOriginal === valorLimpo) {
            return;
        }

        const cursor = campo.selectionStart;
        const prefixoLimpo = removerCaracteresProibidos(valorOriginal.slice(0, cursor));
        campo.value = valorLimpo;
        campo.setSelectionRange(prefixoLimpo.length, prefixoLimpo.length);
    });
})();
