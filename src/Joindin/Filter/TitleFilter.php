<?php

namespace Joindin\Filter;

class TitleFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    public function getWhere()
    {
        $where = array();
        foreach ($this->filter as $filter) {
            $where[] = 'LOWER(events.event_name) like "%' . strtolower($filter) . '%"';;
        }

        if (! $where) {
            return '';
        }

        return '(' . implode(') ' . $this->type . ' (', $where) . ')';
    }
}