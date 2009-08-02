<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, Control Yourself, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('LACONICA')) {
    exit(1);
}

class ArrayWrapper
{
    var $_items = null;
    var $_count = 0;
    var $_i = -1;

    function __construct($items)
    {
        $this->_items = $items;
        $this->_count = count($this->_items);
    }

    function fetch()
    {
        if (!$this->_items) {
            return false;
        }
        $this->_i++;
        if ($this->_i < $this->_count) {
            return true;
        } else {
            return false;
        }
    }

    function __set($name, $value)
    {
        $item =& $this->_items[$this->_i];
        $item->$name = $value;
        return $item->$name;
    }

    function __get($name)
    {
        $item =& $this->_items[$this->_i];
        return $item->$name;
    }

    function __isset($name)
    {
        $item =& $this->_items[$this->_i];
        return isset($item->$name);
    }

    function __unset($name)
    {
        $item =& $this->_items[$this->_i];
        unset($item->$name);
    }

    function __call($name, $args)
    {
        $item =& $this->_items[$this->_i];
        return call_user_func_array(array($item, $name), $args);
    }
}