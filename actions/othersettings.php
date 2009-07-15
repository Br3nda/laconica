<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Miscellaneous settings
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
 * @author    Robin Millette <millette@controlyourself.ca>
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2008-2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/accountsettingsaction.php';

/**
 * Miscellaneous settings actions
 *
 * Currently this just manages URL shortening.
 *
 * @category Settings
 * @package  Laconica
 * @author   Robin Millette <millette@controlyourself.ca>
 * @author   Zach Copley <zach@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

class OthersettingsAction extends AccountSettingsAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */

    function title()
    {
        return _('Other Settings');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */

    function getInstructions()
    {
        return _('Manage various other options.');
    }

    /**
     * Content area of the page
     *
     * Shows a form for uploading an avatar.
     *
     * @return void
     */

    function showContent()
    {
        $user = common_current_user();

        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_settings_other',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('othersettings')));
        $this->elementStart('fieldset');
        $this->hidden('token', common_session_token());

        // I18N

        $services = array(
                          '' => 'None',
                          'ur1.ca' => 'ur1.ca (free service)',
                          '2tu.us' => '2tu.us (free service)',
                          'ptiturl.com' => 'ptiturl.com',
                          'bit.ly' => 'bit.ly',
                          'tinyurl.com' => 'tinyurl.com',
                          'is.gd' => 'is.gd',
                          'snipr.com' => 'snipr.com',
                          'metamark.net' => 'metamark.net'
                          );

        $this->elementStart('ul', 'form_data');
        $this->elementStart('li');
        $this->dropdown('urlshorteningservice', _('Shorten URLs with'),
                        $services, _('Automatic shortening service to use.'),
                        false, $user->urlshorteningservice);
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->checkbox('viewdesigns', _('View profile designs'),
                        $user->viewdesigns, _('Show or hide profile designs.'));
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->submit('save', _('Save'));
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    /**
     * Handle a post
     *
     * Saves the changes to url-shortening prefs and shows a success or failure
     * message.
     *
     * @return void
     */

    function handlePost()
    {
        // CSRF protection
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(_('There was a problem with your session token. '.
                              'Try again, please.'));
            return;
        }

        $urlshorteningservice = $this->trimmed('urlshorteningservice');

        if (!is_null($urlshorteningservice) && strlen($urlshorteningservice) > 50) {
            $this->showForm(_('URL shortening service is too long (max 50 chars).'));
            return;
        }

        $viewdesigns = $this->boolean('viewdesigns');

        $user = common_current_user();

        assert(!is_null($user)); // should already be checked

        $user->query('BEGIN');

        $original = clone($user);

        $user->urlshorteningservice = $urlshorteningservice;
        $user->viewdesigns          = $viewdesigns;

        $result = $user->update($original);

        if ($result === false) {
            common_log_db_error($user, 'UPDATE', __FILE__);
            $this->serverError(_('Couldn\'t update user.'));
            return;
        }

        $user->query('COMMIT');

        $this->showForm(_('Preferences saved.'), true);
    }
}
