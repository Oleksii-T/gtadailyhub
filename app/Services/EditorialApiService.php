<?php

namespace App\Services;

use App\Models\EditorialApiEvent;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditorialApiService
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  Closure(): array{0: int, 1: string, 2: ?array, 3: array, 4: array}  $operation
     * @return array<string, mixed>
     */
    public function mutate(string $entityType, string $editorialKey, string $requestId, array $payload, Closure $operation): array
    {
        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        try {
            $result = DB::transaction(function () use ($entityType, $editorialKey, $requestId, $payloadHash, $operation): array {
                $previous = EditorialApiEvent::query()->where('request_id', $requestId)->first();
                if ($previous !== null) {
                    return $this->replay($previous, $entityType, $editorialKey, $payloadHash);
                }

                [$entityId, $action, $before, $after, $response] = $operation();
                $response['revision'] = EditorialApiEvent::query()->where('entity_type', $entityType)->where('entity_id', $entityId)->count() + 1;
                $event = EditorialApiEvent::query()->create([
                    'request_id' => $requestId,
                    'editorial_key' => $editorialKey,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'operation' => $action,
                    'payload_hash' => $payloadHash,
                    'before_summary' => $before,
                    'after_summary' => $after,
                    'result' => $response,
                ]);

                $response['event_id'] = $event->id;
                $event->update(['result' => $response]);

                return $response;
            }, 3);
        } catch (QueryException $exception) {
            $previous = EditorialApiEvent::query()->where('request_id', $requestId)->first();
            if ($previous === null) {
                throw $exception;
            }
            $result = $this->replay($previous, $entityType, $editorialKey, $payloadHash);
        }

        $event = EditorialApiEvent::query()->where('request_id', $requestId)->firstOrFail();
        Log::info('editorial_api.mutation', [
            'request_id' => $requestId,
            'entity_type' => $entityType,
            'editorial_key' => $editorialKey,
            'entity_id' => $result['id'],
            'action' => $result['action'],
            'revision' => $result['revision'],
            'event_id' => $result['event_id'],
            'replayed' => $result['replayed'] ?? false,
            'url' => $result['url'],
            'before' => $event->before_summary,
            'after' => $event->after_summary,
            'result' => ($result['replayed'] ?? false) ? 'replayed' : 'success',
        ]);

        return $result;
    }

    /** @return array<string, mixed> */
    private function replay(EditorialApiEvent $event, string $entityType, string $editorialKey, string $payloadHash): array
    {
        if ($event->entity_type !== $entityType || $event->editorial_key !== $editorialKey || $event->payload_hash !== $payloadHash) {
            abort(409, 'Request ID was already used for a different editorial mutation.');
        }

        return [...$event->result, 'replayed' => true];
    }
}
