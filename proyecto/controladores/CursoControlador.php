<?php
class CursoControlador extends Controlador
{
    public function __construct(private Curso $cursos) {}

    public function inicio(): void
    {
        $this->vista('publico/inicio');
    }

    public function detalle(): void       { $this->vista('publico/curso'); }
    public function crear(): void         { $this->vista('instructor/crear_curso'); }
    public function diploma(): void       { $this->vista('estudiante/diploma'); }
}