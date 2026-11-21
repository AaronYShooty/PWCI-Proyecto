<?php
class PortadaCurso
{
    private const DIRECTORIO = 'publico/uploads/portadas';
    private const LIMITE = 5 * 1024 * 1024;
    private const EXTENSIONES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function guardar(array $archivo): string
    {
        $temporal = $archivo['tmp_name'] ?? null;
        if (($archivo['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($temporal) || !is_uploaded_file($temporal)) {
            throw new DomainException('Selecciona un banner válido de hasta 5 MB.');
        }

        $tamano = filesize($temporal);
        if (!$tamano || $tamano > self::LIMITE) {
            throw new DomainException('El banner puede pesar hasta 5 MB.');
        }
        $imagen = @getimagesize($temporal);
        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        $extension = self::EXTENSIONES[$tipo] ?? null;
        if (!$extension || ($imagen['mime'] ?? null) !== $tipo) {
            throw new DomainException('El banner debe ser una imagen JPG, PNG o WebP.');
        }

        $raiz = dirname(__DIR__);
        $carpeta = $raiz . '/' . self::DIRECTORIO;
        if (!is_dir($carpeta) && !@mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
            throw new RuntimeException('No se pudo crear la carpeta de banners.');
        }
        $ruta = self::DIRECTORIO . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($temporal, $raiz . '/' . $ruta)) {
            throw new RuntimeException('No se pudo guardar el banner.');
        }
        return $ruta;
    }

    public function descartar(string $ruta): void
    {
        if (!preg_match('~^publico/uploads/portadas/[a-f0-9]{32}\.(jpg|png|webp)$~D', $ruta)) return;
        $archivo = dirname(__DIR__) . '/' . $ruta;
        if (is_file($archivo) && !@unlink($archivo)) error_log('No se pudo descartar una portada no guardada.');
    }
}
