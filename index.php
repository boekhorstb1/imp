<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package IMP
 */

@define('IMP_BASE', dirname(__FILE__));
$imp_configured = (is_readable(IMP_BASE . '/config/conf.php') &&
                   is_readable(IMP_BASE . '/config/mime_drivers.php') &&
                   is_readable(IMP_BASE . '/config/prefs.php') &&
                   is_readable(IMP_BASE . '/config/servers.php'));

if (!$imp_configured) {
    if (!defined('HORDE_BASE')) {
        /* Temporary fix - if horde does not live directly under the imp
         * directory, the HORDE_BASE constant should be defined in
         * imp/lib/base.local.php. */
        if (file_exists(IMP_BASE . '/lib/base.local.php')) {
            include IMP_BASE . '/lib/base.local.php';
        } else {
            define('HORDE_BASE', IMP_BASE . '/..');
        }
    }

    require HORDE_BASE . '/lib/Test.php';
    Horde_Test::configFilesMissing('IMP', IMP_BASE,
        array('conf.php', 'mime_drivers.php', 'prefs.php'),
        array('servers.php' => 'This file controls the default settings for IMP, and also defines the list of available servers if you are using the server list.'));
}

require IMP_BASE . '/redirect.php';
