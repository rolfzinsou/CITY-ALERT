<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Router — routage simple, regex sur path.
 *
 * Définition :
 *   $router->get('/', [HomeController::class, 'index']);
 *   $router->post('/signalements', [SignalementController::class, 'store'])
 *          ->middleware(AuthMiddleware::class);
 *   $router->get('/signalements/{id}', [SignalementController::class, 'show']);
 *
 * Le routeur capture les paramètres entre accolades et les passe à la méthode.
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:array{0:class-string, 1:string}, middlewares:array<class-string>}> */
    private array $routes = [];

    /** @var array<class-string> */
    private array $globalMiddlewares = [];

    public function get(string $path, array $handler): self    { return $this->add('GET',    $path, $handler); }
    public function post(string $path, array $handler): self   { return $this->add('POST',   $path, $handler); }
    public function put(string $path, array $handler): self    { return $this->add('PUT',    $path, $handler); }
    public function delete(string $path, array $handler): self { return $this->add('DELETE', $path, $handler); }

    private function add(string $method, string $path, array $handler): self
    {
        $this->routes[] = [
            'method'      => $method,
            'pattern'     => $this->compile($path),
            'handler'     => $handler,
            'middlewares' => [],
        ];
        return $this;
    }

    /**
     * Attache un middleware à la DERNIÈRE route définie.
     */
    public function middleware(string $middleware): self
    {
        $i = array_key_last($this->routes);
        if ($i === null) {
            throw new RuntimeException("Aucune route à laquelle attacher le middleware.");
        }
        $this->routes[$i]['middlewares'][] = $middleware;
        return $this;
    }

    public function useGlobal(string $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }

            // Pile de middlewares (globaux + spécifiques à la route)
            $stack = array_merge($this->globalMiddlewares, $route['middlewares']);
            foreach ($stack as $middlewareClass) {
                $middleware = new $middlewareClass();
                $result     = $middleware->handle($request);
                if ($result instanceof Response) {
                    return $result;   // Court-circuit (redirect login, 403, etc.)
                }
            }

            // Extraction des paramètres nommés (sans les clés numériques)
            $params = array_filter(
                $matches,
                fn($k) => !is_int($k),
                ARRAY_FILTER_USE_KEY
            );

            [$class, $method] = $route['handler'];
            $controller       = new $class();
            $response         = $controller->$method($request, ...array_values($params));

            return $response instanceof Response
                ? $response
                : Response::html((string) $response);
        }

        // Aucune route ne correspond → 404 avec template stylé
        return $this->rendre404();
    }

    private function rendre404(): Response
    {
        try {
            \App\Core\Session::start();
            $html = \App\Core\View::render('errors/404', [
                'message'            => "La page que vous cherchez n'existe pas ou a été déplacée.",
                'utilisateurCourant' => null,
                'flash'              => [],
                'csrf'               => \App\Core\Session::csrfToken(),
                'appName'            => APP_NAME,
            ]);
            return Response::html($html, 404);
        } catch (\Throwable) {
            return Response::notFound();
        }
    }

    /**
     * Compile "/signalements/{id}" en regex avec groupes nommés.
     */
    private function compile(string $path): string
    {
        $path  = '/' . trim($path, '/');
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }
}
