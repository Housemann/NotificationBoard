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

            // Formular E-Mail-Adresse 
            $this->RegisterPropertyString("email","");





            $this->RegisterVariableString("test","test");

        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();

            $ActionsScriptId = @$this->CreateActionScript ($this->InstanceID, true);
            $this->SetBuffer("b_ActionsScriptId", $ActionsScriptId);

            $RunScriptId = @$this->CreateRunScript ($this->InstanceID, true);
            $this->SetBuffer("b_RunScriptId", $RunScriptId);


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
              "NotificationWay" => "Test"
            );
          } else {
            //Annotate existing elements
            $notificationWays = json_decode($this->ReadPropertyString("notificationWays"));

            foreach($notificationWays as $treeRow) {
              $data->elements[0]->values[] = Array(
                "NotificationWay" => $treeRow->NotificationWay
              );				
            }			
          }
          return json_encode($data);
        }	





        public function SendToNotify(
             string $NotificationFor
            ,string $NotifyType
            ,string $NotifyIcon
            ,string $MailReciever
            ,string $Message
            ,int $ExpirationTime
        )
        {
          $notificationWays = json_decode($this->ReadPropertyString("notificationWays"));
          $VarIdActionsScript = $this->GetBuffer("b_ActionsScriptId");
          $VarIdRunScript = $this->GetBuffer("b_RunScriptId");

          foreach($notificationWays as $notifiWay) {
            // Dummy instanz für Benachrichtigung erstellen z.B: Klingel, Müllabfuhr, ServiceMeldung, Heizung
            $InstanceNameForIdend = $this->sonderzeichen($NotificationFor);
            $InstanceNameForIdend = $this->specialCharacters($InstanceNameForIdend);
            $dummyId = $this->CreateInstanceByIdent($this->InstanceID, $this->ReduceGUIDToIdent($InstanceNameForIdend), $NotificationFor);
            
            // Benachrichitgungsweg-Name
            $notifyWayName = $notifiWay->NotificationWay;
            $notifyWayNameVAR = "Benachrichtigung über... ".$notifyWayName;
            $notifyWayNameToIdent = $this->sonderzeichen($NotificationFor."_".$notifyWayName);
            $notifyWayNameToIdent = $this->specialCharacters($notifyWayNameToIdent);

            // Variablen anlegen 
            $variableId = $this->CreateVariable ($notifyWayNameToIdent, $notifyWayNameVAR, 0, $dummyId, 0, "~Switch", $VarIdActionsScript);
            
            // InstanzId aus Formular lesen
            $InstanceID = $notifiWay->instanceID;

            // Mail empänger auslesen (muss ; getrennt sein wenn es mehrere sind)
            // Mail empfänger werden in ein Array gepackt
            if(empty($MailReciever))
              $MailReciever =  $this->ReadPropertyString("email");

            if(GetValue($variableId) == true) {
              IPS_RunScriptEx($VarIdRunScript,
                array(
                  "notifyWayName"     => $notifyWayName,          // Name für swich (Benachrichtigungsweg SMS, Mail etc.) worübr im RunScript gesendet werden soll
                  "NotificationFor"   => $NotificationFor,        // Name der DummyInstanz wofür die Nachricht ist (Müllabfuhr, Klingel, ServiceMedlung)
                  "InstanceId"        => $InstanceID,             // InstanceId für Benachrichtigungsweg übergeben (wenn im Formular hinterlegt)
                  "NotifyType"        => strtolower($NotifyType), // Information / Warnung / Alarm / Aufgabe
                  "Message"           => $Message,                // Nachricht
                  "MailReciever"      => $MailReciever,           // E-Mail empfänger
                  "ExpirationTime"    => $ExpirationTime,         // Ablaufzeit wann Nachricht auf gelesen gesetzt werden soll
                  "NotifyIcon"        => $NotifyIcon              // Icons aus IP Symcon (https://www.symcon.de/service/dokumentation/komponenten/icons/)
                )
              );
            }





            $this->SetValue("test",json_encode($notificationWays));

          }
        }
          
          
          
         


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
  $notifyWayName    = $_IPS[\'notifyWayName\'];
  $NotificationFor 	= $_IPS[\'NotificationFor\'];
  $InstanceId 	    = $_IPS[\'InstanceId\'];
  $NotifyType       = $_IPS[\'NotifyType\'];
  $Message 		      = $_IPS[\'Message\'];
  $MailReciever     = $_IPS[\'MailReciever\'];
  $ExpirationTime   = $_IPS[\'ExpirationTime\'];
  $NotifyIcon       = $_IPS[\'NotifyIcon\'];

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