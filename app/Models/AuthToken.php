<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class AuthToken extends Model
{
    use HasFactory;

    protected $table = 'auth_tokens';

    public $timestamps = false;

    protected $fillable = [
        'token',
        'user_id',
        'siswa_id',
        'role',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public static function resolveActiveForUser(User $user, string $role): self
    {
        $now = now();
        $session = request() instanceof Request && request()->hasSession()
            ? request()->session()
            : null;

        $sessionKey = 'page_auth_token_id_' . $user->getKey();
        $auth = null;
        $tokenId = $session?->get($sessionKey);

        if ($tokenId) {
            $auth = self::query()
                ->whereKey($tokenId)
                ->where('user_id', $user->getKey())
                ->where('expires_at', '>', $now)
                ->first();
        }

        if (!$auth) {
            $auth = self::query()
                ->where('user_id', $user->getKey())
                ->where('expires_at', '>', $now)
                ->latest('created_at')
                ->first();
        }

        if (!$auth) {
            $auth = self::query()->create([
                'token' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->getKey(),
                'siswa_id' => null,
                'role' => $role,
                'expires_at' => $now->copy()->addDay(),
                'created_at' => $now,
            ]);
        } elseif ((string) ($auth->role ?? '') !== $role) {
            $auth->forceFill([
                'role' => $role,
            ])->save();
        }

        if ($session && (string) $tokenId !== (string) $auth->getKey()) {
            $session->put($sessionKey, $auth->getKey());
        }

        return $auth;
    }
}
