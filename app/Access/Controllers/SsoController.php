<?php

namespace BookStack\Access\Controllers;

use BookStack\Entities\Tools\SlugGenerator;
use BookStack\Http\Controller;
use BookStack\Translation\LocaleManager;
use BookStack\Users\Models\Role;
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
    public function __construct(
        protected SlugGenerator $slugGenerator,
        protected LocaleManager $localeManager,
    ) {
    }

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

        // Establecer idioma del usuario si viene en el payload y es un locale válido
        if (!empty($payload->language)) {
            $validLocales = $this->localeManager->getAllAppLocales();
            if (in_array($payload->language, $validLocales, true)) {
                setting()->putUser($user, 'language', $payload->language);
                app()->setLocale($payload->language);
            }
        }

        // Generar slug único usando SlugGenerator
        if ($user->wasRecentlyCreated || empty($user->slug)) {
            $this->slugGenerator->regenerateForUser($user);
            $user->save();
        }

        // Si es un usuario nuevo, le asignamos el rol de Viewer y opcionalmente el rol de Viewer-Admin (para los manuales de Administrador)
        if ($user->wasRecentlyCreated) {
            $viewerRole = Role::getRole('Viewer');
            if ($viewerRole) {
                $user->roles()->attach($viewerRole->id);
            }

            if($payload->is_admin ?? false) {
                $viewerAdminRole = Role::getRole('Viewer-Admin');
                if ($viewerAdminRole) {
                    $user->roles()->attach($viewerAdminRole->id);
                }
            }
        }

        // Actualizar nombre y slug si cambió en el backend principal
        if ($user->name !== $payload->name) {
            $user->name = $payload->name;
            $this->slugGenerator->regenerateForUser($user);
            $user->save();
        }

        Auth::login($user);

        // Regenerar sesión para prevenir session fixation
        $request->session()->regenerate();

        // Sanitizar redirect para evitar open redirect
        $parsed = parse_url($redirect);
        $safePath = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');

        return redirect($safePath);
    }
}