<?php

namespace JorgeMdDev\KumbiaMigrations;

class ColumnDefinition
{
    protected $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function nullable($value = true)
    {
        $this->attributes['nullable'] = $value;
        return $this;
    }

    public function default($value)
    {
        $this->attributes['default'] = $value;
        return $this;
    }

    public function unsigned()
    {
        $this->attributes['unsigned'] = true;
        return $this;
    }

    public function comment($comment)
    {
        $this->attributes['comment'] = $comment;
        return $this;
    }

    public function autoIncrement()
    {
        $this->attributes['autoIncrement'] = true;
        return $this;
    }

    public function charset($charset)
    {
        $this->attributes['charset'] = $charset;
        return $this;
    }

    public function collation($collation)
    {
        $this->attributes['collation'] = $collation;
        return $this;
    }

    public function useCurrent()
    {
        $this->attributes['useCurrent'] = true;
        return $this;
    }

    public function useCurrentOnUpdate()
    {
        $this->attributes['useCurrentOnUpdate'] = true;
        return $this;
    }

    public function after($column)
    {
        $this->attributes['after'] = $column;
        return $this;
    }

    public function first()
    {
        $this->attributes['first'] = true;
        return $this;
    }

    public function unique()
    {
        $this->attributes['unique'] = true;
        return $this;
    }

    public function primary()
    {
        $this->attributes['primary'] = true;
        return $this;
    }

    public function index()
    {
        $this->attributes['index'] = true;
        return $this;
    }

    public function get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }
}
