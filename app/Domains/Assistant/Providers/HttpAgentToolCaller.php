<?php

namespace App\Domains\Assistant\Providers;

use App\Domains\Assistant\Contracts\ToolCallerContract;
use App\Domains\Assistant\Enums\AgentToolHttpMethod;
use App\Domains\Assistant\Enums\AgentToolParameterLocation;
use App\Domains\Assistant\Exceptions\ToolCallFailedException;
use App\Models\AgentTool;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Appel HTTP réel d'un outil déclaré (section 2.2, P3).
 *
 * Les valeurs substituées dans le chemin de l'URL sont systématiquement
 * encodées (`rawurlencode`) : un argument choisi par Claude à partir du
 * message d'un visiteur ne doit jamais pouvoir changer de route ou ajouter
 * un paramètre non prévu par simple contenu de chaîne (même risque
 * d'injection que l'échappement HTML des campagnes, section 2.9).
 */
final class HttpAgentToolCaller implements ToolCallerContract
{
    private const TIMEOUT_SECONDS = 10;

    private const MAX_RESULT_LENGTH = 4000;

    public function call(AgentTool $tool, array $arguments): string
    {
        $url = $this->buildUrl($tool, $arguments);
        $queryParameters = $this->parametersFor($tool, $arguments, AgentToolParameterLocation::Query);
        $bodyParameters = $this->parametersFor($tool, $arguments, AgentToolParameterLocation::Body);

        try {
            $request = Http::withHeaders($tool->headers ?? [])->timeout(self::TIMEOUT_SECONDS)->withQueryParameters($queryParameters);

            $response = match ($tool->http_method) {
                AgentToolHttpMethod::Get => $request->get($url),
                AgentToolHttpMethod::Post => $request->post($url, $bodyParameters),
                AgentToolHttpMethod::Put => $request->put($url, $bodyParameters),
                AgentToolHttpMethod::Patch => $request->patch($url, $bodyParameters),
                AgentToolHttpMethod::Delete => $request->delete($url, $bodyParameters),
            };

            $response->throw();

            return mb_substr($response->body(), 0, self::MAX_RESULT_LENGTH);
        } catch (Throwable) {
            throw ToolCallFailedException::make($tool->name);
        }
    }

    /** @param  array<string, mixed>  $arguments */
    private function buildUrl(AgentTool $tool, array $arguments): string
    {
        $url = $tool->url;

        foreach ($tool->parameters as $parameter) {
            if ($parameter['in'] === AgentToolParameterLocation::Path->value && isset($arguments[$parameter['name']])) {
                $url = str_replace('{'.$parameter['name'].'}', rawurlencode((string) $arguments[$parameter['name']]), $url);
            }
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function parametersFor(AgentTool $tool, array $arguments, AgentToolParameterLocation $location): array
    {
        $result = [];

        foreach ($tool->parameters as $parameter) {
            if ($parameter['in'] === $location->value && array_key_exists($parameter['name'], $arguments)) {
                $result[$parameter['name']] = $arguments[$parameter['name']];
            }
        }

        return $result;
    }
}
