<?php
// Ejecuta una lista de middlewares en el orden dado. Cada uno puede cortar la
// petición (redirige o responde con error y hace exit); si no corta, sigue el siguiente.
class Middleware
{
    // Nombres: 'cors' o 'acceso:rol1,rol2'. $esApi decide si el acceso responde JSON o redirige.
    public static function ejecutar(array $lista, bool $esApi = false): void
    {
        foreach ($lista as $item) {
            [$nombre, $parametro] = array_pad(explode(':', $item, 2), 2, '');
            $roles = $parametro === '' ? [] : explode(',', $parametro);

            match ($nombre) {
                'cors'   => Cors::manejar(),
                'acceso' => $esApi ? ControlAcceso::exigir($roles) : ControlAcceso::exigirVista($roles),
                default  => throw new InvalidArgumentException("Middleware desconocido: $nombre"),
            };
        }
    }
}
