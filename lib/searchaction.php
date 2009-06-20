<?php
/**
 * Base search action class.
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
 * Copyright (C) 2008, Controlez-Vous, Inc.
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

require_once INSTALLDIR.'/lib/searchgroupnav.php';

/**
 * Base search action class.
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 */
class SearchAction extends Action
{
    /**
     * Return true if read only.
     *
     * @return boolean true
     */
    function isReadOnly($args)
    {
        return true;
    }

    function handle($args)
    {
        parent::handle($args);
        $this->showPage();
    }

    /**
     * Show tabset for this page
     *
     * Uses the SearchGroupNav widget
     *
     * @return void
     * @see SearchGroupNav
     */

    function showLocalNav()
    {
        $nav = new SearchGroupNav($this, $this->trimmed('q'));
        $nav->show();
    }

    function showTop($arr=null)
    {
        $error = null;
        if ($arr) {
            $error = $arr[1];
        }
        if (!empty($error)) {
            $this->element('p', 'error', $error);
        } else {
            $instr = $this->getInstructions();
            $output = common_markup_to_html($instr);
            $this->elementStart('div', 'instructions');
            $this->raw($output);
            $this->elementEnd('div');
        }
    }

    function title()
    {
        return null;
    }

    function showNoticeForm() {
        // remote post notice form
    }

    function showContent() {
        $this->showTop();
        $this->showForm();
    }

    function showForm($error=null)
    {
        $q = $this->trimmed('q');
        $page = $this->trimmed('page', 1);
        $this->elementStart('form', array('method' => 'get',
                                           'id' => 'form_search',
                                           'class' => 'form_settings',
                                           'action' => common_local_url($this->trimmed('action'))));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('Search site'));
        $this->elementStart('ul', 'form_data');
        $this->elementStart('li');
        if (!common_config('site', 'fancy')) {
            $this->hidden('action', $this->trimmed('action'));
        }
        $this->input('q', 'Keyword(s)', $q);
        $this->submit('search', 'Search');
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
        if ($q) {
            $this->showResults($q, $page);
        }
    }

    function searchSuggestions($q) {
        $qe = urlencode($q);
        $message = sprintf(_(<<<E_O_T
* Make sure all words are spelled correctly.
* Try different keywords.
* Try more general keywords.
* Try fewer keywords.

You can also try your search on other engines:

* [Twingly](http://www.twingly.com/search?q=%s&content=microblog&site=identi.ca)
* [Tweet scan](http://www.tweetscan.com/indexi.php?s=%s)
* [Google](http://www.google.com/search?q=site%%3A%%%%site.server%%%%+%s)
* [Yahoo](http://search.yahoo.com/search?p=site%%3A%%%%site.server%%%%+%s)


E_O_T
), $qe, $qe, $qe, $qe);
        $this->elementStart('dl', array('id' => 'help_search', 'class' => 'help'));
        $this->element('dt', null, _('Search help'));
        $this->elementStart('dd', 'instructions');
        $this->raw(common_markup_to_html($message));
        $this->elementEnd('dd');
        $this->elementEnd('div');
    }
}

