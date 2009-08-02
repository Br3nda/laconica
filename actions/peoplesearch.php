<?php
/**
 * People search action class.
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

require_once INSTALLDIR.'/lib/searchaction.php';
require_once INSTALLDIR.'/lib/profilelist.php';

/**
 * People search action class.
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 */
class PeoplesearchAction extends SearchAction
{
    function getInstructions()
    {
        return _('Search for people on %%site.name%% by their name, location, or interests. ' .
                  'Separate the terms by spaces; they must be 3 characters or more.');
    }

    function title()
    {
        return _('People search');
    }

    function showResults($q, $page)
    {
        $profile = new Profile();
        $search_engine = $profile->getSearchEngine('identica_people');
        $search_engine->set_sort_mode('chron');
        // Ask for an extra to see if there's more.
        $search_engine->limit((($page-1)*PROFILES_PER_PAGE), PROFILES_PER_PAGE + 1);
        if (false === $search_engine->query($q)) {
            $cnt = 0;
        }
        else {
            $cnt = $profile->find();
        }
        if ($cnt > 0) {
            $terms = preg_split('/[\s,]+/', $q);
            $results = new PeopleSearchResults($profile, $terms, $this);
            $results->show();
            $profile->free();
            $this->pagination($page > 1, $cnt > PROFILES_PER_PAGE,
                          $page, 'peoplesearch', array('q' => $q));

        } else {
            $this->element('p', 'error', _('No results.'));
            $this->searchSuggestions($q);
            $profile->free();
        }
    }
}

/**
 * People search results class
 *
 * Derivative of ProfileList with specialization for highlighting search terms.
 *
 * @category Widget
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 *
 * @see PeoplesearchAction
 */

class PeopleSearchResults extends ProfileList
{
    var $terms = null;
    var $pattern = null;

    function __construct($profile, $terms, $action)
    {
        parent::__construct($profile, $action);

        $this->terms = array_map('preg_quote',
                                 array_map('htmlspecialchars', $terms));

        $this->pattern = '/('.implode('|',$terms).')/i';
    }

    function newProfileItem($profile)
    {
        return new PeopleSearchResultItem($profile, $this->action);
    }
}

class PeopleSearchResultItem extends ProfileListItem
{
    function highlight($text)
    {
        return preg_replace($this->pattern, '<strong>\\1</strong>', htmlspecialchars($text));
    }
}

