<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * action handler for message inbox
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
 * @category  Message
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2008 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/mailbox.php';

/**
 * action handler for message inbox
 *
 * @category Message
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 * @see      MailboxAction
 */

class InboxAction extends MailboxAction
{

    /**
     * Title of the page
     *
     * @return string page title
     */

    function title()
    {
        if ($this->page > 1) {
            return sprintf(_("Inbox for %s - page %d"), $this->user->nickname,
                $this->page);
        } else {
            return sprintf(_("Inbox for %s"), $this->user->nickname);
        }
    }

    /**
     * Retrieve the messages for this user and this page
     *
     * Does a query for the right messages
     *
     * @return Message data object with stream for messages
     *
     * @see MailboxAction::getMessages()
     */

    function getMessages()
    {
        $message = new Message();

        $message->to_profile = $this->user->id;
        $message->orderBy('created DESC, id DESC');
        $message->limit((($this->page - 1) * MESSAGES_PER_PAGE),
            MESSAGES_PER_PAGE + 1);

        if ($message->find()) {
            return $message;
        } else {
            return null;
        }
    }

    /**
     * Returns the profile we want to show with the message
     *
     * For inboxes, we show the sender; for outboxes, the recipient.
     *
     * @param Message $message The message to get the profile for
     *
     * @return Profile The profile that matches the message
     */

    function getMessageProfile($message)
    {
        return $message->getFrom();
    }

    /**
     * Instructions for using this page
     *
     * @return string localised instructions for using the page
     */

    function getInstructions()
    {
        return _('This is your inbox, which lists your incoming private messages.');
    }
}
