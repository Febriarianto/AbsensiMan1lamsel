<?php

namespace App\Http\Controllers;

use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class PageActionController extends Controller
{
    protected ?AuthToken $pageAuthToken = null;

    protected function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    protected function respondNoArgs(callable $handler): JsonResponse
    {
        return response()->json($handler());
    }

    protected function respondArgs(Request $request, callable $handler): JsonResponse
    {
        return response()->json($handler($this->extractArgs($request)));
    }

    protected function respondAuth(callable $handler): JsonResponse
    {
        return response()->json($handler($this->resolvePageAuthToken()));
    }

    protected function respondArgsAuth(Request $request, callable $handler): JsonResponse
    {
        return response()->json($handler($this->extractArgs($request), $this->resolvePageAuthToken()));
    }

    protected function extractArgs(Request $request): array
    {
        $args = $request->input('args', []);

        if (!is_array($args)) {
            return [$args];
        }

        return array_values($args);
    }

    protected function resolvePageAuthToken(): ?AuthToken
    {
        if ($this->pageAuthToken instanceof AuthToken) {
            return $this->pageAuthToken;
        }

        $user = request()->user();
        if (!$user instanceof User) {
            return null;
        }

        $clientRole = $this->resolvePageClientRole($user);
        $this->pageAuthToken = AuthToken::resolveActiveForUser($user, $clientRole)
            ->loadMissing(['user.roles']);

        return $this->pageAuthToken;
    }

    protected function resolvePageClientRole(User $user): string
    {
        $roleName = strtolower(trim((string) ($user->getRoleNames()->first() ?? '')));

        return $roleName === 'super-admin' ? 'admin' : $roleName;
    }
}
