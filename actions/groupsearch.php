<?php
/**
 * Group search action class.
 *
 * PHP version 5
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 *
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

//require_once INSTALLDIR.'/lib/searchaction.php';
//require_once INSTALLDIR.'/lib/profilelist.php';

/**
 * Group search action class.
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 */
class GroupsearchAction extends SearchAction
{
    function getInstructions()
    {
        return _('Search for groups on %%site.name%% by their name, location, or description. ' .
                  'Separate the terms by spaces; they must be 3 characters or more.');
    }

    function title()
    {
        return _('Group search');
    }

    function showResults($q, $page)
    {
        $user_group = new User_group;
        $user_group->limit((($page-1)*GROUPS_PER_PAGE), GROUPS_PER_PAGE + 1);
        $wheres = array('nickname', 'fullname', 'homepage', 'description', 'location');
        foreach ($wheres as $where) {
            $where_q = "$where like '%" . trim($user_group->escape($q), '\'') . '%\'';
            $user_group->whereAdd($where_q, 'OR');
        }
        $cnt = $user_group->find();
        if ($cnt > 0) {
            $terms = preg_split('/[\s,]+/', $q);
            $results = new GroupSearchResults($user_group, $terms, $this);
            $results->show();
            $user_group->free();
            $this->pagination($page > 1, $cnt > GROUPS_PER_PAGE,
                          $page, 'groupsearch', array('q' => $q));
        } else {
            $this->element('p', 'error', _('No results.'));
            $this->searchSuggestions($q);
            if (common_logged_in()) {
                $message = _('If you can\'t find the group you\'re looking for, you can [create it](%%action.newgroup%%) yourself.');
            }
            else {
                $message = _('Why not [register an account](%%action.register%%) and [create the group](%%action.newgroup%%) yourself!');
            }
            $this->elementStart('div', 'guide');
            $this->raw(common_markup_to_html($message));
            $this->elementEnd('div');
            $user_group->free();
        }
    }
}

class GroupSearchResults extends GroupList
{
    var $terms = null;
    var $pattern = null;

    function __construct($user_group, $terms, $action)
    {
        parent::__construct($user_group, $terms, $action);
        $this->terms = array_map('preg_quote',
                                 array_map('htmlspecialchars', $terms));
        $this->pattern = '/('.implode('|',$terms).')/i';
    }

    function highlight($text)
    {
        return preg_replace($this->pattern, '<strong>\\1</strong>', htmlspecialchars($text));
    }
}

