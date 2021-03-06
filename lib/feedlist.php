<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Widget for showing a list of feeds
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
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
 *
 * @category  Widget
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @author    Sarven Capadisli <csarven@controlyourself.ca>
 * @copyright 2008 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

/**
 * Widget for showing a list of feeds
 *
 * Typically used for Action::showExportList()
 *
 * @category Widget
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Sarven Capadisli <csarven@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 *
 * @see      Action::showExportList()
 */

class FeedList extends Widget
{
    var $action = null;

    function __construct($action=null)
    {
	parent::__construct($action);
	$this->action = $action;
    }

    function show($feeds)
    {
        $this->out->elementStart('div', array('id' => 'export_data',
                                              'class' => 'section'));
        $this->out->element('h2', null, _('Export data'));
        $this->out->elementStart('ul', array('class' => 'xoxo'));

        foreach ($feeds as $feed) {
            $this->feedItem($feed);
        }

        $this->out->elementEnd('ul');
        $this->out->elementEnd('div');
    }

    function feedItem($feed)
    {
        $classname = null;

        switch ($feed->type) {
         case Feed::RSS1:
         case Feed::RSS2:
            $classname = 'rss';
            break;
         case Feed::ATOM:
            $classname = 'atom';
            break;
         case Feed::FOAF:
            $classname = 'foaf';
            break;
        }

        $this->out->elementStart('li');
        $this->out->element('a', array('href' => $feed->url,
                                       'class' => $classname,
                                       'type' => $feed->mimeType(),
                                       'title' => $feed->title),
                            $feed->typeName());
        $this->out->elementEnd('li');
    }
}
