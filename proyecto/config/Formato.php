<?php
// Formatos pedidos en la forma de evaluación: moneda con símbolo, coma de miles y dos decimales;
// fechas como día, tres letras del mes y año de 4 dígitos.
class Formato
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public static function fecha(?string $valor): string
    {
        $t = $valor ? strtotime($valor) : false;
        return $t ? date('d', $t) . ' ' . self::MESES[(int) date('n', $t) - 1] . ' ' . date('Y', $t) : '—';
    }

    public static function hora(?string $valor): string
    {
        $t = $valor ? strtotime($valor) : false;
        if (!$t) return '';
        return date('Y-m-d', $t) === date('Y-m-d') ? date('H:i', $t) : self::fecha($valor);
    }

    public static function moneda(float $valor): string
    {
        return '$' . number_format($valor, 2);
    }

    public static function estrellas(?float $promedio): string
    {
        if ($promedio === null) return '';
        $n = (int) round($promedio);
        return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
    }

    // Contenido del atributo style de un avatar: la foto del usuario o el fondo por defecto.
    public static function avatar(?string $foto, string $fondo = ''): string
    {
        if ($foto) {
            return htmlspecialchars("background-image:url('$foto'); background-size:cover; background-position:center;", ENT_QUOTES);
        }
        return htmlspecialchars($fondo, ENT_QUOTES);
    }
}