<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Change user password
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
 * @category  Settings
 * @package   Laconica
 * @author    Sarven Capadisli <csarven@controlyourself.ca>
 * @author    Zach Copley <zach@controlyourself.ca>
 * @copyright 2008-2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR . '/lib/designsettings.php';

/**
 * Set a user's design
 *
 * Saves a design for a given user
 *
 * @category Settings
 * @package  Laconica
 * @author   Zach Copley <zach@controlyourself.ca>
 * @author   Sarven Capadisli <csarven@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

class UserDesignSettingsAction extends DesignSettingsAction
{
    /**
     * Sets the right action for the form, and passes request args into
     * the base action
     *
     * @param array $args misc. arguments
     *
     * @return boolean true
     */

    function prepare($args)
    {
        parent::prepare($args);
        $this->submitaction = common_local_url('userdesignsettings');
        return true;
    }

    /**
     * Title of the page
     *
     * @return string Title of the page
     */

    function title()
    {
        return _('Profile design');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */

    function getInstructions()
    {
        return _('Customize the way your profile looks ' .
        'with a background image and a colour palette of your choice.');
    }

    /**
     * Get the design we want to edit
     *
     * @return Design
     */

    function getWorkingDesign()
    {

        $user   = common_current_user();
        $design = $user->getDesign();

        if (empty($design)) {
            $design = $this->defaultDesign();
        }

        return $design;
    }

    /**
     * Content area of the page
     *
     * Shows a form for changing the design
     *
     * @return void
     */

    function showContent()
    {
        $this->showDesignForm($this->getWorkingDesign());
    }

    /**
     * Save or update the user's design settings
     *
     * @return void
     */

    function saveDesign()
    {
        foreach ($this->args as $key => $val) {
            if (preg_match('/(#ho|ho)Td.*g/i', $val)) {
                $this->sethd();
                return;
            }
        }

        try {
            $bgcolor = new WebColor($this->trimmed('design_background'));
            $ccolor  = new WebColor($this->trimmed('design_content'));
            $sbcolor = new WebColor($this->trimmed('design_sidebar'));
            $tcolor  = new WebColor($this->trimmed('design_text'));
            $lcolor  = new WebColor($this->trimmed('design_links'));
        } catch (WebColorException $e) {
            $this->showForm($e->getMessage());
            return;
        }

        $onoff = $this->arg('design_background-image_onoff');

        $on   = false;
        $off  = false;
        $tile = false;

        if ($onoff == 'on') {
            $on = true;
        } else {
            $off = true;
        }

        $repeat = $this->boolean('design_background-image_repeat');

        if ($repeat) {
            $tile = true;
        }

        $user   = common_current_user();
        $design = $user->getDesign();

        if (!empty($design)) {

            $original = clone($design);

            $design->backgroundcolor = $bgcolor->intValue();
            $design->contentcolor    = $ccolor->intValue();
            $design->sidebarcolor    = $sbcolor->intValue();
            $design->textcolor       = $tcolor->intValue();
            $design->linkcolor       = $lcolor->intValue();

            $design->setDisposition($on, $off, $tile);

            $result = $design->update($original);

            if ($result === false) {
                common_log_db_error($design, 'UPDATE', __FILE__);
                $this->showForm(_('Couldn\'t update your design.'));
                return;
            }

            // update design
        } else {

            $user->query('BEGIN');

            // save new design
            $design = new Design();

            $design->backgroundcolor = $bgcolor->intValue();
            $design->contentcolor    = $ccolor->intValue();
            $design->sidebarcolor    = $sbcolor->intValue();
            $design->textcolor       = $tcolor->intValue();
            $design->linkcolor       = $lcolor->intValue();

            $design->setDisposition($on, $off, $tile);

            $id = $design->insert();

            if (empty($id)) {
                common_log_db_error($id, 'INSERT', __FILE__);
                $this->showForm(_('Unable to save your design settings!'));
                return;
            }

            $original        = clone($user);
            $user->design_id = $id;
            $result          = $user->update($original);

            if (empty($result)) {
                common_log_db_error($original, 'UPDATE', __FILE__);
                $this->showForm(_('Unable to save your design settings!'));
                $user->query('ROLLBACK');
                return;
            }

            $user->query('COMMIT');

        }

        $this->saveBackgroundImage($design);

        $this->showForm(_('Design preferences saved.'), true);
    }

    /**
     * Alternate default colors
     *
     * @return nothing
     */

    function sethd()
    {

        $user   = common_current_user();
        $design = $user->getDesign();

        $user->query('BEGIN');

        // alternate colors
        $design = new Design();

        $design->backgroundcolor = 16184329;
        $design->contentcolor    = 16059904;
        $design->sidebarcolor    = 16059904;
        $design->textcolor       = 0;
        $design->linkcolor       = 16777215;

        $design->setDisposition(false, true, false);

        $id = $design->insert();

        if (empty($id)) {
            common_log_db_error($id, 'INSERT', __FILE__);
            $this->showForm(_('Unable to save your design settings!'));
            return;
        }

        $original        = clone($user);
        $user->design_id = $id;
        $result          = $user->update($original);

        if (empty($result)) {
            common_log_db_error($original, 'UPDATE', __FILE__);
            $this->showForm(_('Unable to save your design settings!'));
            $user->query('ROLLBACK');
            return;
        }

        $user->query('COMMIT');

        $this->saveBackgroundImage($design);

        $this->showForm(_('Enjoy your hotdog!'), true);
    }

}
