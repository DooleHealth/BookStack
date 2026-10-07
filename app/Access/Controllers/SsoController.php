<?php

namespace BookStack\Access\Controllers;

use BookStack\Entities\Tools\SlugGenerator;
use BookStack\Http\Controller;
use BookStack\Http\Middleware\RestrictEmbedSession;
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

        // Alcance "embed": el token pide que la sesión quede atada a una única versión de libro.
        $embedScope = null;
        if (($payload->scope ?? null) === 'embed') {
            $bookSlug = trim((string) ($payload->book ?? ''));
            $versionSlug = trim((string) ($payload->version ?? ''));

            if (!preg_match('/^[A-Za-z0-9\-]+$/', $bookSlug) || !preg_match('/^[A-Za-z0-9\-]+$/', $versionSlug)) {
                abort(400, 'An embed-scoped token requires a valid book and version slug.');
            }

            $embedScope = ['book' => $bookSlug, 'version' => $versionSlug];
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

        // El alcance del embed vive en la sesión, no en la URL: quitar parámetros de la query
        // o abrir la URL en otra pestaña no amplía lo que el usuario puede ver.
        if ($embedScope) {
            $request->session()->put(RestrictEmbedSession::SESSION_KEY, $embedScope);
        } else {
            $request->session()->forget(RestrictEmbedSession::SESSION_KEY);
        }

        // Sanitizar redirect para evitar open redirect
        $parsed = parse_url($redirect);
        $safePath = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');

        return redirect($safePath);
    }
}