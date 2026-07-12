<?php

namespace App\Core;

/**
 * Router - Sistema de enrutamiento simple pero potente
 * 
 * Gestiona las rutas de la aplicación y mapea URIs a controladores y métodos.
 * Soporta rutas dinámicas con parámetros y métodos HTTP específicos.
 * 
 * @package App\Core
 * @author ngenieria
 * @version 1.0.0
 */
class Router
{
    /**
     * Array de rutas registradas
     * Formato: ['método' => ['patrón' => ['controlador', 'método']]]
     * 
     * @var array
     */
    private array $routes = [];

    /**
     * Parámetros extraídos de la URI
     * 
     * @var array
     */
    private array $params = [];

    /**
     * Registrar una ruta GET
     * 
     * @param string $path Patrón de la ruta
     * @param string $controllerAction Formato: "ControllerName@method"
     * @return void
     */
    public function get(string $path, string $controllerAction): void
    {
        $this->addRoute('GET', $path, $controllerAction);
    }

    /**
     * Registrar una ruta POST
     * 
     * @param string $path Patrón de la ruta
     * @param string $controllerAction Formato: "ControllerName@method"
     * @return void
     */
    public function post(string $path, string $controllerAction): void
    {
        $this->addRoute('POST', $path, $controllerAction);
    }

    /**
     * Registrar una ruta PATCH
     * 
     * @param string $path Patrón de la ruta
     * @param string $controllerAction Formato: "ControllerName@method"
     * @return void
     */
    public function patch(string $path, string $controllerAction): void
    {
        $this->addRoute('PATCH', $path, $controllerAction);
    }

    /**
     * Registrar una ruta DELETE
     * 
     * @param string $path Patrón de la ruta
     * @param string $controllerAction Formato: "ControllerName@method"
     * @return void
     */
    public function delete(string $path, string $controllerAction): void
    {
        $this->addRoute('DELETE', $path, $controllerAction);
    }

    /**
     * Añadir una ruta al array de rutas
     * 
     * @param string $method Método HTTP
     * @param string $path Patrón de la ruta
     * @param string $controllerAction Formato: "ControllerName@method"
     * @return void
     */
    private function addRoute(string $method, string $path, string $controllerAction): void
    {
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][$path] = $controllerAction;
    }

    /**
     * Despachar la solicitud actual
     * 
     * Busca una ruta coincidente y ejecuta el controlador correspondiente.
     * 
     * @return void
     * @throws \Exception Si no se encuentra una ruta
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->extractUri();

        if (!isset($this->routes[$method])) {
            $this->handleNotFound();
            return;
        }

        foreach ($this->routes[$method] as $pattern => $action) {
            if ($this->matchRoute($pattern, $uri)) {
                $this->executeAction($action);
                return;
            }
        }

        $this->handleNotFound();
    }

    /**
     * Extraer la URI de la solicitud
     * 
     * @return string URI sin parámetros de query
     */
    private function extractUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        
        if ($basePath !== '/' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        
        return '/' . trim($uri, '/');
    }

    /**
     * Comprobar si una ruta coincide con la URI
     * 
     * @param string $pattern Patrón de la ruta
     * @param string $uri URI actual
     * @return bool
     */
    private function matchRoute(string $pattern, string $uri): bool
    {
        $patternRegex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            fn($matches) => '(?P<' . $matches[1] . '>[^/]+)',
            preg_quote($pattern, '#')
        );

        if (preg_match('#^' . $patternRegex . '$#', $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Ejecutar la acción del controlador
     * 
     * @param string $action Formato: "ControllerName@method"
     * @return void
     * @throws \Exception Si el controlador o método no existe
     */
    private function executeAction(string $action): void
    {
        [$controllerName, $method] = explode('@', $action);
        $controllerClass = "App\\Controllers\\" . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controlador no encontrado: {$controllerClass}");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \Exception("Método no encontrado: {$controllerClass}::{$method}");
        }

        call_user_func_array([$controller, $method], [$this->params]);
    }

    /**
     * Manejar solicitud no encontrada
     * 
     * @return void
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        echo json_encode([
            'error' => 'Ruta no encontrada',
            'path' => $this->extractUri(),
            'method' => $_SERVER['REQUEST_METHOD']
        ]);
    }
}
