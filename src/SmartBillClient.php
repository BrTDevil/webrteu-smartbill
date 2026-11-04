<?php

declare(strict_types=1);

namespace WeBRTeu\SmartBill;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use WeBRTeu\SmartBill\Exceptions\SmartBillException;

/**
 * HTTP brut spre SmartBill Cloud API: Basic auth (email:token), JSON. Fără retry (risc de factură dublă).
 * Limita SmartBill: 30 de apeluri la 10 secunde.
 */
class SmartBillClient
{
    /**
     * @param  array<string, scalar>  $query
     * @param  array<string, mixed>  $body
     * @return array<array-key, mixed>
     */
    public function send(string $method, string $path, array $query = [], array $body = []): array
    {
        $url = $this->url($path, $query);
        $request = $this->request()->acceptJson();
        $response = $body === [] ? $request->send($method, $url) : $request->asJson()->send($method, $url, ['json' => $body]);

        $json = $response->json();
        $json = is_array($json) ? $json : [];
        $error = is_scalar($json['errorText'] ?? null) ? trim((string) $json['errorText']) : '';

        if ($response->failed() || $error !== '') {
            $this->fail($method, $path, $response, $error !== '' ? $error : null);
        }

        return $json;
    }

    /**
     * @param  array<string, scalar>  $query
     */
    public function download(string $path, array $query): string
    {
        $response = $this->request()->accept('application/octet-stream')->get($this->url($path, $query));

        if ($response->failed()) {
            $json = $response->json();
            $error = is_array($json) && is_scalar($json['errorText'] ?? null) ? (string) $json['errorText'] : null;

            $this->fail('GET', $path, $response, $error);
        }

        return $response->body();
    }

    public function cif(): string
    {
        return Config::string('smartbill.cif', '');
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth(Config::string('smartbill.username', ''), Config::string('smartbill.token', ''))
            ->timeout(Config::integer('smartbill.timeout', 30));
    }

    /**
     * @param  array<string, scalar>  $query
     */
    private function url(string $path, array $query): string
    {
        $url = rtrim(Config::string('smartbill.base_url', 'https://ws.smartbill.ro/SBORO/api'), '/').$path;

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    private function fail(string $method, string $path, Response $response, ?string $error): never
    {
        Log::warning('smartbill.request_failed', ['path' => $path, 'status' => $response->status(), 'error' => $error]);

        throw new SmartBillException(sprintf('SmartBill %s %s a eșuat (HTTP %d): %s', $method, $path, $response->status(), $error ?? 'eroare necunoscută'));
    }
}
