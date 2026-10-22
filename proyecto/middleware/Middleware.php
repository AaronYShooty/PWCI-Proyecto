<?php
class Middleware
{
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
