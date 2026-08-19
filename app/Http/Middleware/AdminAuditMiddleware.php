<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $model = $this->resolveBoundModel($request);
        $before = null;

        $isMutatingExisting = $request->isMethod('PUT') || $request->isMethod('PATCH') || $request->isMethod('DELETE');

        if ($request->user()?->isAdmin() && $isMutatingExisting) {
            $before = $model?->getAttributes();
        }

        /** @var Response $response */
        $response = $next($request);

        if ($request->user()?->isAdmin() && ! $request->isMethod('GET') && $response->getStatusCode() < 400) {
            $after = null;

            if ($model && ($request->isMethod('PUT') || $request->isMethod('PATCH'))) {
                $after = $model->fresh()?->getAttributes();
            }

            app(AuditLogger::class)->record(
                $request->route()?->getName() ?? $request->method().' '.$request->path(),
                $model,
                [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'route_parameters' => $this->routeParameters($request),
                    'input' => $this->auditInput($request),
                    'before' => $before,
                    'after' => $after,
                ]
            );
        }

        return $response;
    }

    private function resolveBoundModel(Request $request): ?Model
    {
        foreach ($request->route()?->parameters() ?? [] as $value) {
            if ($value instanceof Model) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParameters(Request $request): array
    {
        return collect($request->route()?->parameters() ?? [])
            ->map(fn (mixed $value): mixed => $value instanceof Model
                ? ['model' => $value::class, 'id' => $value->getKey()]
                : $value)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function auditInput(Request $request): array
    {
        return collect($request->except(['password', 'password_confirmation', '_token', '_method']))
            ->map(fn (mixed $value): mixed => $this->normalizeAuditValue($value))
            ->all();
    }

    private function normalizeAuditValue(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return [
                'original_name' => $value->getClientOriginalName(),
                'mime_type' => $value->getMimeType(),
                'size' => $value->getSize(),
            ];
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeAuditValue($item), $value);
        }

        return $value;
    }
}
