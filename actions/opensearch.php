<?php

/**
 * Opensearch action class.
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

/**
 * Opensearch action class.
 *
 * Formatting of RSS handled by Rss10Action
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 */
class OpensearchAction extends Action
{
    /**
     * Class handler.
     *
     * @param array $args query arguments
     * 
     * @return boolean false if user doesn't exist
     */
    function handle($args)
    {
        parent::handle($args);
        $type       = $this->trimmed('type');
        $short_name = '';
        if ($type == 'people') {
            $type       = 'peoplesearch';
            $short_name = _('People Search');
        } else {
            $type       = 'noticesearch';
            $short_name = _('Notice Search');
        }
        header('Content-Type: text/html');
        $this->startXML();
        $this->elementStart('OpenSearchDescription', array('xmlns' => 'http://a9.com/-/spec/opensearch/1.1/'));
        $short_name =  common_config('site', 'name').' '.$short_name;
        $this->element('ShortName', null, $short_name);
        $this->element('Contact', null, common_config('site', 'email'));
        $this->element('Url', array('type' => 'text/html', 'method' => 'get',
                       'template' => str_replace('---', '{searchTerms}', common_local_url($type, array('q' => '---')))));
        $this->element('Image', array('height' => 16, 'width' => 16, 'type' => 'image/vnd.microsoft.icon'), common_path('favicon.ico'));
        $this->element('Image', array('height' => 50, 'width' => 50, 'type' => 'image/png'), theme_path('logo.png'));
        $this->element('AdultContent', null, 'false');
        $this->element('Language', null, common_language());
        $this->element('OutputEncoding', null, 'UTF-8');
        $this->element('InputEncoding', null, 'UTF-8');
        $this->elementEnd('OpenSearchDescription');
        $this->endXML();
    }

    function isReadOnly($args)
    {
        return true;
    }
}

