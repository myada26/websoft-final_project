<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transforms JSON API responses: snake_case keys → camelCase.
 * Also supports ?fields= query param to whitelist returned keys.
 * Lab Activity 6 — Section 7.2 API Response Optimization.
 */
class TransformApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $data = $response->getData(true);

        $fields = $request->query('fields')
            ? array_map('trim', explode(',', $request->query('fields')))
            : null;

        $data = $this->transform($data, $fields);

        return $response->setData($data);
    }

    private function transform(mixed $data, ?array $fields = null): mixed
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $camelKey = is_string($key) ? $this->toCamelCase($key) : $key;

                if ($fields !== null && is_string($key) && ! in_array($key, $fields, true) && ! in_array($camelKey, $fields, true)) {
                    continue;
                }

                $result[$camelKey] = $this->transform($value);
            }
            return $result;
        }

        return $data;
    }

    private function toCamelCase(string $key): string
    {
        return lcfirst(str_replace('_', '', ucwords($key, '_')));
    }
}
