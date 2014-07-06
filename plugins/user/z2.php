<?php
defined('_JEXEC') or die ;

//20140624 zoearth 修改 使用附加欄位
class plgUserZ2 extends JPlugin
{
    public function onContentPrepareData($context, $data)
    {
        if (!in_array($context, array('com_users.profile','com_users.user','com_users.registration','com_admin.profile')))
        {
            return true;
        }
    
        //20140625 zoearth 這邊必須先將POST資料暫存
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            $session = JFactory::getSession();
            $session->set('plgUserZ2PostData',$_POST);
        }
        return true;
    }
    
    public function onContentPrepareForm($form, $data)
    {
        //20140624 zoearth 判斷是否為 JForm
        if (!($form instanceof JForm))
        {
            return true;
        }
    
        //20140624 zoearth 只在某些表單才執行
        $name = $form->getName();
        if (!in_array($name, array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
        {
            return true;
        }
        
        //20140624 zoearth 決定使用Z2原本的附加欄位方式去執行
        require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'SetupValidate.php');
        Z2HelperSetupValidate::setupValidate();
        
        //20140625 zoearth 把附加欄位放進變數
        $form->z2ExtendField = array();
        //取得後台Model
        Z2Model::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_z2'.DS.'models');
        $extraFieldModel = Z2Model::getInstance('extraField','Z2Model');
        //取得目前預設群組ID
        $userGroupsModel = Z2Model::getInstance('userGroups','Z2Model');
        $defaultNewUsertype = (int)$userGroupsModel->getDefaultNewUsertype();
        if (!($defaultNewUsertype >= 26 ))
        {
            return true;
        }
        //取得目前預設群組的附加欄位ID
        $userGroupModel = Z2Model::getInstance('userGroup','Z2Model');
        $groupData = $userGroupModel->getData($defaultNewUsertype);
        if (!($groupData['extraFieldsGroup'] > 0 ))
        {
            return true;
        }
        //取得目前預設群組的附加欄位
        $extraFields = $extraFieldModel->getExtraFieldsByGroup((int)$groupData['extraFieldsGroup']);
        if (!(is_array($extraFields) && count($extraFields) > 0 ))
        {
            return true;
        }
        
        $outputArray = array();
        $c = 0;
        
        if (isset($data->id) && $data->id > 0 )
        {
            //20140626 zoearth $data 是 JUser 當ID大於0則為登入中 帶出原本使用者資料
            $usersModel = Z2Model::getInstance('users','Z2Model');
            $userData = $usersModel->getUser((int)$data->id);
            if (!$userData)
            {
                JError::raiseWarning(500,'找不到會員資料');
                return true;
            }
            if (count($userData['ExtraField']) > 0 )
            {
                foreach ($userData['ExtraField'] as $key=>$val)
                {
                    $jsonVal = json_decode($val);
                    $userData['Z2ExtraField_'.$key] = $jsonVal ? $jsonVal:$val;
                }
            }
        }
        else 
        {
            $session = JFactory::getSession();
            $userData = $session->get('plgUserZ2PostData');
        }
        
        foreach ($extraFields as $extraField)
        {
            $c++;
            $outputArray[$c] = $extraField;
            $outputArray[$c]->output = '';
            $value = json_decode($extraField->value);
            $outputArray[$c]->alias = $value[0]->alias;//20140626 zoearth 補上 alias
            if (isset($userData['Z2ExtraField_'.$extraField->id]))
            {
                $value[0]->value = $userData['Z2ExtraField_'.$extraField->id];
                $extraField->value = json_encode($value);
                if ($userData['Z2ExtraField_'.$extraField->id])
                {
                    //20140626 zoearth 顯示附加欄位輸出
                    $outputArray[$c]->output = Z2HelperExtendField::sayExtendVal($extraField->id,$userData['Z2ExtraField_'.$extraField->id]);
                }
            }
            $outputArray[$c]->input = $extraFieldModel->renderExtraField($extraField, $itemID);
            $outputArray[$c]->input .= '<input type="hidden" value="'.$extraField->id.'" name="haveExtraField['.$extraField->id.']">';
        }
        $form->z2ExtendField = $outputArray;
        return true;
    }
    
    public function onUserBeforeSave($user, $isnew, $data)
    {
        //20140625 zoearth 這邊應該是檢查欄位 但目前這邊不檢查附加欄位
        
        return true;
    }
    
    public function onUserAfterSave($data, $isNew, $result, $error)
    {
        //20140625 zoearth 有通過才會進入這一步 所以這邊不用判斷是否新增會員失敗
        //取得後台Model
        Z2Model::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_z2'.DS.'models');
        $extraFieldModel = Z2Model::getInstance('extraField','Z2Model');
        //取得目前預設群組ID
        $userGroupsModel = Z2Model::getInstance('userGroups','Z2Model');
        $defaultNewUsertype = (int)$userGroupsModel->getDefaultNewUsertype();
        if (!($defaultNewUsertype >= 26 ))
        {
            JError::raiseWarning(500,'找不到預設群組');
            return true;
        }
        //取得目前預設群組的附加欄位ID
        $userGroupModel = Z2Model::getInstance('userGroup','Z2Model');
        $groupData = $userGroupModel->getData($defaultNewUsertype);
        if (!($groupData['extraFieldsGroup'] > 0 ))
        {
            return true;
        }
        //取得目前預設群組的附加欄位
        $extraFields = $extraFieldModel->getExtraFieldsByGroup((int)$groupData['extraFieldsGroup']);
        if (!(is_array($extraFields) && count($extraFields) > 0 ))
        {
            return true;
        }
    
        //修改附加欄位 不修改群組
        //20140512 zoearth 修改不開放修改帳號 所以讀取原本帳號
        $usersModel = Z2Model::getInstance('users','Z2Model');
        $userData = $usersModel->getUser((int)$data['id']);
        if (!$userData)
        {
            JError::raiseWarning(500,'修改附加欄位時找不到會員資料');
            return true;
        }
        //如果沒有  mainGroupId 則設定 mainGroupId .在前台會員修改時 不修改附加群組
        if (!($userData['mainGroupId'] > 0 ))
        {
            $userData['mainGroupId'] = $defaultNewUsertype;
        }
        
        $goData = array(
                'id'          => (int)$data['id'],
                'update'      => TRUE,//只修改附加欄位
                'mainGroupId' => $userData['mainGroupId'],
                'haveExtraField' => $_POST['haveExtraField'],
        );
        foreach ($_POST['haveExtraField'] as $key=>$val)
        {
            $goData['Z2ExtraField_'.(int)$key] = @$_POST['Z2ExtraField_'.(int)$key];
        }
        //更新使用者
        $user = $usersModel->save($goData,'site');
        
        return true;
    }

    public function onUserAfterDelete($user, $success, $msg)
    {
        //$user['id'];
        return true;
    }
}