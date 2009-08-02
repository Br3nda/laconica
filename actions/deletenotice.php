<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Class for deleting a notice
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
 * @category  Personal
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

require_once INSTALLDIR.'/lib/deleteaction.php';

class DeletenoticeAction extends DeleteAction
{
    var $error = null;

    function handle($args)
    {
        parent::handle($args);
        // XXX: Ajax!

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->deleteNotice();
        } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $this->showForm();
        }
    }

    /**
     * Show the page notice
     *
     * Shows instructions for the page
     *
     * @return void
     */

    function showPageNotice()
    {
        $instr  = $this->getInstructions();
        $output = common_markup_to_html($instr);

        $this->elementStart('div', 'instructions');
        $this->raw($output);
        $this->elementEnd('div');
    }

    function getInstructions()
    {
        return _('You are about to permanently delete a notice. ' .
                 'Once this is done, it cannot be undone.');
    }

    function title()
    {
        return _('Delete notice');
    }

    /**
     * Wrapper for showing a page
     *
     * Stores an error and shows the page
     *
     * @param string $error Error, if any
     *
     * @return void
     */

    function showForm($error = null)
    {
        $this->error = $error;
        $this->showPage();
    }

    /**
     * Insert delete notice form into the content
     *
     * @return void
     */

    function showContent()
    {
        $this->elementStart('form', array('id' => 'form_notice_delete',
                                          'class' => 'form_settings',
                                          'method' => 'post',
                                          'action' => common_local_url('deletenotice')));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('Delete notice'));
        $this->hidden('token', common_session_token());
        $this->hidden('notice', $this->trimmed('notice'));
        $this->element('p', null, _('Are you sure you want to delete this notice?'));
        $this->submit('form_action-no', _('No'), 'submit form_action-primary', 'no', _("Do not delete this notice"));
        $this->submit('form_action-yes', _('Yes'), 'submit form_action-secondary', 'yes', _('Delete this notice'));
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    function deleteNotice()
    {
        // CSRF protection
        $token = $this->trimmed('token');

        if (!$token || $token != common_session_token()) {
            $this->showForm(_('There was a problem with your session token. ' .
                              ' Try again, please.'));
            return;
        }

        if ($this->arg('yes')) {
            $this->notice->delete();
        }

        $url = common_get_returnto();

        if ($url) {
            common_set_returnto(null);
        } else {
            $url = common_local_url('public');
        }

        common_redirect($url, 303);
    }
}
