<?php

    require_once __DIR__ . '/../libs/helper_variables.php';

    // Klassendefinition
    class NotificationBoard extends IPSModule {

        use STNB_HelperVariables;
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();

            // FormularListe
            $this->RegisterPropertyString("notificationWays","");

            // TestVar für Informationen
            $this->RegisterVariableString("test","test");
            $this->RegisterVariableString("test11","test11");

        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();

            $ActionsScriptId = @$this->CreateActionScript ($this->InstanceID, true);
            $this->SetBuffer("b_ActionsScriptId", $ActionsScriptId);

            $RunScriptId = @$this->CreateRunScript ($this->InstanceID, true);
            $this->SetBuffer("b_RunScriptId", $RunScriptId);

            // 
            $this->CreateNewNotifications();


            #$this->SendToNotify("Klingel", "test");
            #$this->RegisterVariableString("test","test");
        }
 
        
        public function GetConfigurationForm()
        {
          
          $data = json_decode(file_get_contents(__DIR__ . "/form.json"));
          
          //Only add default element if we do not have anything in persistence
          if($this->ReadPropertyString("notificationWays") == "") {			
            $data->elements[0]->values[] = Array(
              "instanceID"      => 12435,
              "NotificationWay" => "Test",
              "Receiver"        => "test@test.de"
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
                $notifyWayNameVAR = "Benachrichtigung über... ".$notifyWayName;
                $notifyWayNameToIdent = $this->sonderzeichen(IPS_GetName($cId)."_".$notifyWayName);
                $notifyWayNameToIdent = $this->specialCharacters($notifyWayNameToIdent);

                // Variablen anlegen 
                $this->CreateVariable ($notifyWayNameToIdent, $notifyWayNameVAR, 0, $cId, 0, "~Switch", $VarIdActionsScript);
              }
            }
          }
        }

        ############################################################################################################################################
        // Minimalaufruf
        public function SendToNotify(
           string $NotificationSubject
          ,string $NotifyType
          ,string $NotifyIcon
          ,string $Message
        )
        {
          $ExpirationTime = 0;
          $MediaID        = 0;

          $return = $this->SendToNotifyIntern($NotificationSubject , $NotifyType, $NotifyIcon, $Message, $MediaID, $ExpirationTime);
          return $return;
        }
        ############################################################################################################################################               
        // Minimalaufruf mit Bildversenden
        public function SendToNotifyImage(
           string $NotificationSubject
          ,string $NotifyType
          ,string $NotifyIcon
          ,string $Message
          ,int    $MediaID
        )
        {
          $ExpirationTime = 0;

          $return = $this->SendToNotifyIntern($NotificationSubject , $NotifyType, $NotifyIcon, $Message, $MediaID, $ExpirationTime);
          return $return;
        }
        ############################################################################################################################################
        ############################################################################################################################################
        ############################################################################################################################################
        // Interne Funktion zum uebergeben ans RunScript
        private function SendToNotifyIntern(
             string $NotificationSubject
            ,string $NotifyType
            ,string $NotifyIcon
            ,string $Message
            ,int    $MediaID
            ,int  	$ExpirationTime
        )
        {
          // Benachrichtigung auslesen
          $notificationWays = json_decode($this->ReadPropertyString("notificationWays"));
          
          // Script Ids holen
          $VarIdActionsScript = $this->GetBuffer("b_ActionsScriptId");
          $VarIdRunScript = $this->GetBuffer("b_RunScriptId");

          // Variable mit Inhalt was gesendet wurde als Hilfe
          $this->SetValue("test",json_encode($notificationWays));
          
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
            $notifyWayNameVAR = "Benachrichtigung über... ".$notifyWayName;
            $notifyWayNameToIdent = $this->sonderzeichen($NotificationSubject."_".$notifyWayName);
            $notifyWayNameToIdent = $this->specialCharacters($notifyWayNameToIdent);

            // Variablen anlegen 
            $variableId = $this->CreateVariable ($notifyWayNameToIdent, $notifyWayNameVAR, 0, $dummyId, 0, "~Switch", $VarIdActionsScript);
            
            // InstanzId aus Formular lesen
            $InstanceID = $notifiWay->instanceID;

            // Receiver holen wenn Receivers leer
            $Receiver = $notifiWay->Receiver;

            // Ablaufdatum setzten wenn nicht übergeben
            if($ExpirationTime == 0 || empty($ExpirationTime) || $ExpirationTime == "")
              $ExpirationTime = 86400;

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
                "MediaID"               => $MediaID                   // ID zum Medien Objekt in IPS
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
          
        private function CreateActionScript ($ParentID, $hidden=false)
        {
            $Script = '<?if ($_IPS[\'SENDER\'] == \'WebFront\') {SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);}?>';
            $ID_Aktionsscipt = @IPS_GetScriptIDByName ( "Aktionsskript", $ParentID );
        
            if ($ID_Aktionsscipt === false)
            {
                $NewScriptID = IPS_CreateScript ( 0 );
                IPS_SetParent($NewScriptID, $ParentID);
                IPS_SetName($NewScriptID, "Aktionsskript");
                IPS_SetScriptContent($NewScriptID, $Script);
                if($hidden == true) {
                  IPS_SetHidden($NewScriptID,true);
                }
            }
            return $ID_Aktionsscipt;
        }

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

  switch ($notifyWayName) {
    case "Fall1":     # Der Name muss Identisch sein, zu dem der im Formular hinterlegt wurde
       echo "Deine Benachrichtigung";
       break;
  }';
            $FileName = "run_NotifyBoard.ips.php";
            
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
    }