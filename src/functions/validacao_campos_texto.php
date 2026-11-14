<?php
function validarCamposTexto(array $campos): array
{
    $caracteresProibidos = ',/\\@<>#$%&*{}[]';
    $camposInvalidos = [];

    $validar = function (array $dados, string $prefixo = '') use (&$validar, &$camposInvalidos, $caracteresProibidos): void {
        foreach ($dados as $chave => $valor) {
            $nomeCampo = $prefixo === '' ? (string) $chave : $prefixo . '[' . $chave . ']';

            if (preg_match('/email|senha|password/i', (string) $chave)) {
                continue;
            }

            if (is_array($valor)) {
                $validar($valor, $nomeCampo);
            } elseif (is_string($valor) && strpbrk($valor, $caracteresProibidos) !== false) {
                $camposInvalidos[] = $nomeCampo;
            }
        }
    };

    $validar($campos);

    return array_values(array_unique($camposInvalidos));
}
