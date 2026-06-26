<?php

namespace App\Http\Controllers;

use App\Http\Requests\TempUploadRequest;
use App\Services\Global\MediaService;
use Illuminate\Http\JsonResponse;

class TempUploadController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function store(TempUploadRequest $request): JsonResponse
    {
        return response()->json(
            $this->media->storeTempUpload(
                $request->file('file'),
                $request->input('context', 'generic'),
                $request->user()->id,
            )
        );
    }
}
