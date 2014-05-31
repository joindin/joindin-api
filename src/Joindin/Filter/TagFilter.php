<?php

namespace Joindin\Filter;

class TagFilter extends AbstractFilter
{
    protected $counters = array();
    /**
     * {@inheritDoc}
     */
    public function getJoin()
    {
        $join = array();
        $i = 'a';
        foreach ($this->filter as $filter) {
            $join[] = sprintf(
                'LEFT JOIN tags_events AS %1$s ON %1$s.event_id = events.ID LEFT JOIN tags as %2$s ON %2$s.ID = %1$s.tag_id',
                $i++,
                $i
            );
            $this->counters[] = $i++;
        }

        return implode(' ', $join);
    }

    /**
     * {@inheritDoc}
     */
    public function getWhere()
    {
        $where = array();

        foreach($this->filter as $key => $filter) {
            $where[] = sprintf(
                '%1$s.tag_value="%2$s"',
                $this->counters[$key],
                $filter
            );
        }

        return '(' . implode(') ' . $this->type . ' (', $where) . ')';
    }
}


