<?php

namespace SAHM\ImageOptimizer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use SAHM\ImageOptimizer\Facades\ImageOptimizer;
use SAHM\ImageOptimizer\Http\Requests\ImageUploadRequest;

class ImageUploadController extends Controller
{
    /**
     * Upload and optimize image
     */
    public function upload(ImageUploadRequest $request): JsonResponse
    {
        try {
            $options = array_filter([
                'quality' => $request->input('quality'),
                'format' => $request->input('format'),
                'preset' => $request->input('preset'),
                'is_lcp' => $request->boolean('is_lcp'),
                'alt' => $request->input('alt'),
                'sizes_attr' => $request->input('sizes_attr'),
                'sizes_preset' => $request->input('sizes_preset'),
            ]);

            $imageData = ImageOptimizer::optimize($request->file('image'), $options);

            return response()->json([
                'success' => true,
                'data' => $imageData->toArray(),
                'message' => 'Image optimized successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get optimized image data
     */
    public function show(string $hash): JsonResponse
    {
        $imageData = ImageOptimizer::get($hash);

        if (!$imageData) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $imageData->toArray(),
        ]);
    }

    /**
     * Delete optimized image
     */
    public function destroy(string $hash): JsonResponse
    {
        $deleted = ImageOptimizer::delete($hash);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully',
        ]);
    }

    /**
     * Get system info
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'processor' => ImageOptimizer::getActiveProcessor(),
                'processor_info' => ImageOptimizer::getProcessorInfo(),
                'supported_formats' => ImageOptimizer::getSupportedFormats(),
                'stats' => ImageOptimizer::getStats(),
            ],
        ]);
    }
}
