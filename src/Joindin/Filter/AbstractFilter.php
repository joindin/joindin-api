<?php

namespace Joindin\Filter;

abstract class AbstractFilter implements QueryFilterInterface
{
    protected $filter = array();

    protected $type = 'OR';

    public function __construct($filters, $type = 'OR')
    {
        $this->type = $type;

        $filters = (array) $filters;

        foreach ($filters as $filter) {
            $filter = filter_var($filter, FILTER_SANITIZE_STRING);
            if (! $filter) {
                continue;
            }

            $this->filter[] = $filter;
        }
    }

    public function getWhere()
    {
        return '';
    }

    public function getJoin()
    {
        return '';
    }

    public function getHaving()
    {
        return '';
    }

    public function getSelect()
    {
        return '';
    }
} 