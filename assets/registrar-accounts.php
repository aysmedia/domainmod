<?php
/**
 * /assets/registrar-accounts.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2016 Greg Chetcuti <greg@chetcuti.com>
 *
 * Project: http://domainmod.org   Author: http://chetcuti.com
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
?>
<?php //@formatter:off
include("../_includes/start-session.inc.php");
include("../_includes/init.inc.php");

require_once(DIR_ROOT . "classes/Autoloader.php");
spl_autoload_register('DomainMOD\Autoloader::classAutoloader');

$system = new DomainMOD\System();
$error = new DomainMOD\Error();
$layout = new DomainMOD\Layout();
$time = new DomainMOD\Time();

include(DIR_INC . "head.inc.php");
include(DIR_INC . "config.inc.php");
include(DIR_INC . "software.inc.php");
include(DIR_INC . "settings/assets-registrar-accounts.inc.php");
include(DIR_INC . "database.inc.php");

$system->authCheck();

$rid = $_GET['rid'];
$raid = $_GET['raid'];
$oid = $_GET['oid'];
$export_data = $_GET['export_data'];

if ($rid != '') { $rid_string = " AND ra.registrar_id = '$rid' "; } else { $rid_string = ''; }
if ($raid != '') { $raid_string = " AND ra.id = '$raid' "; } else { $raid_string = ''; }
if ($oid != '') { $oid_string = " AND ra.owner_id = '$oid' "; } else { $oid_string = ''; }

$sql = "SELECT ra.id AS raid, ra.username, ra.password, ra.api_key, ra.owner_id, ra.registrar_id, ra.reseller,
            o.id AS oid, o.name AS oname, r.id AS rid, r.name AS rname, ra.notes, ra.insert_time, ra.update_time
        FROM registrar_accounts AS ra, owners AS o, registrars AS r
        WHERE ra.owner_id = o.id
          AND ra.registrar_id = r.id
          $rid_string
          $raid_string
          $oid_string
        GROUP BY ra.username, oname, rname
        ORDER BY rname, username, oname";

if ($export_data == '1') {

    $result = mysqli_query($connection, $sql) or $error->outputOldSqlError($connection);

    $export = new DomainMOD\Export();
    $export_file = $export->openFile('registrar_account_list', strtotime($time->stamp()));

    $row_contents = array($page_title);
    $export->writeRow($export_file, $row_contents);

    $export->writeBlankRow($export_file);

    $row_contents = array(
        'Status',
        'Registrar',
        'Username',
        'Password',
        'API Key',
        'Owner',
        'Domains',
        'Default Account?',
        'Reseller Account?',
        'Notes',
        'Inserted',
        'Updated'
    );
    $export->writeRow($export_file, $row_contents);

    if (mysqli_num_rows($result) > 0) {

        while ($row = mysqli_fetch_object($result)) {

            $sql_domain_count = "SELECT count(*) AS total_domain_count
                                 FROM domains
                                 WHERE account_id = '" . $row->raid . "'
                                   AND active NOT IN ('0', '10')";
            $result_domain_count = mysqli_query($connection, $sql_domain_count);

            while ($row_domain_count = mysqli_fetch_object($result_domain_count)) {
                $total_domains = $row_domain_count->total_domain_count;
            }

            if ($row->raid == $_SESSION['s_default_registrar_account']) {

                $is_default = '1';

            } else {

                $is_default = '0';

            }

            if ($row->reseller == '0') {

                $is_reseller = '0';

            } else {

                $is_reseller = '1';

            }

            if ($total_domains >= 1) {

                $status = 'Active';

            } else {

                $status = 'Inactive';

            }

            $row_contents = array(
                $status,
                $row->rname,
                $row->username,
                $row->password,
                $row->api_key,
                $row->oname,
                $total_domain_count,
                $is_default,
                $is_reseller,
                $row->notes,
                $time->toUserTimezone($row->insert_time),
                $time->toUserTimezone($row->update_time)
            );
            $export->writeRow($export_file, $row_contents);

            $current_raid = $row->raid;

        }

    }

    $export->closeFile($export_file);

}
?>
<?php include(DIR_INC . 'doctype.inc.php'); ?>
<html>
<head>
    <title><?php echo $system->pageTitle($software_title, $page_title); ?></title>
    <?php include(DIR_INC . "layout/head-tags.inc.php"); ?>
</head>
<body class="hold-transition skin-red sidebar-mini">
<?php include(DIR_INC . "layout/header.inc.php"); ?>
Below is a list of all the Domain Registrar Accounts that are stored in <?php echo $software_title; ?>.<BR><BR>
<a href="add/registrar-account.php"><?php echo $layout->showButton('button', 'Add Registrar Account'); ?></a>&nbsp;&nbsp;&nbsp;
<a href="registrar-accounts.php?export_data=1&rid=<?php echo $rid; ?>&raid=<?php echo $raid; ?>&oid=<?php echo $oid; ?>"><?php echo $layout->showButton('button', 'Export'); ?></a><BR><BR><?php

$result = mysqli_query($connection, $sql) or $error->outputOldSqlError($connection);

if (mysqli_num_rows($result) > 0) { ?>

    <table id="<?php echo $slug; ?>" class="<?php echo $datatable_class; ?>">
        <thead>
        <tr>
            <th width="20px"></th>
            <th>Registrar</th>
            <th>Account</th>
            <th>Owner</th>
            <th>Domains</th>
        </tr>
        </thead>

        <tbody><?php

        while ($row = mysqli_fetch_object($result)) {

            $sql_domain_count = "SELECT count(*) AS total_domain_count
                                 FROM domains
                                 WHERE account_id = '" . $row->raid . "'
                                   AND active NOT IN ('0', '10')";
            $result_domain_count = mysqli_query($connection, $sql_domain_count);

            while ($row_domain_count = mysqli_fetch_object($result_domain_count)) {
                $total_domains = $row_domain_count->total_domain_count;
            }

            if ($total_domains >= 1 || $_SESSION['s_display_inactive_assets'] == '1') { ?>

                <tr>
                <td></td>
                <td>
                    <a href="edit/registrar.php?rid=<?php echo $row->rid; ?>"><?php echo $row->rname; ?></a>
                </td>
                <td>
                    <a href="edit/registrar-account.php?raid=<?php echo $row->raid; ?>"><?php echo $row->username; ?></a><?php
                    if ($_SESSION['s_default_registrar_account'] == $row->raid) echo '<strong>*</strong>'; ?><?php
                    if ($row->reseller == '1') echo '<strong>^</strong>'; ?>
                </td>
                <td>
                    <a href="edit/account-owner.php?oid=<?php echo $row->oid; ?>"><?php echo $row->oname; ?></a>
                </td>
                <td><?php

                    if ($total_domains >= 1) { ?>

                        <a href="../domains/index.php?oid=<?php echo $row->oid; ?>&rid=<?php echo $row->rid; ?>&raid=<?php echo $row->raid; ?>"><?php echo $total_domains; ?></a><?php

                    } else {

                        echo '-';

                    } ?>

                </td>
                </tr><?php

            }

        } ?>

        </tbody>
    </table>

    <strong>*</strong> = Default (<a href="../settings/defaults/">set defaults</a>)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>^</strong> = Reseller<BR><BR><?php

} else {

    $sql = "SELECT id
            FROM registrars
            LIMIT 1";
    $result = mysqli_query($connection, $sql);

    if (mysqli_num_rows($result) == 0) { ?>

        <BR>Before adding a Registrar Account you must add at least one Registrar. <a href="add/registrar.php">Click here to add a Registrar</a>.<BR><?php

    } else { ?>

        <BR>You don't currently have any Registrar Accounts. <a href="add/registrar-account.php">Click here to add one</a>.<BR><?php

    }

}
?>
<?php include(DIR_INC . "layout/asset-footer.inc.php"); ?>
<?php include(DIR_INC . "layout/footer.inc.php"); //@formatter:on ?>
</body>
</html>
