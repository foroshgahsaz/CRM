<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class WorkflowException extends RuntimeException implements ShouldntReport
{
    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}
