<?php

namespace App\Http\Controllers;

use App\Services\Modules\AlumniRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataAlumniController extends PageActionController
{
    public function __construct(
        protected AlumniRecordService $alumniRecords,
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($this->shouldReturnJson($request)) {
            return $this->respondArgsAuth($request, fn (array $args, $auth) => $this->alumniRecords->getAlumniList($args, $auth));
        }

        return view('pages.data-alumni');
    }

    public function destroy(Request $request): JsonResponse
    {
        return $this->respondArgsAuth($request, fn (array $args, $auth) => $this->alumniRecords->deleteAlumni($args, $auth));
    }

    public function restore(Request $request): JsonResponse
    {
        return $this->respondArgsAuth($request, fn (array $args, $auth) => $this->alumniRecords->restoreToSiswa($args, $auth));
    }
}
