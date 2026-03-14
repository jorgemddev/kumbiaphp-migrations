<?php
/**
 * KumbiaPHP Migration System
 *
 * Definición fluida de columnas
 *
 * @category Kumbia
 * @package Migration
 */

class ColumnDefinition
{
    /**
     * Atributos de la columna
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Constructor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Marcar la columna como nullable
     *
     * @param bool $value
     * @return $this
     */
    public function nullable($value = true)
    {
        $this->attributes['nullable'] = $value;
        return $this;
    }

    /**
     * Definir valor por defecto
     *
     * @param mixed $value
     * @return $this
     */
    public function default($value)
    {
        $this->attributes['default'] = $value;
        return $this;
    }

    /**
     * Marcar la columna como unsigned
     *
     * @return $this
     */
    public function unsigned()
    {
        $this->attributes['unsigned'] = true;
        return $this;
    }

    /**
     * Agregar comentario a la columna
     *
     * @param string $comment
     * @return $this
     */
    public function comment($comment)
    {
        $this->attributes['comment'] = $comment;
        return $this;
    }

    /**
     * Marcar como auto-increment
     *
     * @return $this
     */
    public function autoIncrement()
    {
        $this->attributes['autoIncrement'] = true;
        return $this;
    }

    /**
     * Definir charset (MySQL)
     *
     * @param string $charset
     * @return $this
     */
    public function charset($charset)
    {
        $this->attributes['charset'] = $charset;
        return $this;
    }

    /**
     * Definir collation (MySQL)
     *
     * @param string $collation
     * @return $this
     */
    public function collation($collation)
    {
        $this->attributes['collation'] = $collation;
        return $this;
    }

    /**
     * Marcar para usar el timestamp actual por defecto
     *
     * @return $this
     */
    public function useCurrent()
    {
        $this->attributes['useCurrent'] = true;
        return $this;
    }

    /**
     * Marcar para actualizar al timestamp actual en cada update
     *
     * @return $this
     */
    public function useCurrentOnUpdate()
    {
        $this->attributes['useCurrentOnUpdate'] = true;
        return $this;
    }

    /**
     * Colocar la columna después de otra
     *
     * @param string $column
     * @return $this
     */
    public function after($column)
    {
        $this->attributes['after'] = $column;
        return $this;
    }

    /**
     * Colocar la columna al inicio
     *
     * @return $this
     */
    public function first()
    {
        $this->attributes['first'] = true;
        return $this;
    }

    /**
     * Marcar columna como único
     *
     * @return $this
     */
    public function unique()
    {
        $this->attributes['unique'] = true;
        return $this;
    }

    /**
     * Marcar columna como primary key
     *
     * @return $this
     */
    public function primary()
    {
        $this->attributes['primary'] = true;
        return $this;
    }

    /**
     * Agregar índice a la columna
     *
     * @return $this
     */
    public function index()
    {
        $this->attributes['index'] = true;
        return $this;
    }

    /**
     * Obtener un atributo
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Obtener todos los atributos
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Establecer un atributo
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }
}
