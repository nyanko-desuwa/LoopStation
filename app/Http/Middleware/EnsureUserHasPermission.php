<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    /**
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$codes  Một hoặc nhiều permission code (OR logic).
     */
    public function handle(Request $request, Closure $next, string ...$codes): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        foreach ($codes as $code) {
            if ($this->permissions->userHas($user, $code)) {
                return $next($request);
            }
        }

        abort(403, __('permissions.messages.forbidden'));
    }
}
