<?php

declare(strict_types=1);

namespace Tests\Apoyo;

// Navegador mínimo: guarda la cookie de sesión y no sigue redirecciones.
final class Cliente
{
    private \CurlHandle $curl;

    public function __construct(private readonly string $base)
    {
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_COOKIEFILE => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
    }

    /** @return array{codigo: int, cabeceras: array<string, string>, cuerpo: string} */
    public function get(string $ruta): array
    {
        return $this->pedir('GET', $ruta);
    }

    /**
     * @param array<string, string> $datos
     * @return array{codigo: int, cabeceras: array<string, string>, cuerpo: string}
     */
    public function post(string $ruta, array $datos): array
    {
        return $this->pedir('POST', $ruta, $datos);
    }

    // El token CSRF del formulario de la ruta.
    public function token(string $ruta): string
    {
        preg_match('/name="csrf" value="([0-9a-f]{64})"/', $this->get($ruta)['cuerpo'], $m);

        return $m[1] ?? throw new \LogicException("Sin token CSRF en {$ruta}.");
    }

    // Id de la sesión; null si no hay cookie.
    public function sesion(): ?string
    {
        foreach (curl_getinfo($this->curl, CURLINFO_COOKIELIST) as $linea) {
            $campos = explode("\t", $linea);
            if ($campos[5] === 'casos_sesion') {
                return $campos[6];
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $datos
     * @return array{codigo: int, cabeceras: array<string, string>, cuerpo: string}
     */
    public function pedir(string $metodo, string $ruta, array $datos = []): array
    {
        if ($metodo === 'GET') {
            curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        curl_setopt($this->curl, CURLOPT_URL, $this->base . $ruta);
        $texto = (string) curl_exec($this->curl);
        $tam = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $cabeceras = [];
        foreach (explode("\r\n", substr($texto, 0, $tam)) as $linea) {
            if (str_contains($linea, ':')) {
                [$nombre, $valor] = explode(':', $linea, 2);
                $cabeceras[strtolower($nombre)] = trim($valor);
            }
        }

        return ['codigo' => curl_getinfo($this->curl, CURLINFO_RESPONSE_CODE), 'cabeceras' => $cabeceras, 'cuerpo' => substr($texto, $tam)];
    }
}
