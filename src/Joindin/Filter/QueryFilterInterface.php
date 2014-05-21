<?php

namespace Joindin\Filter;

interface QueryFilterInterface
{
    /**
     * Get a join-Statement to be included in an SQL-Statement
     *
     * @return string
     */
    public function getJoin();

    /**
     * Get a where-Statement to be included in an SQL-Statement
     *
     * @return string
     */
    public function getWhere();

} 