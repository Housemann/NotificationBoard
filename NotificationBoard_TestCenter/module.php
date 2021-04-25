<?php

    require_once __DIR__ . '/../libs/helper_variables.php';

    // Klassendefinition
    class NotificationBoard_TestCenter extends IPSModule {

        use STNB_HelperVariables;

        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen..
            parent::Create();

            // Propertys            
            $this->RegisterPropertyInteger("PropertyInstanceID",0);

            // Variablen
            $this->Variable_Register("messageBox",$this->translate("Message Box"),"~TextBox", "Edit", 3, true, 0, false);
            
            $this->Variable_Register("subject",$this->translate("Subject"),"STNB.NotificationInstanzen", "", 1, true, 4, false);            
            $this->Variable_Register("subjectNew",$this->translate("New Subject"),"", "Edit", 3, true, 8, false);
            
            $this->RegisterProfileIntegerEx('STNBTC.MessageType'  , '', '', '', Array(
                Array(1 , $this->translate('Information')   , 'Information', '0x64FF64'),
                Array(2 , $this->translate('Warning')       , 'Warning', '0xFFFF64'),
                Array(3 , $this->translate('Alert')         , 'Alert', '0xFF6464'),
                Array(4 , $this->translate('ToDo')          , 'Gear', '0x3264FF')
            ));
            $this->Variable_Register("messagetype",$this->translate("MessageType"),"STNBTC.MessageType", "", 1, true, 12, false);

            $this->RegisterProfileIntegerEx('STNBTC.Icons'  , '', '', '', Array(
                Array(1 , 'Information'     , 'Information', -1),
                Array(2 , 'Warning'         , 'Warning', -1),
                Array(3 , 'Alert'           , 'Alert', -1),
                Array(4 , 'Database'        , 'Database', -1),
                Array(5 , 'Camera'          , 'Camera', -1),
                Array(6 , 'Clock'           , 'Clock', -1),
                Array(7 , 'Battery'         , 'Battery', -1),
                Array(8 , 'Camera'          , 'Camera', -1),
                Array(9 , 'EnergyStorage'   , 'EnergyStorage', -1),
                Array(10 , 'Intensity'      , 'Intensity', -1)
            ));
            $this->Variable_Register("icons","Icons","STNBTC.Icons", "", 1, true, 16, false);

            $this->Variable_Register("mediaPath",$this->translate("Media ID or Path"),"", "Edit", 3, true, 18, false);

            $this->Variable_Register("stringContent",$this->translate("String Content"),"~TextBox", "Edit", 3, true, 30, false);

            $this-> RegisterProfileIntegerEx("STNBTC.Action", "", "", "", Array(
                Array(0 , $this->translate('clear') , ''  , '0xFF6464'),
                Array(1 , $this->translate('send')  , ''  , '0x64FF64')
            ));
            $this->Variable_Register("action",$this->translate("Action"),"STNBTC.Action", "Script", 1, true, 40, false);            

            $this->Variable_Register("debugLog",$this->translate("Debug Log"),"~TextBox", "Talk", 3, true, 50, false);



        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
        }

        public function Destroy() 
        {
            // Remove variable profiles from this module if there is no instance left
            $InstancesAR = IPS_GetInstanceListByModuleID('{F6869138-2789-4CA5-43C5-95C71D974002}');
            if ((@array_key_exists('0', $InstancesAR) === false) || (@array_key_exists('0', $InstancesAR) === NULL)) {
                $VarProfileAR = array('STNBTC.Icons','STNBTC.MessageType','STNBTC.Action');
                foreach ($VarProfileAR as $VarProfileName) {
                    @IPS_DeleteVariableProfile($VarProfileName);
                }
            }
            parent::Destroy();
        }          


        public function RequestAction($Ident, $Value) 
        {
            switch($Ident) {
                case "action":
                    SetValue($this->GetIDForIdent($Ident), $Value);
                    if( $Value === 0 )
                    {
                        $this->ClearFields($Value);
                    }
                    else 
                    {
                        $this->Send($Value);
                    }                    
                    break;
                case "messageBox":
                case "mediaPath":
                case "subjectNew":
                case "subject":
                case "messagetype":
                case "icons":
                case "stringContent":
                    SetValue($this->GetIDForIdent($Ident), $Value);
                    break;                    
                default:
                    throw new Exception("Invalid Ident");
            }
         
        }
        

        private function ClearFields(int $value)
        {
            if( $value===0 )
            {
                if( $this->GetValue("messageBox") !== "" && $this->GetValue("subjectNew") === "" )
                {
                    $this->SetValue("messageBox","");
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- "."Message Box is cleard.");
                } 
                elseif( $this->GetValue("subjectNew") !== "" &&  $this->GetValue("messageBox") === "" )
                {
                    $this->SetValue("subjectNew","");
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- "."New Subject is cleard.");
                } 
                elseif( $this->GetValue("messageBox") !== "" && $this->GetValue("subjectNew") !== "" )            
                {
                    $this->SetValue("messageBox","");
                    $this->SetValue("subjectNew","");
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- "."Message Box and New Subject is cleard.");
                } 
                else
                {
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- "."Nothing to be cleard.");
                }
            }
        }


        private function SendCheck(int $value)
        {
            if( $value===1 ) 
            {
                $ValueMessageBox = $this->GetValue("messageBox");
                $ValueAction = $this->GetValue("action");
                $ValueSubject = $this->GetValue("subject");
                $ValueNewSubject = $this->GetValue("subjectNew");
                $ValueMessageType = $this->GetValue("messagetype");
                $ValueIcons = $this->GetValue("icons");
                $rc = 0;

                if( $ValueMessageBox=="" || $ValueMessageBox==null ) 
                {
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- ".$this->translate("Message Box is empty."));
                    $rc=-1;
                }
                if( $rc>=0 && $ValueNewSubject==="" && ($ValueSubject=="" || $ValueSubject==null || $ValueSubject===0) )
                {
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- ".$this->translate("Subject is not set."));
                    $rc=-1;                    
                }
                if( $rc>=0 && ($ValueMessageType=="" || $ValueMessageType==null || $ValueMessageType===0) )
                {
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- ".$this->translate("Message-Type is not set."));
                    $rc=-1;
                }
                if( $rc>=0 && ($ValueIcons=="" || $ValueIcons==null) )
                {
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- ".$this->translate("Icon is not set."));
                    $rc=-1;
                }
            }
            return $rc;
        }
    
      
        private function Send($value)
        {
            $rc = $this->SendCheck($value);

            if ($rc>=0)
            {                
                $ValueMessageBox = $this->GetValue("messageBox");
                $ValueAction = $this->GetValue("action");                
                $ValueMessageType = GetValueFormatted($this->GetIDForIdent("messagetype"));
                $ValueIcons = GetValueFormatted($this->GetIDForIdent("icons"));
                $ValueNewSubject = $this->GetValue("subjectNew");
                $ValueMedia = $this->GetValue("mediaPath");                
                
                // Werte für String auslesen und wenn auskommentiert nicht nehmen
                $ValueStringContent = $this->GetValue("stringContent");
                if( $ValueStringContent!=="" || $ValueStringContent!==null ) 
                {
                    $ContentConvert = "{".$this->ImplodeNetString($ValueStringContent)."}";
                } 
                else 
                {
                    $ContentConvert = "";
                }
                
                // Wenn neues Subject gefüllt dann das nehmen
                if( $ValueNewSubject==="" || $ValueNewSubject===null )
                {
                    $ValueForSubject = GetValueFormatted($this->GetIDForIdent("subject"));
                }
                elseif( $ValueNewSubject!=="" || $ValueNewSubject!==null )
                {
                    $ValueForSubject = $this->GetValue("subjectNew");
                }
            

                $return = STNB_SendToNotify(
                    $InstanceId            = $this->ReadPropertyInteger("PropertyInstanceID")
                   ,$NotificationSubject   = $ValueForSubject
                   ,$NotifyType            = $ValueMessageType
                   ,$NotifyIcon            = $ValueIcons     
                   ,$Message               = $ValueMessageBox
                   ,$Attachment            = $ValueMedia
                   ,$String1               = $ContentConvert
                   ,$String2               = ""
                   ,$String3               = ""
                 );

                $Log="";
                foreach($return as $val) {
                $Log=date("d.m.Y H:i:s",time())." ---- ".$this->translate("Message Send")."\nLog:\n";
                  foreach($val as $key => $values) {
                    $val = $key."=>".$values."\n";
                    $Log.=$val;
                    $this->SetValue("debugLog",date("d.m.Y H:i:s",time())." ---- ".$Log);
                  }
                }
            }
        }


        private function ImplodeNetString (string $value)
        {
            $val = ltrim(rtrim($value));
            $val = str_replace(",","",$val);
            $val = str_replace("\n",",",$val);
            $val = preg_replace("/\s+/", "", $val);

            $arrayVal = explode(",",$val);
            $newString=[];
            foreach($arrayVal as $values)
            {
                if(substr($values,0,1)!=="#")
                {
                $newString[]=$values;
                }
            }
            $implodedString = implode(",",$newString);

            return $implodedString;
        }

    

    }