<?php

    require_once __DIR__ . '/../libs/helper_variables.php';
    require_once __DIR__ . '/../libs/helper_hook.php';

    // Klassendefinition
    class NotificationBoard extends IPSModule {

        use STNB_HelperVariables;
        use STNB_HelperHook;

        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();

            // FormularListe
            $this->RegisterPropertyString("notificationWays","");

            #// Propertys
            #$this->RegisterPropertyBoolean("notificationWaysEnable",false);

            // Var für Informationen der Benachrichtigungswege
            #$this->RegisterVariableString("NotifyWays","NotifyWays");
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();

            $ActionsScriptId = @$this->CreateActionScript ($this->InstanceID, true);
            $this->SetBuffer("b_ActionsScriptId", $ActionsScriptId);

            $RunScriptId = @$this->CreateRunScript ($this->InstanceID, true);
            $this->SetBuffer("b_RunScriptId", $RunScriptId);

            // Vorlage anlegen
            $this->CreateSendTemplateScript ($this->InstanceID, false);

            // Übersicht in Html darstellen und anlegen
            $InzId = $this->CreateHtmlBox($ActionsScriptId);
            $this->SetBuffer("b_VarIdInstance", $InzId);

            // VariablenProfil aktualisieren
            $this->CreateVariableProfile($InzId, $this->InstanceID);

            // Wenn Übernehmen, werden Variablen direkt angelegt
            $this->CreateNewNotifications();

            // WebHook generieren
            $this->RegisterHook('/hook/' . $this->hook);
        }
 
        
        public function GetConfigurationForm()
        {
          
          $data = json_decode(file_get_contents(__DIR__ . "/form.json"));
          
          //Only add default element if we do not have anything in persistence
          if($this->ReadPropertyString("notificationWays") == "") {			
            $data->elements[0]->values[] = Array(
              "instanceID"      => IPS_GetInstanceListByModuleID ("{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}")[0],
              "NotificationWay" => "E-Mail",
              "Receiver"        => "deine@mail.de"
            );
            $data->elements[0]->values[] = Array(
              "instanceID"      => IPS_GetInstanceListByModuleID ("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}")[0],
              "NotificationWay" => "WebFront SendNotification",
              "Receiver"        => ""
            );    
            $data->elements[0]->values[] = Array(
              "instanceID"      => IPS_GetInstanceListByModuleID ("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}")[0],
              "NotificationWay" => "WebFront PopUp",
              "Receiver"        => ""
            );                      
          } else {
            //Annotate existing elements
            $notificationWays = json_decode($this->ReadPropertyString("notificationWays"));

            foreach($notificationWays as $treeRow) {
              $data->elements[0]->values[] = Array(
                "NotificationWay" => $treeRow->NotificationWay,
                "Receiver"        => $treeRow->Receiver
              );				
            }			
          }
          return json_encode($data);
        }

        // Neue Benachrichtigungen (Variablen anlegen)
        private function CreateNewNotifications() {
          // Benachrichtigung auslesen
          $notificationWays = json_decode($this->ReadPropertyString("notificationWays"));
          
          // Script Ids holen
          $VarIdActionsScript = $this->GetBuffer("b_ActionsScriptId");
          $VarIdRunScript = $this->GetBuffer("b_RunScriptId");

          // ChildrenIds (Subjects) vom Modul durchgehen
          foreach(IPS_GetChildrenIDs($this->InstanceID) as $cId) {
            // pruefen ob instance existiert
            if(IPS_InstanceExists ($cId)) { 
              // Benachrichtigungen im Formular durch gehen
              foreach($notificationWays as $notifiWay) {            
                // Benachrichitgungsweg-Name
                $notifyWayName = $notifiWay->NotificationWay;
                $notifyWayNameVAR = $this->translate("Notification over... ").$notifyWayName;
                $notifyWayNameToIdent = $this->sonderzeichen(IPS_GetName($cId)."_".$notifyWayName);
                $notifyWayNameToIdent = $this->specialCharacters($notifyWayNameToIdent);

                // Variablen anlegen 
                $VarId = $this->CreateVariable ($notifyWayNameToIdent, $notifyWayNameVAR, 0, $cId, 0, "~Switch", $VarIdActionsScript);

                #if($this->ReadPropertyBoolean("notificationWaysEnable") == true)
                #  SetValue($VarId,true);
              }
            }
          }
        }

        ############################################################################################################################################
        ### Funktionen zum uebergabeaufruf des Boards damit Nachrichten an die Kanaele geschickt werden
        ############################################################################################################################################
        // Minimalaufruf mit Bildversenden
        public function SendToNotify(
           string $NotificationSubject
          ,string $NotifyType
          ,string $NotifyIcon
          ,string $Message
          ,string $Attachment
          ,string $String1
          ,string $String2
          ,string $String3
        )
        {
          $ExpirationTime = 0;

          $return = $this->SendToNotifyIntern($NotificationSubject , $NotifyType, $NotifyIcon, $Message, $Attachment, $ExpirationTime, $String1, $String2, $String3);
          return $return;
        }
        ############################################################################################################################################
        ############################################################################################################################################
        ############################################################################################################################################
        ### Standart Funktionen zum senden....
        ############################################################################################################################################
        // Zum versenden einer Mail mit MediaId oder Pfad einer Datei
        public function SendMail(
            int    $ModuleIdMail, 
            string $Receivers, 
            string $NotificationSubject, 
            string $Message, 
            string $MediaID="0", 
            string $AttachmentPath=""
        ) 
        {
          // Empänger in Array umwandeln wenn mehrere uebergeben wurden
          $Receivers = explode(";", str_replace(" ", "",$Receivers));

          // empaenger durchgehen
          foreach($Receivers as $Receiver) {
            
            // Log Message zusammenbauen
            $LogMessage = "ModuleIdMail: $ModuleIdMail\nReceiver: $Receiver\nNotificationSubject: $NotificationSubject\nMessage: $Message\nMediaID: $MediaID\nAttachmentPath: $AttachmentPath";

            if($Receiver !== "") {
              if($MediaID==0 && $AttachmentPath=="") {
                $Status = SMTP_SendMailEx ($ModuleIdMail, $Receiver, $NotificationSubject, $Message);

                if($Status==false) {
                  $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Fehler beim Senden der Mail.\n".$LogMessage, KL_ERROR);
                } else {
                  $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Mail erfolgreich gesendet\n".$LogMessage, KL_NOTIFY);
                }
              } elseif($MediaID>0 && $AttachmentPath!=="") {
                if(IPS_MediaExists($MediaID)) {
                  $Status = SMTP_SendMailMediaEx ($ModuleIdMail, $Receiver, $NotificationSubject, $Message, $MediaID);

                  if($Status==false) {
                    $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Fehler beim Senden der Mail.\n".$LogMessage, KL_ERROR);
                  } else {
                    $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Mail erfolgreich gesendet\n".$LogMessage, KL_NOTIFY);
                  }
                } else {
                  $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Fehler beim Sender der Mail, MediaID: $MediaID existiert nicht!\n".$LogMessage, KL_NOTIFY);
                }
              } elseif($MediaID=="" && $AttachmentPath!=="") {
                $Status = SMTP_SendMailAttachmentEx ($ModuleIdMail, $Receiver, $NotificationSubject, $Message, $AttachmentPath);

                if($Status==false) {
                  $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Fehler beim Senden der Mail.\n".$LogMessage, KL_ERROR);
                } else {
                  $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Mail erfolgreich gesendet\n".$LogMessage, KL_NOTIFY);
                }
              }
            } else {
              $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Es wurden keine Empänger hinterlegt.\n".$LogMessage, KL_ERROR);
            }
          }          
        }
        ############################################################################################################################################
        // Zum öffnen eines PupUps im Webfront
        public function WF_SendPopup(
            int    $ModuleIdWebFront, 
            string $Title, 
            string $Message
        ) 
        {
          $Status = WFC_SendPopup($ModuleIdWebFront, $Title, $Message);
          
          // Log Message zusammenbauen
          $LogMessage = "ModuleIdWebFront: $ModuleIdWebFront\nNotificationSubject: $Title\nMessage: $Message\n";

          if($Status==false) {
            $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Fehler beim Senden and Webfront.\n".$LogMessage, KL_ERROR);
          } else {
            $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Nachricht erfolgreich ans Webfront gesendet.\n".$LogMessage, KL_NOTIFY);
          }    
        }
        ############################################################################################################################################
        // Zum Senden einer Nachricht ans Webfront
        public function WF_SendNotification(
            int    $ModuleIdWebFront, 
            string $Title, 
            string $Message, 
            string $NotifyIcon, 
            int    $TimeOut
        ) 
        { 
          $Status = WFC_SendNotification($ModuleIdWebFront, $Title, $Message, $NotifyIcon, $TimeOut);
        
          // Log Message zusammenbauen
          $LogMessage = "ModuleIdWebFront: $ModuleIdWebFront\nNotificationSubject: $Title\nMessage: $Message\nIcon: $NotifyIcon\nTimeOut: $TimeOut";

          if($Status==false) {
            $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Fehler beim Senden and Webfront.\n".$LogMessage, KL_ERROR);
          } else {
            $this->LogMessage(IPS_GetName($_IPS['SELF'])." (". $_IPS['SELF'].") Nachricht erfolgreich ans Webfront gesendet.\n".$LogMessage, KL_NOTIFY);
          }    
        }
        ############################################################################################################################################ 
        ############################################################################################################################################
        // Interne Funktion zum uebergeben ans RunScript
        private function SendToNotifyIntern(
             string $NotificationSubject
            ,string $NotifyType
            ,string $NotifyIcon
            ,string $Message
            ,string $Attachment
            ,int  	$ExpirationTime
            ,string $String1
            ,string $String2
            ,string $String3
        )
        {
          // Benachrichtigung auslesen
          $notificationWays = json_decode($this->ReadPropertyString("notificationWays"));
          
          // Script Ids holen
          $VarIdActionsScript = $this->GetBuffer("b_ActionsScriptId");
          $VarIdRunScript = $this->GetBuffer("b_RunScriptId");

          // Variable mit Inhalt was gesendet wurde als Hilfe
          #$this->SetValue("NotifyWays",json_encode($notificationWays));
          
          // Array für Rückgabe der Benachrichtigungen
          $SenderArray = array();

          // Benachrichtigungen im Formular durch gehen
          foreach($notificationWays as $notifiWay) {
            // Dummy instanz für Benachrichtigung erstellen z.B: Klingel, Müllabfuhr, ServiceMeldung, Heizung
            $InstanceNameForIdend = $this->sonderzeichen($NotificationSubject);
            $InstanceNameForIdend = $this->specialCharacters($InstanceNameForIdend);
            $dummyId = $this->CreateInstanceByIdent($this->InstanceID, $this->ReduceGUIDToIdent($InstanceNameForIdend), $NotificationSubject);
            
            // Benachrichitgungsweg-Name
            $notifyWayName = $notifiWay->NotificationWay;
            $notifyWayNameVAR = $this->translate("Notification over... ").$notifyWayName;
            $notifyWayNameToIdent = $this->sonderzeichen($NotificationSubject."_".$notifyWayName);
            $notifyWayNameToIdent = $this->specialCharacters($notifyWayNameToIdent);

            // Variablen anlegen
            $variableId = @IPS_GetVariableIDByName($notifyWayNameVAR, $dummyId);
            if($variableId===false) {
              $variableId = $this->CreateVariable ($notifyWayNameToIdent, $notifyWayNameVAR, 0, $dummyId, 0, "~Switch", $VarIdActionsScript);
              
              // Variablenprofil aktualisieren
              $InzId = $this->GetBuffer("b_VarIdInstance");
              $this->CreateVariableProfile($InzId, $this->InstanceID);
            }

            // InstanzId aus Formular lesen
            $InstanceID = $notifiWay->instanceID;

            // Receiver holen
            $Receiver = $notifiWay->Receiver;

            // Ablaufdatum setzten wenn nicht übergeben
            if($ExpirationTime == 0 || empty($ExpirationTime) || $ExpirationTime == "")
              $ExpirationTime = 86400;

            // Medienübergabe (wenn Media Exisitert wird id und Pfad zurück gesendet, ansonsten nur der Pfad)
            if(is_numeric($Attachment) && IPS_MediaExists(intval($Attachment))) {
              $MediaID = intval($Attachment);
              
              $GetMedia = IPS_GetMedia(intval($Attachment));
              $AttachmentPath = IPS_GetKernelDir().$GetMedia['MediaFile'];
            } elseif (!is_numeric($Attachment)) {
              $MediaID = "";
              $AttachmentPath = $Attachment;
            }


            // Array for RunScript mit werten die uebergeben wurden
            $RunScriptArray = array(
                "notifyWayName"         => $notifyWayName,            // Name für swich (Benachrichtigungsweg SMS, Mail etc.) worübr im RunScript gesendet werden soll
                "NotificationSubject"   => $NotificationSubject,      // Name der DummyInstanz wofür die Nachricht ist (Müllabfuhr, Klingel, ServiceMedlung)
                "InstanceId"            => $InstanceID,               // InstanceId für Benachrichtigungsweg übergeben (wenn im Formular hinterlegt)
                "NotifyType"            => strtolower($NotifyType),   // Information / Warnung / Alarm / Aufgabe
                "Message"               => $Message,                  // Nachricht
                "Receiver"              => $Receiver,                 // Empfänger
                "ExpirationTime"        => $ExpirationTime,           // Ablaufzeit wann Nachricht auf gelesen gesetzt werden soll
                "NotifyIcon"            => $NotifyIcon,               // Icons aus IP Symcon (https://www.symcon.de/service/dokumentation/komponenten/icons/)
                "MediaID"               => $MediaID,                  // ID zum Medien Objekt in IPS
                "AttachmentPath"        => $AttachmentPath,           // Pfad zum Medien / Dateiobjekt
                "String1"               => $String1,                  // String zur freien verwendung
                "String2"               => $String2,                  // String zur freien verwendung
                "String3"               => $String3                   // String zur freien verwendung
                );


            if(GetValue($variableId) == true) {
              $Status = IPS_RunScriptEx($VarIdRunScript, $RunScriptArray);
              
              // Status in Array schreiben und Mergen
              $ArrayStatusRunScipt = array("StatusRunScript" => $Status);
              $ArrayMerge = array_merge($ArrayStatusRunScipt, $RunScriptArray); 
              
              // Durchgaenge Mergen
              array_push($SenderArray,$ArrayMerge);
            }
          }
          return $SenderArray;
        }
        ############################################################################################################################################
        ############################################################################################################################################
        ############################################################################################################################################
          
        // AktionsSkript anlegen
        private function CreateActionScript ($ParentID, $hidden=false)
        {
            $Script = '<?if ($_IPS[\'SENDER\'] == \'WebFront\') {SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);}?>';
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
            
            $FileName = 'run_NotifyBoard.ips.php';
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

                $FileName = 'run_NotifyBoard_'.$NewScriptID.'.ips.php';
                $Script = IPS_GetScript($NewScriptID);
                rename(IPS_GetKernelDir().'/scripts/'.$Script['ScriptFile'], IPS_GetKernelDir().'/scripts/'.$FileName);
                IPS_SetScriptFile($NewScriptID, $FileName);
            }
            return $ID_Includescipt;
        }


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
      ,$String1               = "filebot"
      ,$String2               = ""
      ,$String3               = ""
  ));
  }';

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


        private function ReduceGUIDToIdent($guid)
        {
            return str_replace(['{', '-', '}'], '', $guid);
        }

        private function sonderzeichen($string)
        {
          $search = array("Ä", "Ö", "Ü", "ä", "ö", "ü", "ß", "´");
          $replace = array("Ae", "Oe", "Ue", "ae", "oe", "ue", "ss", "");
          return str_replace($search, $replace, $string);
        }

        private function specialCharacters(string $string) 
        {
          $string = preg_replace ( '/[^a-z0-9]/i', '', $string );
          return $string;
        }

        private function CreateInstanceByIdent($id, $ident, $name, $moduleid = '{485D0419-BE97-4548-AA9C-C083EB82E61E}')
        {
            $iid = @IPS_GetObjectIDByName($name, $id);
            if ($iid === false) {
                $iid = IPS_CreateInstance($moduleid);
                IPS_SetParent($iid, $id);
                IPS_SetName($iid, $name);
                IPS_SetIdent($iid, $ident);
            }
            return $iid;
        }

        private function CreateHtmlBox(int $ActionScriptId) 
        {
          $CatName = "Html Overview";
          if(@IPS_GetCategoryIDByName($CatName,$this->InstanceID)==false) {
            $CatID = IPS_CreateCategory();
            IPS_SetName($CatID, $this->translate("Html Overview"));
            IPS_SetParent($CatID, $this->InstanceID);
            
            // Variablen anlegen
            $InzID = $this->CreateVariable("Instance", "Instance", 1, $CatID, 0, "", $ActionScriptId);
            $this->CreateVariable("HtmlOverview", "HtmlOverview", 3, $CatID, 1, "~HTMLBox", $ActionScriptId);
            
            // Variabelen Profil füllen mit DummyInstanzen
            $this->CreateVariableProfile($InzID, $this->InstanceID);
          } else {
            $CatID = @IPS_GetCategoryIDByName($CatName,$this->InstanceID);
            $InzID = @IPS_GetVariableIDByName ("Instance", $CatID);
          }
          return $InzID;
        }

        private function CreateVariableProfile(int $InzID, int $DummyID)	 
        {	
          $profilename = 'NotificationInstanzen';

          if (IPS_VariableProfileExists($profilename) === false) {
            IPS_CreateVariableProfile($profilename, 1);
          }

          #Assoziationen immer vorher leeren
          $GetVarProfile = IPS_GetVariableProfile ( $profilename );
          foreach($GetVarProfile['Associations'] as $assi ) {
            @IPS_SetVariableProfileAssociation ($profilename, $assi['Value'], "", "", 0 );
          }

          $ChildIds = IPS_GetChildrenIDs($DummyID);
          $ArrayInstanzIDs = array();
          foreach($ChildIds as $Ids) {
            if(IPS_InstanceExists($Ids) == TRUE) { #Prüfen ob Instanz existiert
              $InstanceIds = IPS_GetInstance($Ids);		
              if($InstanceIds['ModuleInfo']['ModuleID'] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}") {  #Prüfen ob Mudul ein DUmmy Modul ist	
                $ArrayInstanzIDs[] = array (
                    "name"  => IPS_GetName($InstanceIds['InstanceID']),
                    "id"    => $InstanceIds['InstanceID']
                );
              }
            }
          }
          asort($ArrayInstanzIDs);
            
          $i = 0;
          $NewArray = array();
          foreach($ArrayInstanzIDs as $key) {
            $name = $key['name'];
            $id = $key['id'];
            $NewArray[] = array(
                  "key"   => $i+1, 
                  "id"    => $id, 
                  "name"  => $name
            );
            $i++;
          }
          
          // Assoziationen füllen
          foreach($NewArray as $key) {
            IPS_SetVariableProfileAssociation($profilename, $key['key'], $key['name'], "", 0 );
          }

          IPS_SetVariableCustomProfile ($InzID, $profilename);
          
          return $NewArray;
        }

        private function FillHtmlBox($Id) 
        {
          // Mit der Id den Baum durchgehen und das passende Dummy holen und in die HtmlBox schreiben

          // Etwas CSS und HTML
          $style = "";
          $style = $style.'<style type="text/css">';
          $style = $style.'table.test { width: 100%; border-collapse: true;}';
          $style = $style.'Test { border: 2px solid #444455; }';
          $style = $style.'td.lst { width: 120px; text-align:center; padding: 2px;  border-right: 0px solid rgba(255, 255, 255, 0.2); border-top: 0px solid rgba(255, 255, 255, 0.1); }';
          $style = $style.'.blue { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(0, 0, 255); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
          $style = $style.'.red { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(255, 0, 0); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
          $style = $style.'.green { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(0, 255, 0); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
          $style = $style.'.yellow { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(255, 255, 0); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
          $style = $style.'.orange { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(255, 160, 0); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
          $style = $style.'</style>';


          $s = '';	
          $s = $s . $style;

          //Tabelle Erstellen
          $s = $s . '<table class=\'test\'>'; 

          $s = $s . '<tr>'; 
          #$s = $s . '<td style=\'background: #121212;font-size:12;padding: 5px;\' colspan=\'3\'><B>'.IPS_GetName($InstanceIds['InstanceID']).'</td>';
          #$s = $s . '</tr>'; 

          #$EID = array(); # Neue EventIDs Sammeln zum vergleich, dass die alten gelöscht werden können.
          foreach($ArrayVarChildIds as $CIDs)
          {
              $ID = IPS_GetObjectIDByName($CIDs,$ValueInstanzID);
              #$EID[] = @CreateEvent ("$ID.$CIDs", $ID);
              
              IPS_Sleep(100);
              
              $class = '';
              $toggle = '';
              $aktWert = GetValue($ID);
              if ($aktWert === true) {
                  $class = 'green';
                  $toggle = 'Senden';
                  
              } else {
                  $class = 'red';
                  $toggle = 'Nicht senden';
              }	
              
              $s = $s . '<td style=\'text-align:left;font-size:11;border-bottom:0.0px outset;border-top:0.0px outset;color:#FFFFF;\' colspan=\'2\'>'.$CIDs.'</td>';
              $s = $s . '<td style=\'border-bottom:0.0px outset;border-top:0.0px outset\' class=\'lst\'><div class =\''.$class.'\' onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/setnotify?action=toggle&id='.$ID.'\' });">'.$toggle.'</div></td>';			
              $s = $s . '</tr>';
          }

          // HTML Box füllen
          $CatIdHtmlBox = @IPS_GetCategoryIDByName("Html Overview",$this->InstanceID);
          $HtmlBox = @IPS_GetVariableIDByName("HtmlOverview", $CatIdHtmlBox);
          SetValue($HtmlBox, $s);
        }

    }