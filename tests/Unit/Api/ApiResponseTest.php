<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api;

use App\Api\ApiResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseTest extends TestCase
{
    public function testSuccessEnvelope(): void
    {
        $response = ApiResponse::success(['foo' => 'bar']);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(['success' => true, 'data' => ['foo' => 'bar']], $payload);
    }

    public function testSuccessWithCustomStatus(): void
    {
        $response = ApiResponse::success([], Response::HTTP_CREATED);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testErrorEnvelope(): void
    {
        $response = ApiResponse::error('not_found', 'Introuvable.', Response::HTTP_NOT_FOUND);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame([
            'success' => false,
            'error' => ['code' => 'not_found', 'message' => 'Introuvable.'],
        ], $payload);
    }

    public function testErrorEnvelopeWithDetails(): void
    {
        $response = ApiResponse::error('validation_failed', 'Invalide.', Response::HTTP_UNPROCESSABLE_ENTITY, [
            'fields' => ['name' => 'Ce champ est obligatoire.'],
        ]);

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(['fields' => ['name' => 'Ce champ est obligatoire.']], $payload['error']['details']);
    }

    public function testErrorCodeForStatusMapping(): void
    {
        $this->assertSame('bad_request', ApiResponse::errorCodeForStatus(400));
        $this->assertSame('unauthenticated', ApiResponse::errorCodeForStatus(401));
        $this->assertSame('forbidden', ApiResponse::errorCodeForStatus(403));
        $this->assertSame('not_found', ApiResponse::errorCodeForStatus(404));
        $this->assertSame('method_not_allowed', ApiResponse::errorCodeForStatus(405));
        $this->assertSame('conflict', ApiResponse::errorCodeForStatus(409));
        $this->assertSame('validation_failed', ApiResponse::errorCodeForStatus(422));
        $this->assertSame('too_many_requests', ApiResponse::errorCodeForStatus(429));
        $this->assertSame('service_unavailable', ApiResponse::errorCodeForStatus(503));
        $this->assertSame('server_error', ApiResponse::errorCodeForStatus(500));
        $this->assertSame('server_error', ApiResponse::errorCodeForStatus(418));
    }
}
