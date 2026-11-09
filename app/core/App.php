<?php

class App
{
    // Tutorial controllers from the original MVC starter are retained in source
    // for reference but are not part of the IT Asset Management application.
    private const DISABLED_CONTROLLERS = ["about", "mahasiswa", "member"];

    protected $controller = "Home";
    protected $method = "index";
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseURL();

        $requestedController = $url[0] ?? "";
        if ($requestedController !== "") {
            if (
                in_array(
                    strtolower($requestedController),
                    self::DISABLED_CONTROLLERS,
                    true,
                ) ||
                !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $requestedController) ||
                !is_file("app/controllers/" . $requestedController . ".php")
            ) {
                $this->notFound();
                return;
            }
            $this->controller = $requestedController;
            unset($url[0]);
        }

        $controllerName = $this->controller;
        require_once "app/controllers/" . $controllerName . ".php";

        $requestedMethod = $url[1] ?? "";
        if ($requestedMethod !== "") {
            if (
                !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $requestedMethod) ||
                !method_exists($controllerName, $requestedMethod)
            ) {
                $this->notFound();
                return;
            }
            $method = new ReflectionMethod($controllerName, $requestedMethod);
            if (
                $method->isPublic() &&
                !$method->isConstructor() &&
                $method->getDeclaringClass()->getName() === $controllerName
            ) {
                $this->method = $requestedMethod;
                unset($url[1]);
            } else {
                $this->notFound();
                return;
            }
        }

        // The optional SaaS gate runs before controller constructors can open
        // a tenant database. It is a no-op while SaaS mode is disabled.
        SaasGate::enforce($controllerName, $this->method);
        $this->controller = new $controllerName();

        if (!empty($url)) {
            $this->params = array_values($url);
        }

        $dispatchMethod = new ReflectionMethod($this->controller, $this->method);
        if (count($this->params) < $dispatchMethod->getNumberOfRequiredParameters()) {
            http_response_code(404);
            echo "Not Found";
            return;
        }
        if (
            !$dispatchMethod->isVariadic() &&
            count($this->params) > $dispatchMethod->getNumberOfParameters()
        ) {
            $this->params = array_slice(
                $this->params,
                0,
                $dispatchMethod->getNumberOfParameters(),
            );
        }

        // Dispatch only the route parameters that remain after controller/method parsing.
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo "Not Found";
    }

    public function parseURL(): array
    {
        if (!isset($_GET["url"])) {
            return [];
        }

        $url = rtrim((string) $_GET["url"], "/");
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return $url === "" ? [] : explode("/", $url);
    }
}
