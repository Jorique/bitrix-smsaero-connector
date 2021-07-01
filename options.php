<?php

/**
 * @var string $REQUEST_METHOD
 * @var string $Update
 * @global CMain $APPLICATION
 */

defined('B_PROLOG_INCLUDED') or die;

$module_id = 'jorique.smsaero_connector';

$RIGHT = $APPLICATION->GetGroupRight($module_id);
$RIGHT_W = ($RIGHT >= 'W');
$RIGHT_R = ($RIGHT >= 'R');

if($RIGHT_R) {
    if($REQUEST_METHOD == 'POST' && strlen($Update)>0 && $RIGHT_W && check_bitrix_sessid()) {
        # сохраняем
        COption::SetOptionString('messageservice', 'sender.sms.smsaero', serialize([
            'login' => $_POST['login'],
            'api_key' => $_POST['api_key']
        ]));
        $ex = $APPLICATION->GetException();
        if($ex) {
            CAdminMessage::ShowOldStyleError($ex->GetString());
        }
    }

    $currentOptions = COption::GetOptionString('messageservice', 'sender.sms.smsaero');
    $currentOptions = @unserialize($currentOptions);
    if (!is_array($currentOptions)) {
        $currentOptions = [];
    }
    

    $aTabs = [
        ['DIV' => 'edit1', 'TAB' => 'Настройки', 'ICON' => '', 'TITLE' => 'Настройки'],
        ['DIV' => 'edit3', 'TAB' => 'Доступ', 'ICON' => '', 'TITLE' => 'Доступ'],
    ];

    $tabControl = new CAdminTabControl('tabControl', $aTabs);
    $tabControl->Begin();
    ?>

    <form method="post" action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialchars($mid) ?>&lang=<?= LANGUAGE_ID ?>">
        <?= bitrix_sessid_post() ?>
        <? $tabControl->BeginNextTab(); ?>

        <tr class="heading">
            <td colspan="2"><b>Настройки SMSAero</b></td>
        </tr>
        <tr>
            <td><label for="mo_kasper_pin">Логин:</label></td>
            <td><input type="text" name="login" id="mo_kasper_pin" value="<?= $currentOptions['login'] ?>"/></td>
        </tr>
        <tr>
            <td><label for="mo_kasper_pin">API ключ:</label></td>
            <td><input type="text" name="api_key" id="mo_kasper_pin" value="<?= $currentOptions['api_key'] ?>"/></td>
        </tr>

        <? $tabControl->BeginNextTab(); ?>
        <? require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin/group_rights.php'); ?>
        <? $tabControl->Buttons(); ?>
        <input <? if(!$RIGHT_W) echo 'disabled' ?> type='submit' name='Update' value='<?= GetMessage('MAIN_SAVE') ?>'
                                                   title='<?= GetMessage('MAIN_OPT_SAVE_TITLE') ?>'>
        <? $tabControl->End(); ?>
    </form>
    <?php
}
