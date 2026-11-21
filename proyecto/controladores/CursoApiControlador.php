<?php
class CursoApiControlador extends ApiControlador
{
    private const NIVEL_MAX = 5;
    private const POR_PAGINA = 10;
    private const POR_PAGINA_MAX = 50;
    private const ID_MAX = 4294967295;
    private const CAMPOS = ['titulo', 'descripcion', 'nivel', 'precio', 'categorias', 'imagen', 'activo'];
    private const ORDENES = ['recientes', 'calificacion', 'ventas'];

    private ?Curso $cursos = null;
    private ?Categoria $categorias = null;

    public function __construct(private Conexion $conexion, private PortadaCurso $portadas) {}

    public function atender(string $metodo, ?string $id): void
    {
        try {
            $multipart = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) === 'multipart/form-data';
            if ($metodo === 'GET') {
                $id === null ? $this->listar() : $this->detalle($id);
                return;
            }
            if ($metodo === 'POST' && $id === null) {
                $this->crear($multipart);
                return;
            }
            if ($metodo === 'POST' && $id !== null && $multipart) {
                $this->modificar('PATCH', $id, true);
                return;
            }
            if ($id !== null && in_array($metodo, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->modificar($metodo, $id);
                return;
            }
            header($id === null ? 'Allow: GET, POST' : 'Allow: GET, POST, PUT, PATCH, DELETE');
            $this->fallo('Método no permitido.', 405);
        } catch (DomainException $e) {
            $this->fallo($e->getMessage(), 409);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fallo('Error interno del servidor.', 500);
        }
    }

    private function listar(): void
    {
        $consulta = $this->leerConsulta($_GET);
        if ($consulta['errores']) {
            $this->fallo('Los parámetros de la consulta no son válidos.', 400, $consulta['errores']);
            return;
        }

        $cursos = $this->cursos()->buscar($consulta['filtros'], $consulta['pagina'], $consulta['por_pagina'], $consulta['orden']);
        $this->exito('Consulta realizada correctamente', $cursos);
    }

    private function detalle(string $id): void
    {
        $numero = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $curso = $numero === false ? null : $this->cursos()->obtenerPorId($numero);

        if (!$curso) {
            $this->fallo('Curso no encontrado.', 404);
            return;
        }
        $this->exito('Consulta realizada correctamente', $curso);
    }

    private function crear(bool $multipart): void
    {
        $cuerpo = $this->leerDatosCurso($multipart);
        if ($cuerpo === null) return;

        $errores = $this->validarCurso($cuerpo, false);
        if ($errores) {
            $this->fallo('Los datos enviados no son válidos.', 400, $errores);
            return;
        }

        if (($cuerpo['activo'] ?? false) === true) {
            $this->fallo('Crea el curso como borrador y agrega sus videos antes de publicarlo.', 409);
            return;
        }
        $datos = $this->normalizar($cuerpo, true) + ['instructor_id' => (int) $_SESSION['usuario']['id'], 'activo' => false];
        $portada = $multipart ? $this->guardarPortada() : null;
        if ($multipart && $portada === null) return;
        if ($portada !== null) $datos['imagen'] = $portada;
        try {
            $id = $this->cursos()->crear($datos);
        } catch (Throwable $e) {
            if ($portada !== null) $this->portadas->descartar($portada);
            throw $e;
        }

        $this->exito('Curso creado correctamente', $this->cursos()->obtenerPorId($id, false), 201);
    }

    private function modificar(string $metodo, string $id, bool $multipart = false): void
    {
        $numero = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $curso = $numero === false ? null : $this->cursos()->obtenerPorId($numero, false);
        if (!$curso) {
            $this->fallo('Curso no encontrado.', 404);
            return;
        }

        $usuario = $_SESSION['usuario'];
        if ($usuario['rol'] !== 'administrador' && $curso['id_instructor'] !== (int) $usuario['id']) {
            $this->fallo('No puedes modificar un curso que no es tuyo.', 403);
            return;
        }

        if ($metodo === 'DELETE') {
            $this->cursos()->darDeBaja($numero);
            $this->exito('Curso dado de baja correctamente', $this->cursos()->obtenerPorId($numero, false));
            return;
        }

        $cuerpo = $this->leerDatosCurso($multipart);
        if ($cuerpo === null) return;

        $parcial = $metodo === 'PATCH';
        if ($parcial && !$multipart && !array_intersect(self::CAMPOS, array_keys($cuerpo))) {
            $this->fallo('Envía al menos un campo a modificar.', 400);
            return;
        }

        $errores = $this->validarCurso($cuerpo, $parcial);
        if ($errores) {
            $this->fallo('Los datos enviados no son válidos.', 400, $errores);
            return;
        }

        $datos = $this->normalizar($cuerpo, !$parcial);
        $portada = $multipart ? $this->guardarPortada() : null;
        if ($multipart && $portada === null) return;
        if ($portada !== null) $datos['imagen'] = $portada;
        try {
            $this->cursos()->actualizar($numero, $datos);
        } catch (Throwable $e) {
            if ($portada !== null) $this->portadas->descartar($portada);
            throw $e;
        }
        $this->exito('Curso actualizado correctamente', $this->cursos()->obtenerPorId($numero, false));
    }

    private function leerDatosCurso(bool $multipart): ?array
    {
        if (!$multipart) return $this->leerJson();

        $datos = $_POST['datos'] ?? null;
        if (!is_string($datos)) {
            $this->fallo('No se recibió el formulario del curso. Revisa el tamaño del banner.', 400);
            return null;
        }
        $token = $_POST['csrf'] ?? null;
        if (empty($_SESSION['csrf']) || !is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            $this->fallo('La sesión del formulario expiró. Recarga la página.', 403);
            return null;
        }
        return $this->leerObjetoJson($datos);
    }

    private function guardarPortada(): ?string
    {
        try {
            return $this->portadas->guardar($_FILES['portada'] ?? []);
        } catch (DomainException $e) {
            $this->fallo('Revisa el banner del curso.', 400, ['portada' => $e->getMessage()]);
            return null;
        }
    }

    private function validarCurso(array $d, bool $parcial): array
    {
        $errores = [];
        $aplica = fn(string $campo) => !$parcial || array_key_exists($campo, $d);

        if ($aplica('titulo') && !$this->textoValido($d['titulo'] ?? null, 150)) {
            $errores['titulo'] = 'Obligatorio: texto de 1 a 150 caracteres.';
        }
        if ($aplica('descripcion') && !$this->textoValido($d['descripcion'] ?? null, 5000)) {
            $errores['descripcion'] = 'Obligatorio: texto de 1 a 5000 caracteres.';
        }

        $nivel = $d['nivel'] ?? null;
        if ($aplica('nivel') && (!is_int($nivel) || $nivel < 1 || $nivel > self::NIVEL_MAX)) {
            $errores['nivel'] = 'Obligatorio: entero entre 1 y ' . self::NIVEL_MAX . '.';
        }

        $precio = $d['precio'] ?? null;
        if ($aplica('precio')
            && ((!is_int($precio) && !is_float($precio)) || $precio < 0 || ($precio > 0 && $precio < 10)
                || $precio > 999999.99 || round($precio, 2) != $precio)) {
            $errores['precio'] = 'Usa 0 para un curso gratis o un precio entre $10.00 y $999,999.99 MXN.';
        }

        $categorias = $d['categorias'] ?? null;
        if ($aplica('categorias')) {
            if (!is_array($categorias) || !$categorias || !array_is_list($categorias)
                || count(array_filter($categorias, fn($c) => is_int($c) && $c > 0)) !== count($categorias)) {
                $errores['categorias'] = 'Obligatorio: arreglo con al menos un identificador entero positivo.';
            } elseif ($this->categorias()->contarExistentes(array_unique($categorias)) !== count(array_unique($categorias))) {
                $errores['categorias'] = 'Una o más categorías no existen.';
            }
        }

        if (array_key_exists('imagen', $d) && $d['imagen'] !== null && (!is_string($d['imagen']) || mb_strlen($d['imagen']) > 255)) {
            $errores['imagen'] = 'Debe ser texto de hasta 255 caracteres.';
        }
        if (array_key_exists('activo', $d) && !is_bool($d['activo'])) {
            $errores['activo'] = 'Debe ser verdadero o falso.';
        }

        return $errores;
    }

    private function normalizar(array $c, bool $conDefectos): array
    {
        $datos = [];

        foreach (['titulo', 'descripcion'] as $campo) {
            if (array_key_exists($campo, $c)) $datos[$campo] = trim($c[$campo]);
        }
        foreach (['nivel', 'precio'] as $campo) {
            if (array_key_exists($campo, $c)) $datos[$campo] = $c[$campo];
        }
        if (array_key_exists('categorias', $c)) {
            $datos['categorias'] = array_values(array_unique($c['categorias']));
        }

        if (array_key_exists('imagen', $c)) {
            $datos['imagen'] = ($c['imagen'] === null || $c['imagen'] === '') ? null : $c['imagen'];
        } elseif ($conDefectos) {
            $datos['imagen'] = null;
        }

        if (array_key_exists('activo', $c)) {
            $datos['activo'] = $c['activo'];
        }

        return $datos;
    }

    private function cursos(): Curso
    {
        return $this->cursos ??= new Curso($this->conexion->obtener());
    }

    private function categorias(): Categoria
    {
        return $this->categorias ??= new Categoria($this->conexion->obtener());
    }

    private function leerConsulta(array $q): array
    {
        $errores = [];
        $filtros = [];

        foreach (['categoria', 'publicador'] as $campo) {
            $valor = $this->entero($q, $campo, 1, self::ID_MAX, $errores);
            if ($valor !== null) $filtros[$campo] = $valor;
        }

        $nivel = $this->entero($q, 'nivel', 1, self::NIVEL_MAX, $errores);
        if ($nivel !== null) $filtros['nivel'] = $nivel;

        if (isset($q['titulo']) && $q['titulo'] !== '') {
            $titulo = is_string($q['titulo']) ? trim($q['titulo']) : '';
            if ($titulo === '' || mb_strlen($titulo) > 150) {
                $errores['titulo'] = 'Debe ser texto de 1 a 150 caracteres.';
            } else {
                $filtros['titulo'] = $titulo;
            }
        }

        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            $valor = $this->fecha($q, $campo, $errores);
            if ($valor !== null) $filtros[$campo] = $valor;
        }
        if (isset($filtros['fecha_inicio'], $filtros['fecha_fin']) && $filtros['fecha_inicio'] > $filtros['fecha_fin']) {
            $errores['fecha_fin'] = 'No puede ser anterior a fecha_inicio.';
        }

        $pagina = $this->entero($q, 'pagina', 1, self::ID_MAX, $errores) ?? 1;
        $porPagina = $this->entero($q, 'por_pagina', 1, self::POR_PAGINA_MAX, $errores) ?? self::POR_PAGINA;

        $orden = 'recientes';
        if (isset($q['orden']) && $q['orden'] !== '') {
            if (is_string($q['orden']) && in_array($q['orden'], self::ORDENES, true)) {
                $orden = $q['orden'];
            } else {
                $errores['orden'] = 'Debe ser ' . implode(', ', self::ORDENES) . '.';
            }
        }

        return [
            'filtros' => $filtros, 'pagina' => $pagina, 'por_pagina' => $porPagina,
            'orden' => $orden, 'errores' => $errores,
        ];
    }

    private function entero(array $q, string $campo, int $min, int $max, array &$errores): ?int
    {
        if (!isset($q[$campo]) || $q[$campo] === '') return null;

        $valor = filter_var($q[$campo], FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($valor === false) {
            $errores[$campo] = $max >= self::ID_MAX
                ? "Debe ser un entero mayor o igual a $min."
                : "Debe ser un entero entre $min y $max.";
            return null;
        }
        return $valor;
    }

    private function fecha(array $q, string $campo, array &$errores): ?string
    {
        if (!isset($q[$campo]) || $q[$campo] === '') return null;

        $valor = $q[$campo];
        $fecha = is_string($valor) ? DateTime::createFromFormat('Y-m-d', $valor) : false;
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            $errores[$campo] = 'Debe ser una fecha válida con formato AAAA-MM-DD.';
            return null;
        }
        return $valor;
    }
}
