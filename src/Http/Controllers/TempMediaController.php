<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Http\Controllers;

use BardanIO\TempMedia\Contracts\TempMediaServiceInterface;
use BardanIO\TempMedia\Exceptions\InvalidFileException;
use BardanIO\TempMedia\Http\Requests\TempMediaUploadRequest;
use BardanIO\TempMedia\Http\Requests\TempMediaValidateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

final class TempMediaController extends Controller
{
    public function __construct(
        private readonly TempMediaServiceInterface $tempMediaService
    ) {}

    public function upload(TempMediaUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $sessionId = $request->input('session_id', session()->getId());

            $dto = $this->tempMediaService->uploadTempMedia(
                $file,
                $sessionId,
            );

            return response()->json($dto->toJsonResponse(), ResponseAlias::HTTP_CREATED);

        } catch (InvalidFileException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], ResponseAlias::HTTP_BAD_REQUEST);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Upload failed',
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id): JsonResponse
    {
        $tempMedia = $this->tempMediaService->getTempMedia($id);

        if (! $tempMedia) {
            return response()->json([
                'success' => false,
                'error' => 'Temp media not found or expired',
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        $mediaItem = $tempMedia->getFirstMedia($tempMedia->getCollectionName());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tempMedia->id,
                'url' => $mediaItem?->getUrl(),
                'thumb_url' => $mediaItem?->getUrl('thumb'),
                'original_name' => $tempMedia->original_name,
                'size' => $tempMedia->size,
                'mime_type' => $tempMedia->mime_type,
                'expires_at' => $tempMedia->expires_at->toISOString(),
            ],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->tempMediaService->deleteTempMedia($id);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'error' => 'Temp media not found',
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Temp media deleted successfully',
        ]);
    }

    public function validate(TempMediaValidateRequest $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        try {
            $validTempMedia = $this->tempMediaService->validateTempMediaIds($ids);

            return response()->json([
                'success' => true,
                'data' => [
                    'valid_ids' => array_column($validTempMedia, 'id'),
                    'count' => count($validTempMedia),
                ],
            ]);

        } catch (InvalidFileException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }
    }
}
