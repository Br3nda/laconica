<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Widget to show a list of profiles
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
 * @category  Public
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2008-2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/profilelist.php';

define('PROFILES_PER_MINILIST', 27);

/**
 * Widget to show a list of profiles, good for sidebar
 *
 * @category Public
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

class ProfileMiniList extends ProfileList
{

    function startList()
    {
        $this->out->elementStart('ul', 'entities users xoxo');
    }

    function newListItem($profile)
    {
        return new ProfileMiniListItem($profile, $this->action);
    }

    function showProfiles()
    {
        $cnt = 0;

        while ($this->profile->fetch()) {
            $cnt++;
            if ($cnt > PROFILES_PER_MINILIST) {
                break;
            }
            $pli = $this->newListItem($this->profile);
            $pli->show();
        }

        return $cnt;
    }

}

class ProfileMiniListItem extends ProfileListItem
{
    function show()
    {
        $this->out->elementStart('li', 'vcard');
        $this->out->elementStart('a', array('title' => $this->profile->getBestName(),
                                       'href' => $this->profile->profileurl,
                                       'rel' => 'contact member',
                                       'class' => 'url'));
        $avatar = $this->profile->getAvatar(AVATAR_MINI_SIZE);
        $this->out->element('img', array('src' => (($avatar) ? $avatar->displayUrl() :  Avatar::defaultImage(AVATAR_MINI_SIZE)),
                                    'width' => AVATAR_MINI_SIZE,
                                    'height' => AVATAR_MINI_SIZE,
                                    'class' => 'avatar photo',
                                    'alt' =>  ($this->profile->fullname) ?
                                    $this->profile->fullname :
                                    $this->profile->nickname));
        $this->out->element('span', 'fn nickname', $this->profile->nickname);
        $this->out->elementEnd('a');
        $this->out->elementEnd('li');
    }
}
