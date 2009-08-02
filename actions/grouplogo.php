<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Upload an avatar
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
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @author    Zach Copley <zach@controlyourself.ca>
 * @copyright 2008-2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/accountsettingsaction.php';

define('MAX_ORIGINAL', 480);

/**
 * Upload an avatar
 *
 * We use jCrop plugin for jQuery to crop the image after upload.
 *
 * @category Settings
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Zach Copley <zach@controlyourself.ca>
 * @author   Sarven Capadisli <csarven@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

class GrouplogoAction extends GroupDesignAction
{
    var $mode = null;
    var $imagefile = null;
    var $filename = null;
    var $msg = null;
    var $success = null;

    /**
     * Prepare to run
     */

    function prepare($args)
    {
        parent::prepare($args);

        if (!common_config('inboxes','enabled')) {
            $this->serverError(_('Inboxes must be enabled for groups to work'));
            return false;
        }

        if (!common_logged_in()) {
            $this->clientError(_('You must be logged in to create a group.'));
            return false;
        }

        $nickname_arg = $this->trimmed('nickname');
        $nickname = common_canonical_nickname($nickname_arg);

        // Permanent redirect on non-canonical nickname

        if ($nickname_arg != $nickname) {
            $args = array('nickname' => $nickname);
            common_redirect(common_local_url('grouplogo', $args), 301);
            return false;
        }

        if (!$nickname) {
            $this->clientError(_('No nickname'), 404);
            return false;
        }

        $groupid = $this->trimmed('groupid');

        if ($groupid) {
            $this->group = User_group::staticGet('id', $groupid);
        } else {
            $this->group = User_group::staticGet('nickname', $nickname);
        }

        if (!$this->group) {
            $this->clientError(_('No such group'), 404);
            return false;
        }

        $cur = common_current_user();

        if (!$cur->isAdmin($this->group)) {
            $this->clientError(_('You must be an admin to edit the group'), 403);
            return false;
        }

        return true;
    }

    function handle($args)
    {
        parent::handle($args);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->handlePost();
        } else {
            $this->showForm();
        }
    }

    function showForm($msg = null, $success = false)
    {
        $this->msg     = $msg;
        $this->success = $success;

        $this->showPage();
    }

    /**
     * Title of the page
     *
     * @return string Title of the page
     */

    function title()
    {
        return _('Group logo');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */

    function getInstructions()
    {
        return sprintf(_('You can upload a logo image for your group. The maximum file size is %s.'), ImageFile::maxFileSize());
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
        if ($this->mode == 'crop') {
            $this->showCropForm();
        } else {
            $this->showUploadForm();
        }
    }

    function showUploadForm()
    {
        $user = common_current_user();

        $profile = $user->getProfile();

        if (!$profile) {
            common_log_db_error($user, 'SELECT', __FILE__);
            $this->serverError(_('User without matching profile'));
            return;
        }

        $original = $this->group->original_logo;

        $this->elementStart('form', array('enctype' => 'multipart/form-data',
                                          'method' => 'post',
                                          'id' => 'form_settings_avatar',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('grouplogo',
                                                           array('nickname' => $this->group->nickname))));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('Group logo'));
        $this->hidden('token', common_session_token());

        $this->elementStart('ul', 'form_data');
        if ($original) {
            $this->elementStart('li', array('id' => 'avatar_original',
                                            'class' => 'avatar_view'));
            $this->element('h2', null, _("Original"));
            $this->elementStart('div', array('id'=>'avatar_original_view'));
            $this->element('img', array('src' => $this->group->original_logo,
                                        'alt' => $this->group->nickname));
            $this->elementEnd('div');
            $this->elementEnd('li');
        }

        if ($this->group->homepage_logo) {
            $this->elementStart('li', array('id' => 'avatar_preview',
                                            'class' => 'avatar_view'));
            $this->element('h2', null, _("Preview"));
            $this->elementStart('div', array('id'=>'avatar_preview_view'));
            $this->element('img', array('src' => $this->group->homepage_logo,
                                        'width' => AVATAR_PROFILE_SIZE,
                                        'height' => AVATAR_PROFILE_SIZE,
                                        'alt' => $this->group->nickname));
            $this->elementEnd('div');
            $this->elementEnd('li');
        }

        $this->elementStart('li', array ('id' => 'settings_attach'));
        $this->element('input', array('name' => 'avatarfile',
                                      'type' => 'file',
                                      'id' => 'avatarfile'));
        $this->element('input', array('name' => 'MAX_FILE_SIZE',
                                      'type' => 'hidden',
                                      'id' => 'MAX_FILE_SIZE',
                                      'value' => ImageFile::maxFileSizeInt()));
        $this->elementEnd('li');
        $this->elementEnd('ul');

        $this->elementStart('ul', 'form_actions');
        $this->elementStart('li');
        $this->submit('upload', _('Upload'));
        $this->elementEnd('li');
        $this->elementEnd('ul');

        $this->elementEnd('fieldset');
        $this->elementEnd('form');

    }

    function showCropForm()
    {
        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_settings_avatar',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('grouplogo',
                                                           array('nickname' => $this->group->nickname))));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('Avatar settings'));
        $this->hidden('token', common_session_token());

        $this->elementStart('ul', 'form_data');

        $this->elementStart('li',
                            array('id' => 'avatar_original',
                                  'class' => 'avatar_view'));
        $this->element('h2', null, _("Original"));
        $this->elementStart('div', array('id'=>'avatar_original_view'));
        $this->element('img', array('src' => Avatar::url($this->filedata['filename']),
                                    'width' => $this->filedata['width'],
                                    'height' => $this->filedata['height'],
                                    'alt' => $this->group->nickname));
        $this->elementEnd('div');
        $this->elementEnd('li');

        $this->elementStart('li',
                            array('id' => 'avatar_preview',
                                  'class' => 'avatar_view'));
        $this->element('h2', null, _("Preview"));
        $this->elementStart('div', array('id'=>'avatar_preview_view'));
        $this->element('img', array('src' => Avatar::url($this->filedata['filename']),
                                    'width' => AVATAR_PROFILE_SIZE,
                                    'height' => AVATAR_PROFILE_SIZE,
                                    'alt' => $this->group->nickname));
        $this->elementEnd('div');

        foreach (array('avatar_crop_x', 'avatar_crop_y',
                       'avatar_crop_w', 'avatar_crop_h') as $crop_info) {
            $this->element('input', array('name' => $crop_info,
                                          'type' => 'hidden',
                                          'id' => $crop_info));
        }

        $this->submit('crop', _('Crop'));

        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->elementEnd('fieldset');
        $this->elementEnd('form');

    }

    /**
     * Handle a post
     *
     * We mux on the button name to figure out what the user actually wanted.
     *
     * @return void
     */

    function handlePost()
    {
        // CSRF protection

        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->show_form(_('There was a problem with your session token. '.
                               'Try again, please.'));
            return;
        }

        if ($this->arg('upload')) {
            $this->uploadLogo();
        } else if ($this->arg('crop')) {
            $this->cropLogo();
        } else {
            $this->showForm(_('Unexpected form submission.'));
        }
    }

    /**
     * Handle an image upload
     *
     * Does all the magic for handling an image upload, and crops the
     * image by default.
     *
     * @return void
     */

    function uploadLogo()
    {
        try {
            $imagefile = ImageFile::fromUpload('avatarfile');
        } catch (Exception $e) {
            $this->showForm($e->getMessage());
            return;
        }

        $filename = Avatar::filename($this->group->id,
                                     image_type_to_extension($imagefile->type),
                                     null,
                                     'group-temp-'.common_timestamp());

        $filepath = Avatar::path($filename);

        move_uploaded_file($imagefile->filepath, $filepath);

        $filedata = array('filename' => $filename,
                          'filepath' => $filepath,
                          'width' => $imagefile->width,
                          'height' => $imagefile->height,
                          'type' => $imagefile->type);

        $_SESSION['FILEDATA'] = $filedata;

        $this->filedata = $filedata;

        $this->mode = 'crop';

        $this->showForm(_('Pick a square area of the image to be the logo.'),
                        true);
    }

    /**
     * Handle the results of jcrop.
     *
     * @return void
     */

    function cropLogo()
    {
        $filedata = $_SESSION['FILEDATA'];

        if (!$filedata) {
            $this->serverError(_('Lost our file data.'));
            return;
        }

        // If image is not being cropped assume pos & dimentions of original
        $dest_x = $this->arg('avatar_crop_x') ? $this->arg('avatar_crop_x'):0;
        $dest_y = $this->arg('avatar_crop_y') ? $this->arg('avatar_crop_y'):0;
        $dest_w = $this->arg('avatar_crop_w') ? $this->arg('avatar_crop_w'):$filedata['width'];
        $dest_h = $this->arg('avatar_crop_h') ? $this->arg('avatar_crop_h'):$filedata['height'];
        $size = min($dest_w, $dest_h);
        $size = ($size > MAX_ORIGINAL) ? MAX_ORIGINAL:$size;

        $imagefile = new ImageFile($this->group->id, $filedata['filepath']);
        $filename = $imagefile->resize($size, $dest_x, $dest_y, $dest_w, $dest_h);

        if ($this->group->setOriginal($filename)) {
            @unlink($filedata['filepath']);
            unset($_SESSION['FILEDATA']);
            $this->mode = 'upload';
            $this->showForm(_('Logo updated.'), true);
        } else {
            $this->showForm(_('Failed updating logo.'));
        }
    }

    function showPageNotice()
    {
        if ($this->msg) {
            $this->element('div', ($this->success) ? 'success' : 'error',
                           $this->msg);
        } else {
            $inst   = $this->getInstructions();
            $output = common_markup_to_html($inst);

            $this->elementStart('div', 'instructions');
            $this->raw($output);
            $this->elementEnd('div');
        }
    }

    /**
     * Add the jCrop stylesheet
     *
     * @return void
     */

    function showStylesheets()
    {
        parent::showStylesheets();
        $jcropStyle =
          common_path('theme/base/css/jquery.Jcrop.css?version='.LACONICA_VERSION);

        $this->element('link', array('rel' => 'stylesheet',
                                     'type' => 'text/css',
                                     'href' => $jcropStyle,
                                     'media' => 'screen, projection, tv'));
    }

    /**
     * Add the jCrop scripts
     *
     * @return void
     */

    function showScripts()
    {
        parent::showScripts();

        if ($this->mode == 'crop') {
            $jcropPack = common_path('js/jcrop/jquery.Jcrop.pack.js');
            $jcropGo   = common_path('js/jcrop/jquery.Jcrop.go.js');

            $this->element('script', array('type' => 'text/javascript',
                                           'src' => $jcropPack));
            $this->element('script', array('type' => 'text/javascript',
                                           'src' => $jcropGo));
        }
    }

    function showLocalNav()
    {
        $nav = new GroupNav($this, $this->group);
        $nav->show();
    }
}
