<?php

namespace BookStack\Access\Controllers;

use BookStack\Http\Controller;
use BookStack\Users\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Recibe un JWT desde el backend principal, valida el token,
     * crea o recupera el usuario y lo autentica con sesión.
     */
    public function login(Request $request)
    {
        $token = $request->query('token');
        $redirect = $request->query('redirect', '/');

        if (! $token) {
            abort(400, 'Token is required.');
        }

        try {
            $payload = JWT::decode($token, new Key(config('app.jwt_secret'), 'HS256'));
        } catch (ExpiredException $e) {
            abort(401, 'Token has expired.');
        } catch (SignatureInvalidException $e) {
            abort(401, 'Invalid token signature.');
        } catch (\Exception $e) {
            abort(401, 'Invalid token.');
        }

        // Prevenir replay: cada jti solo se puede usar una vez
        $jtiCacheKey = 'sso_jti_' . $payload->jti;

        if (Cache::has($jtiCacheKey)) {
            abort(401, 'Token has already been used.');
        }

        // Guardar el jti en cache durante 120s (más que la vida del token)
        Cache::put($jtiCacheKey, true, 120);

        // Crear o recuperar el usuario
        $user = User::firstOrCreate(
            ['email' => $payload->email],
            [
                'name' => $payload->name,
                'password' => bcrypt(Str::random(32)),
            ]
        );

        // Asignar rol Viewer (ID 3) si es un usuario nuevo (recién creado)
        if ($user->wasRecentlyCreated) {
            $user->roles()->attach(3);
        }

        // Actualizar nombre si cambió en el backend principal
        if ($user->name !== $payload->name) {
            $user->update(['name' => $payload->name]);
        }

        Auth::login($user);

        // Regenerar sesión para prevenir session fixation
        $request->session()->regenerate();

        // Sanitizar redirect para evitar open redirect
        $safePath = parse_url($redirect, PHP_URL_PATH) ?: '/';

        return redirect($safePath);
    }
}