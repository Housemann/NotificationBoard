<?php

trait STNB_HelperScripts
{
    #############################################################################################################################################################################################    
    // AktionsSkript anlegen
    private function CreateActionScript ($ParentID, $hidden=false)
    {
        $Script = '<?if ($_IPS[\'SENDER\'] == \'WebFront\') {SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);STNB_FillHtmlBox('.$this->InstanceID.');}?>';
        $ID_Aktionsscipt = @IPS_GetScriptIDByName ( "Aktionsskript", $ParentID );

        if ($ID_Aktionsscipt === false)
        {
            $NewScriptID = IPS_CreateScript ( 0 );
            IPS_SetParent($NewScriptID, $ParentID);
            #IPS_SetName($NewScriptID, "Aktionsskript");
            IPS_SetScriptContent($NewScriptID, $Script);
            if($hidden == true) {
              IPS_SetHidden($NewScriptID,true);
            }
            
            $ScriptName = 'Aktionsskript_'.$NewScriptID;
            $Script = IPS_GetScript($NewScriptID);
            rename(IPS_GetKernelDir().'/scripts/'.$Script['ScriptFile'], IPS_GetKernelDir().'/scripts/'.$ScriptName);
            IPS_SetScriptFile($NewScriptID, $ScriptName);
            IPS_SetName($NewScriptID, substr($ScriptName, 0, -6));
        }
        return $ID_Aktionsscipt;
    }

    #############################################################################################################################################################################################
    // runScript anlegen
    private function CreateRunScript ($ParentID, $hidden=false)
    {
        $Script = 
 '<? 
  $notifyWayName        = $_IPS[\'notifyWayName\'];
  $NotificationSubject 	= $_IPS[\'NotificationSubject\'];
  $InstanceId 	        = $_IPS[\'InstanceId\'];
  $NotifyType           = $_IPS[\'NotifyType\'];
  $Message 		          = $_IPS[\'Message\'];
  $Receiver             = $_IPS[\'Receiver\'];
  $ExpirationTime       = $_IPS[\'ExpirationTime\'];
  $NotifyIcon           = $_IPS[\'NotifyIcon\'];
  $MediaID              = $_IPS[\'MediaID\'];
  $AttachmentPath       = $_IPS[\'AttachmentPath\'];
  $String1              = $_IPS[\'String1\'];
  $String2              = $_IPS[\'String2\'];
  $String3              = $_IPS[\'String3\'];

  $IdNotifyBoard        = '.$this->InstanceID.';

  ### Der Name im CASE muss Identisch zu dem im Konfigurationsformular sein, damit ein Mapping stattfindet
  switch ($notifyWayName) {
    case "E-Mail":
      STNB_SendMail($IdNotifyBoard, $InstanceId, $Receiver, $NotificationSubject, $Message, $MediaID, $AttachmentPath);
      break;
    case "WebFront PopUp":
      STNB_WF_SendPopup($IdNotifyBoard, $InstanceId, $NotificationSubject, $Message);
      break;
    case "WebFront SendNotification":
      STNB_WF_SendNotification($IdNotifyBoard, $InstanceId, $NotificationSubject, $Message, $NotifyIcon, $TimeOut=4);
      break;
  }';
            
      $FileName = 'run_NotifyBoard';
      $ID_Includescipt = @IPS_GetScriptIDByName ( $FileName, $ParentID );
    
      if ($ID_Includescipt === false)
      {
          $NewScriptID = IPS_CreateScript ( 0 );
          IPS_SetParent($NewScriptID, $ParentID);
          #IPS_SetName($NewScriptID, $FileName);
          IPS_SetScriptContent($NewScriptID, $Script);
          
          if($hidden == true) {
            IPS_SetHidden($NewScriptID,true);
          }

          $FileName = 'run_NotifyBoard_'.$NewScriptID.'.ips.php';
          $Script = IPS_GetScript($NewScriptID);
          rename(IPS_GetKernelDir().'/scripts/'.$Script['ScriptFile'], IPS_GetKernelDir().'/scripts/'.$FileName);
          IPS_SetScriptFile($NewScriptID, $FileName);
          IPS_SetName($NewScriptID, substr($FileName, 0, -14));
      }
      return $ID_Includescipt;
    }

    #############################################################################################################################################################################################
    // VorlageScript anlegen
    private function CreateSendTemplateScript ($ParentID, $hidden=false)
    {
        $Script = 
 '<? 
 print_r(
  STNB_SendToNotify(
       $instanceid            = '.$this->InstanceID.'
      ,$NotificationSubject   = "Vorlage"
      ,$NotifyType            = "alarm"
      ,$NotifyIcon            = "IPS"
      ,$Message               = "Das ist eine vorlage"
      ,$Attachment            = "" ## MedienId oder Pfad
      ,$String1               = "" ## String zur freien verwendung
      ,$String2               = "" ## String zur freien verwendung
      ,$String3               = "" ## String zur freien verwendung
  ));';

    $FileName = 'VorlageSendToNotify.ips.php';
    $ID_Includescipt = @IPS_GetScriptIDByName ( $FileName, $ParentID );

    if ($ID_Includescipt === false)
    {
        $NewScriptID = IPS_CreateScript ( 0 );
        IPS_SetParent($NewScriptID, $ParentID);
        IPS_SetName($NewScriptID, $FileName);
        IPS_SetScriptContent($NewScriptID, $Script);
        
        if($hidden == true) {
          IPS_SetHidden($NewScriptID,true);
        }

        $FileName = 'VorlageSendToNotify_'.$NewScriptID.'.ips.php';
        $Script = IPS_GetScript($NewScriptID);
        rename(IPS_GetKernelDir().'/scripts/'.$Script['ScriptFile'], IPS_GetKernelDir().'/scripts/'.$FileName);
        IPS_SetScriptFile($NewScriptID, $FileName);
    }
    return $ID_Includescipt;
  } 

  #############################################################################################################################################################################################

}