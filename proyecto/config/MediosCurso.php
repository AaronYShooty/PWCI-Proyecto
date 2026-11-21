<?php
class MediosCurso
{
    private const TIPOS = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'application/pdf' => 'pdf'];
    private const LIMITE_VIDEO = 100 * 1024 * 1024;
    private const LIMITE_PDF = 15 * 1024 * 1024;

    public function guardar(?array $archivo, bool $video): ?string
    {
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) return null;
        if ($error !== UPLOAD_ERR_OK || !is_string($archivo['tmp_name'] ?? null)
            || !is_uploaded_file($archivo['tmp_name'])) {
            throw new DomainException('No se pudo subir el archivo. Revisa su tamaño y vuelve a intentarlo.');
        }
        $temporal = $archivo['tmp_name'];
        $limite = $video ? self::LIMITE_VIDEO : self::LIMITE_PDF;
        $tamano = filesize($temporal);
        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        $permitidos = $video ? ['video/mp4', 'video/webm'] : ['application/pdf'];
        if (!$tamano || $tamano > $limite || !in_array($tipo, $permitidos, true)) {
            throw new DomainException($video ? 'Sube un video MP4 o WebM de hasta 100 MB.' : 'Sube un PDF de hasta 15 MB.');
        }

        $nombre = bin2hex(random_bytes(24)) . '.' . self::TIPOS[$tipo];
        if (!move_uploaded_file($temporal, $this->ruta($nombre))) {
            throw new RuntimeException('No se pudo guardar el archivo del curso.');
        }
        return $nombre;
    }

    public function disponible(string $nombre): bool
    {
        $ruta = $this->ruta($nombre);
        return $ruta !== null && is_file($ruta) && !is_link($ruta);
    }

    public function quitar(?string $nombre): void
    {
        if ($nombre !== null && $this->disponible($nombre) && !@unlink($this->ruta($nombre))) {
            error_log('No se pudo retirar un archivo anterior de un curso.');
        }
    }

    public function enviar(string $nombre, ?string $descarga = null): bool
    {
        if (!$this->disponible($nombre)) return false;
        $archivo = @fopen($this->ruta($nombre), 'rb');
        if ($archivo === false) return false;
        $tamano = fstat($archivo)['size'];
        $inicio = 0;
        $fin = $tamano - 1;
        $rango = !$descarga ? ($_SERVER['HTTP_RANGE'] ?? '') : '';
        $parcial = $rango !== '';

        try {
            if ($parcial) {
                $valido = preg_match('/^bytes=(\d*)-(\d*)$/D', $rango, $m) && ($m[1] !== '' || $m[2] !== '');
                if ($valido && $m[1] === '') {
                    $valido = (int) $m[2] > 0;
                    $inicio = max(0, $tamano - (int) $m[2]);
                } elseif ($valido) {
                    $inicio = (int) $m[1];
                    if ($m[2] !== '') $fin = min($fin, (int) $m[2]);
                }
                if (!$valido || $inicio > $fin) {
                    http_response_code(416);
                    header('Content-Range: bytes */' . $tamano);
                    return true;
                }
                http_response_code(206);
                header("Content-Range: bytes $inicio-$fin/$tamano");
            }

            $tipo = array_search(pathinfo($nombre, PATHINFO_EXTENSION), self::TIPOS, true);
            header('Content-Type: ' . $tipo);
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            if ($descarga !== null) {
                header("Content-Disposition: attachment; filename=\"material.pdf\"; filename*=UTF-8''" . rawurlencode($descarga));
            } else {
                header('Accept-Ranges: bytes');
            }
            $pendiente = $fin - $inicio + 1;
            header('Content-Length: ' . $pendiente);
            if ($_SERVER['REQUEST_METHOD'] === 'HEAD') return true;

            session_write_close();
            fseek($archivo, $inicio);
            while ($pendiente > 0 && !connection_aborted()) {
                $bloque = fread($archivo, min(65536, $pendiente));
                if ($bloque === false || $bloque === '') break;
                echo $bloque;
                $pendiente -= strlen($bloque);
                flush();
            }
            return true;
        } finally {
            fclose($archivo);
        }
    }

    private function ruta(string $nombre): ?string
    {
        return preg_match('/^[a-f0-9]{48}\.(mp4|webm|pdf)$/D', $nombre)
            ? dirname(__DIR__) . '/almacen/' . $nombre
            : null;
    }
}
