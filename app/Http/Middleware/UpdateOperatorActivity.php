<?php

namespace App\Http\Middleware;

use App\Enums\OperatorStatus;
use App\Models\Operator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateOperatorActivity
{
    /**
     * Handle an incoming request.
     * Keep the authenticated operator's presence status active and update last_activity_at.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Operator && $user->is_active) {
            $currentStatus = $user->status;
            $newStatus = ($currentStatus === OperatorStatus::BUSY) 
                ? OperatorStatus::BUSY 
                : OperatorStatus::ONLINE;

            $user->forceFill([
                'last_activity_at' => now(),
                'status' => $newStatus,
            ])->saveQuietly();
        }

        return $next($request);
    }
}
