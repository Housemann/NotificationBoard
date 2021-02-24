<?php

    require_once __DIR__ . '/../libs/helper_variables.php';
    require_once __DIR__ . '/../libs/helper_hook.php';
    require_once __DIR__ . '/../libs/helper_scripts.php';

    // Klassendefinition
    class NotificationBoard extends IPSModule {

        use STNB_HelperVariables;
        use STNB_HelperHook;
        use STNB_HelperScripts;

        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen..
            parent::Create();

            // FormularListe
            $this->RegisterPropertyString("notificationWays","");

            // Propertys
            $this->RegisterPropertyString('Username', '');
            $this->RegisterPropertyString('Password', '');

            $this->RegisterPropertyBoolean("CreateNotifyTypes",false);
            $this->RegisterPropertyBoolean("CreateHtmlBox",false);
            $this->RegisterPropertyBoolean("CreatePopUpModul",false);

            $this->RegisterPropertyBoolean("NotifyTypesVisible",false);
            $this->RegisterPropertyBoolean("HtmlVisible",false);
            $this->RegisterPropertyBoolean("PopUpVisible",false);
            $this->RegisterPropertyBoolean("InstanceVisible",false);

            // Vorlage anlegen
            $this->CreateSendTemplateScript ($this->InstanceID, false);

            // VariablenProfil Senden anlegen
            $this->RegisterProfileBooleanEx("STNB.SendButton", "Information", "", "", Array(
              Array(0 , $this->translate('not Send')  , ''  , '0xFF6464'),
              Array(1 , $this->translate('Send')      , ''  , '0x64FF64')
            ));
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();


            // Variablen
            if($this->ReadPropertyBoolean("CreateNotifyTypes")==true) {
              // Variable Notify Typen anlegen
              $this->RegisterVariableInteger("NotifyTypes", $this->translate("Notify Types"), "", -3);
              $this->EnableAction ("NotifyTypes");

              // Unsichtbar schalten über Checkbox
              if($this->ReadPropertyBoolean("NotifyTypesVisible")===true) {
                IPS_SetHidden(@$this->GetIDForIdent("NotifyTypes"),true);
              } else {
                IPS_SetHidden(@$this->GetIDForIdent("NotifyTypes"),false);
              }
            } else {
              @$this->UnregisterVariable("NotifyTypes");
            }
                  
            // Html Box anlegen
            if($this->ReadPropertyBoolean("CreateHtmlBox")==true) {
                $this->RegisterVariableString("NotifyWays", $this->translate("Notify Ways"), "~HTMLBox", -2);

                // Unsichtbar schalten über Checkbox
                if($this->ReadPropertyBoolean("HtmlVisible")===true) {
                  IPS_SetHidden(@$this->GetIDForIdent("NotifyWays"),true);
                } else {
                  IPS_SetHidden(@$this->GetIDForIdent("NotifyWays"),false);
                }
            } else {
              @$this->UnregisterVariable("NotifyWays");
            }

            // PopUp Instanz erstellen
            if($this->ReadPropertyBoolean("CreatePopUpModul")==true) {
              $this->CreatePopUpByIdent($this->InstanceID, "PopUpNotifyWays", $this->translate("Notify"), -1);
              
              // PupUp fuellen
              $this->CreateLinkInPopUp($this->InstanceID, $this->GetIDForIdent("NotifyTypes"), $this->GetIDForIdent("PopUpNotifyWays"));
              
              // Unsichtbar schalten über Checkbox
              if($this->ReadPropertyBoolean("PopUpVisible")==true ) {  
                IPS_SetHidden(@$this->GetIDForIdent("PopUpNotifyWays"),true);
              } else {
                IPS_SetHidden(@$this->GetIDForIdent("PopUpNotifyWays"),false);
              }
            } else {
              $iid = @IPS_GetInstanceIDByName($this->translate("Notify"), $this->InstanceID);
              $LinkID = IPS_GetChildrenIDs($iid);
              @IPS_DeleteLink ($LinkID[0]);
              @IPS_DeleteInstance($iid);
            }

            // Sichtbarkeit Instanzen ändern
            $this->ChangeVisibility($this->ReadPropertyBoolean("InstanceVisible"));

            // Scripte anlegen
            $this->CreateActionScript ($this->InstanceID, true);
            $this->CreateRunScript ($this->InstanceID, true);

            // VariablenProfil aktualisieren
            if($this->ReadPropertyBoolean("CreateNotifyTypes")==true) {
              $this->CreateVariableProfile($this->GetIDForIdent("NotifyTypes"), $this->InstanceID);
            }

            // Wenn Übernehmen, werden Variablen direkt angelegt
            $this->CreateNewNotifications();

            // WebHook generieren
            $this->RegisterHook('/hook/'.$this->hook);
        }
 

        public function Destroy() 
        {
            // Remove variable profiles from this module if there is no instance left
            $InstancesAR = IPS_GetInstanceListByModuleID('{41434F5C-B8DD-ECFA-8591-9E2F2C553FC4}');
            if ((@array_key_exists('0', $InstancesAR) === false) || (@array_key_exists('0', $InstancesAR) === NULL)) {
                $VarProfileAR = array('STNB.NotificationInstanzen','STNB.SendButton');
                foreach ($VarProfileAR as $VarProfileName) {
                    @IPS_DeleteVariableProfile($VarProfileName);
                }
            }
            parent::Destroy();
        }          
        

        // Konfigurationsform laden und füllen
        public function GetConfigurationForm()
        {
          $data = json_decode(file_get_contents(__DIR__ . "/form.json"));
          
          //Only add default element if we do not have anything in persistence
          if($this->ReadPropertyString("notificationWays") == "") {			
            $data->elements[0]->values[] = Array(
              "instanceID"      => @IPS_GetInstanceListByModuleID ("{375EAF21-35EF-4BC4-83B3-C780FD8BD88A}")[0],
              "NotificationWay" => "E-Mail",
              "Receiver"        => "deine@mail.de"
            );
            $data->elements[0]->values[] = Array(
              "instanceID"      => @IPS_GetInstanceListByModuleID ("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}")[0],
              "NotificationWay" => "WebFront SendNotification",
              "Receiver"        => ""
            );    
            $data->elements[0]->values[] = Array(
              "instanceID"      => @IPS_GetInstanceListByModuleID ("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}")[0],
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


        // Auf Webhook raegieren zum schalten der Benachrichtigungen
        protected function ProcessHookData()
        {
          $this->SendDebug('Data', print_r($_GET, true), 0);
          
          if ((IPS_GetProperty($this->InstanceID, 'Username') != '') || (IPS_GetProperty($this->InstanceID, 'Password') != '')) {
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                $_SERVER['PHP_AUTH_USER'] = '';
            }
            if (!isset($_SERVER['PHP_AUTH_PW'])) {
                $_SERVER['PHP_AUTH_PW'] = '';
            }

            if (($_SERVER['PHP_AUTH_USER'] != IPS_GetProperty($this->InstanceID, 'Username')) || ($_SERVER['PHP_AUTH_PW'] != IPS_GetProperty($this->InstanceID, 'Password'))) {
                header('WWW-Authenticate: Basic Realm="NotificationBoard WebHook"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Authorization required';
                $this->SendDebug('Unauthorized', print_r($_GET, true), 0);
                return;
            }

            #IPS_LogMessage("WebHook GET", print_r($_GET, true));
       
            $id = $_GET['id'];
            if (isset($id)) {
            switch ($_GET['action']) {
                case 'toggle':
                    $aktwert = GetValue($id);
                    SetValue($id, !$aktwert);
                    $this->FillHtmlBox();
                    break;
                }	  
            }
          }
        }


        // Auf umschalten der Var Notify reagieren
        public function RequestAction($Ident, $Value) {
          switch($Ident) {
              case "NotifyTypes":
                SetValue($this->GetIDForIdent($Ident), $Value);
                // prüfen ob 
                if($this->ReadPropertyBoolean("CreateNotifyTypes")==true) {
                  $this->CreateVariableProfile($this->GetIDForIdent("NotifyTypes"), $this->InstanceID);
                  
                  // pruefen HtmlBox eingeschaltet
                  if($this->ReadPropertyBoolean("CreateHtmlBox")==true) {
                    $this->FillHtmlBox();
                  }
                  
                  // pruefen PopUp eingeschaltet
                  if($this->ReadPropertyBoolean("CreatePopUpModul")==true) {
                    $this->CreateLinkInPopUp($this->InstanceID, $this->GetIDForIdent("NotifyTypes"), $this->GetIDForIdent("PopUpNotifyWays"));
                  }
                }
                break;
              default:
                  throw new Exception("Invalid Ident");
          }
        }


        ############################################################################################################################################
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
          $VarIdActionsScript = IPS_GetObjectIDByName("Aktionsskript",$this->InstanceID);
          $VarIdRunScript = IPS_GetObjectIDByName("run_NotifyBoard",$this->InstanceID);

          // Array für Rückgabe der Benachrichtigungen
          $SenderArray = array();

          // hilfsVar ob Dummy neu ist
          $ChkCreateDid = 0;

          // Benachrichtigungen im Formular durch gehen
          foreach($notificationWays as $notifiWay) {
            // Dummy instanz für Benachrichtigung erstellen z.B: Klingel, Müllabfuhr, ServiceMeldung, Heizung
            $InstanceNameForIdend = $this->sonderzeichen($NotificationSubject);
            $InstanceNameForIdend = $this->specialCharacters($InstanceNameForIdend);
            
            $dummyId = @IPS_GetObjectIDByName($NotificationSubject, $this->InstanceID);
            if($dummyId==false) {
              $dummyId = $this->CreateInstanceByIdent($this->InstanceID, $this->ReduceGUIDToIdent($InstanceNameForIdend), $NotificationSubject);
              $ChkCreateDid = 1;
            }
            
            // if true dummyInstance is visible
            IPS_SetHidden ($dummyId, $this->ReadPropertyBoolean("InstanceVisible"));
            
            // Benachrichitgungsweg-Name
            $notifyWayName = $notifiWay->NotificationWay;
            $notifyWayNameVAR = $notifyWayName;
            $notifyWayNameToIdent = $this->sonderzeichen($NotificationSubject."_".$notifyWayName);
            $notifyWayNameToIdent = $this->specialCharacters($notifyWayNameToIdent);

            // Variablen anlegen wenn es dummy Modul ist
            $InstanceIdDummy = IPS_GetInstance($dummyId);
            if($InstanceIdDummy['ModuleInfo']['ModuleID'] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}") {  #Prüfen ob Mudul ein DUmmy Modul ist
              $variableId = @IPS_GetVariableIDByName($notifyWayNameVAR, $dummyId);
              if($variableId===false) {
                $variableId = $this->CreateVariable ($notifyWayNameToIdent, $notifyWayNameVAR, 0, $dummyId, 0, "STNB.SendButton", $VarIdActionsScript);
                
                // Variablenprofil aktualisieren
                if($this->ReadPropertyBoolean("CreateNotifyTypes")==true) {
                  $this->CreateVariableProfile($this->GetIDForIdent("NotifyTypes"), $this->InstanceID);
                }
              }
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
                "notifyWayName"         => $notifyWayName,            // Name für Schalter (Benachrichtigungsweg SMS, Mail etc.) worübr im RunScript gesendet werden soll
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
          if($ChkCreateDid==1) { 
            if($this->ReadPropertyBoolean("CreateNotifyTypes")===true) {
              $this->FillHtmlBox();
            }
          }
          return $SenderArray;
        }
        ############################################################################################################################################
        ############################################################################################################################################
        ############################################################################################################################################
        ############################################################################################################################################

        // Neue Benachrichtigungen (Variablen anlegen) im Apply Changes
        private function CreateNewNotifications() {
          // Benachrichtigung auslesen
          $notificationWays = json_decode($this->ReadPropertyString("notificationWays"));
          
          // Script Ids holen
          $VarIdActionsScript = IPS_GetObjectIDByName("Aktionsskript",$this->InstanceID);
          $VarIdRunScript = IPS_GetObjectIDByName("run_NotifyBoard",$this->InstanceID);

          // ChildrenIds (Subjects) vom Modul durchgehen
          foreach(IPS_GetChildrenIDs($this->InstanceID) as $cId) {
            // pruefen ob instance existiert
            if(IPS_InstanceExists ($cId)) { 
              // Benachrichtigungen im Formular durch gehen
              foreach($notificationWays as $notifiWay) {            
                // Benachrichitgungsweg-Name
                $notifyWayName = $notifiWay->NotificationWay;
                $notifyWayNameVAR = $notifyWayName;
                $notifyWayNameToIdent = $this->sonderzeichen(IPS_GetName($cId)."_".$notifyWayName);
                $notifyWayNameToIdent = $this->specialCharacters($notifyWayNameToIdent);

                // Variablen anlegen wenn ein Dummy Modul
                $InstanceIdDummy = IPS_GetInstance($cId);
                if($InstanceIdDummy['ModuleInfo']['ModuleID'] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}") {  #Prüfen ob Mudul ein DUmmy Modul ist
                  $this->CreateVariable ($notifyWayNameToIdent, $notifyWayNameVAR, 0, $cId, 0, "STNB.SendButton", $VarIdActionsScript);
                }
              }
            }
          }
          // HTML Box aktualieren
          $this->FillHtmlBox();
        }
        
        ############################################################################################################################################

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

                // Variablenprofil aktualisieren
                if($this->ReadPropertyBoolean("CreateNotifyTypes")==true) {
                  $Array = $this->CreateVariableProfile($this->GetIDForIdent("NotifyTypes"), $this->InstanceID);
                
                  // für Dummyname ID im Variablenprofil holen und Instanz "NotifyTypes" damit setzten und HTML Box neu laden
                  foreach($Array as $key) {
                    if($key['name']==$name) {
                      $this->SetValue("NotifyTypes",$key['key']);
                    }
                  }

                  // pruefen PopUp eingeschaltet
                  if($this->ReadPropertyBoolean("CreatePopUpModul")==true) {
                    $this->CreateLinkInPopUp($this->InstanceID, $this->GetIDForIdent("NotifyTypes"), $this->GetIDForIdent("PopUpNotifyWays"));
                  }
                }
            }
            return $iid;
        }

        private function CreatePopUpByIdent($id, $ident, $name, $position, $moduleid = '{5EA439B8-FB5C-4B81-AA35-1D14F4EA9821}')
        {
            $iid = @IPS_GetObjectIDByName($name, $id);
            if ($iid === false) {
                $iid = IPS_CreateInstance($moduleid);
                IPS_SetParent($iid, $id);
                IPS_SetName($iid, $name);
                IPS_SetIdent($iid, $ident);
                IPS_SetPosition($iid, $position);
            }
            return $iid;
        }

        private function CreateLinkInPopUp ($DashBoardid, $VarIdNotify, $PopUpUpModId) {
          $ValueInstanzID = @IPS_GetObjectIDByName(GetValueFormatted($VarIdNotify),$DashBoardid);
          $ChildIds = IPS_GetChildrenIDs($DashBoardid);
          $ArrayVarChildIds = array();
          foreach($ChildIds as $Ids) {
            if(IPS_InstanceExists($Ids) == TRUE) { #Prüfen ob Instanz existiert
              $InstanceIds = IPS_GetInstance($Ids);
              if($InstanceIds['ModuleInfo']['ModuleID'] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}") {  #Prüfen ob Mudul ein PopUp Modul ist
                switch($InstanceIds['InstanceID']) {
                  case $ValueInstanzID :
                    // pruefen ob ein alter Link existiert  
                    $PreviousLink = IPS_GetChildrenIDs($PopUpUpModId);
                    if(@IPS_LinkExists($PreviousLink[0])===true) {
                        IPS_DeleteLink($PreviousLink[0]);
                    }
                    // neuen link erstellen
                    $LinkID = IPS_CreateLink();
                    IPS_SetName($LinkID, IPS_GetName($ValueInstanzID));
                    IPS_SetParent($LinkID, $PopUpUpModId);
                    IPS_SetLinkTargetID($LinkID, $ValueInstanzID);
                  break;
                }
              }
            }
          }
        }        

        private function ChangeVisibility(bool $Value) 
        {
          $ChildIds = IPS_GetChildrenIDs($this->InstanceID);
          $ArrayVarChildIds = array();
          foreach($ChildIds as $Ids) {
            if(IPS_InstanceExists($Ids) == TRUE) { #Prüfen ob Instanz existiert
              $InstanceIds = IPS_GetInstance($Ids);		
              if($InstanceIds['ModuleInfo']['ModuleID'] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}") {  #Prüfen ob Mudul ein DUmmy Modul ist	
                IPS_SetHidden($InstanceIds['InstanceID'],$Value);
              }
            }
          }
        }

        // Variablenprofil NotificationInstanzen erstellen
        private function CreateVariableProfile(int $VarId, int $CatId)	 
        {	
          $profilename = 'STNB.NotificationInstanzen';

          if (IPS_VariableProfileExists($profilename) === false) {
            IPS_CreateVariableProfile($profilename, 1);
          }

          #Assoziationen immer vorher leeren
          $GetVarProfile = IPS_GetVariableProfile ( $profilename );
          foreach($GetVarProfile['Associations'] as $assi ) {
            @IPS_SetVariableProfileAssociation ($profilename, $assi['Value'], "", "", 0 );
          }

          $ChildIds = IPS_GetChildrenIDs($CatId);
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

          @IPS_SetVariableCustomProfile ($VarId, $profilename);

          return $NewArray;
        }


        // HTML Box füllen
        public function FillHtmlBox() 
        {
          $Username = $this->ReadPropertyString("Username");
          $Password = $this->ReadPropertyString("Password");

          if($this->ReadPropertyBoolean("CreateNotifyTypes")==true && $this->ReadPropertyBoolean("CreateHtmlBox")==true) {
            // Mit der Id den Baum durchgehen und das passende Dummy holen und in die HtmlBox schreiben
            $ValueFormattedFromNotifyTypes = GetValueFormatted($this->GetIDForIdent("NotifyTypes"));
            $ValueInstanzID = @IPS_GetObjectIDByName($ValueFormattedFromNotifyTypes,$this->InstanceID);
            $ChildIds = IPS_GetChildrenIDs($this->InstanceID);
            $ArrayVarChildIds = array();
            foreach($ChildIds as $Ids) {
                if(IPS_InstanceExists($Ids) == TRUE) { #Prüfen ob Instanz existiert
                    $InstanceIds = IPS_GetInstance($Ids);		
                    if($InstanceIds['ModuleInfo']['ModuleID'] == "{485D0419-BE97-4548-AA9C-C083EB82E61E}") {  #Prüfen ob Mudul ein DUmmy Modul ist	
                        switch($InstanceIds['InstanceID']) {
                            case $ValueInstanzID :
                                $VarIDs = IPS_GetChildrenIDs($InstanceIds['InstanceID']);
                                foreach($VarIDs as $IDs) {
                                    $ArrayVarChildIds[] = IPS_GetName($IDs);
                                }
                            break;
                        }
                    }
                }
            }
            sort($ArrayVarChildIds);
            
            // Etwas CSS und HTML
            $style = "";
            $style = $style.'<style type="text/css">';
            $style = $style.'table.test { width: 100%; border-collapse: collapse}';
            $style = $style.'Test { border: 2px solid #444455;}';
            $style = $style.'td.lst { width: 150px; text-align:center; padding: 2px; border-right: 0px solid rgba(255, 255, 255, 0.2); border-top: 0px solid rgba(255, 255, 255, 0.1); }';
            $style = $style.'.blue { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(100, 100, 255); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
            $style = $style.'.red { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(255, 100, 100); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
            $style = $style.'.green { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(100, 255, 100); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
            $style = $style.'.yellow { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(255, 255, 100); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
            $style = $style.'.orange { padding: 7px; color: rgb(255, 255, 255); background-color: rgb(255, 160, 100); background-icon: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-icon: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
            $style = $style.'</style>';

            $s = '';	
            $s = $s . $style;

            //Tabelle Erstellen
            $s = $s . '<table class=\'test\'>'; 

            $s = $s . '<tr>'; 
            #$s = $s . '<td style=\'background: #121212;font-size:12;padding: 5px;\' colspan=\'3\'><B>'.IPS_GetName($InstanceIds['InstanceID']).'</td>';
            #$s = $s . '</tr>'; 

            foreach($ArrayVarChildIds as $CIDs)
            {
                $ID = IPS_GetObjectIDByName($CIDs,$ValueInstanzID);
                IPS_Sleep(100);
                
                $class = '';
                $toggle = '';
                $aktWert = GetValue($ID);
                if ($aktWert === true) {
                    $class = 'green';
                    $toggle = $this->translate('Send');
                    
                } else {
                    $class = 'red';
                    $toggle = $this->translate('not Send');
                }	
                
                $s = $s . '<td style=\'text-align:left;font-size:15px;border-bottom:1.0px outset;border-top:0.0px outset;color:#FFFFF;\' colspan=\'2\'>'.str_replace($this->translate("Notification over... "),"",$CIDs).'</td>';
                $s = $s . '<td style=\'font-size:15px; border-bottom:1.0px outset;border-top:0.0px outset\' class=\'lst\'><div class =\''.$class.'\' onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest(); HTTP.open(\'GET\',o.url,true,\''.$Username.'\',\''.$Password.'\'); HTTP.send();};window.xhrGet({ url: \'hook/NotificationBoard?action=toggle&id='.$ID.'\' });">'.$toggle.'</div></td>';
                $s = $s . '</tr>';
            }

            // HTML Box füllen
            if(IPS_ObjectExists(@IPS_GetObjectIDByName($this->translate("Notify Ways"),$this->InstanceID)))
              @$this->SetValue("NotifyWays", $s);
          }
        }

    }