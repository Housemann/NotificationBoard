<?php

trait STNB_HelperVariables
{

   /**
     * Variable_Register (register and create variable with some parameters)
     *
     * @param $identName
     * @param $name
     * @param $type
     * @param $parentId
     * @param $position
     * @param $profile
     * @param $action
     * @param $referenceID
     */
    protected function CreateVariable ($identName, $name, $type, $parentId, $position=0, $profile="",$action=null, $referenceID=null)
    {
      $variableId = @IPS_GetVariableIDByName($name, $parentId);
      if ($variableId === false)
      {
          $variableId = IPS_CreateVariable($type);
          IPS_SetParent($variableId, $parentId);
          IPS_SetName($variableId, $name);
          IPS_SetIdent($variableId, $identName);
          IPS_SetPosition($variableId, $position);
          if ($referenceID != null)
          {
              $variable = IPS_GetVariable($referenceID);
              $profile  = $variable['VariableProfile'];
          }
          IPS_SetVariableCustomProfile($variableId, $profile);
          IPS_SetVariableCustomAction($variableId, $action);
          SetValue($variableId, FALSE);
      }
      return $variableId;
    }

    /**
       * RegisterProfileBoolean (creating a boolean variable profile with given parameters)
       *
       * @param $Name
       * @param $Icon
       * @param $Prefix
       * @param $Suffix
       * @return bool
       */
      protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix)
      {
          if (IPS_VariableProfileExists($Name) === false) {
              IPS_CreateVariableProfile($Name, 0);
          } else {
              $ProfileInfo = IPS_GetVariableProfile($Name);
              if ($ProfileInfo['ProfileType'] !== 0) {
                  $this->SendDebug(__FUNCTION__, 'Type of variable does not match the variable profile "' . $Name . '"', 0);
                  return false;
              }
          }

          IPS_SetVariableProfileIcon($Name, $Icon);
          IPS_SetVariableProfileText($Name, $Prefix, $Suffix);

          return true;
      }


      /**
       * RegisterProfileBooleanEx (creating a boolean variable profile with given parameters and extra associations)
       *
       * @param $Name
       * @param $Icon
       * @param $Prefix
       * @param $Suffix
       * @param $Associations
       * @return bool
       */
      protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations)
      {
          $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix);

          foreach ($Associations as $Association) {
              IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
          }

          return true;
      }


    /**
     * RegisterProfileInteger (creating a integer variable profile with given parameters)
     *
     * @param $Name
     * @param $Icon
     * @param $Prefix
     * @param $Suffix
     * @param $MinValue
     * @param $MaxValue
     * @param $StepSize
     * @return bool
     */
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
    {
        if (IPS_VariableProfileExists($Name) === false) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] !== 1) {
                $this->SendDebug(__FUNCTION__, 'Type of variable does not match the variable profile "' . $Name . '"', 0);
                return false;
            }
        }

        if ($StepSize > 0) {
            IPS_SetVariableProfileDigits($Name, 1);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);

        return true;
    }


    /**
     * RegisterProfileIntegerEx (creating a integer variable profile with given parameters and extra associations)
     *
     * @param $Name
     * @param $Icon
     * @param $Prefix
     * @param $Suffix
     * @param $Associations
     * @return bool
     */
    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (count($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[count($Associations) - 1][0];
        }

        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

        foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }

        return true;
    }


    /**
     * Variable_Register (register and create variable with some parameters)
     *
     * @param $VarIdent
     * @param $VarName
     * @param $VarProfile
     * @param $VarIcon
     * @param $VarType
     * @param $EnableAction
     * @param $PositionX
     */
    protected function Variable_Register($VarIdent, $VarName, $VarProfile, $VarIcon, $VarType, $EnableAction, $PositionX = false, $Debug = false)
    {
      if ($PositionX === false) {
          $Position = 0;
      } else {
          $Position = $PositionX;
      }

      switch ($VarType) {
          case 0:
            $this->RegisterVariableBoolean($VarIdent, $VarName, $VarProfile, $Position);
            $Debug_VarType = "bool";
            break;

          case 1:
            $this->RegisterVariableInteger($VarIdent, $VarName, $VarProfile, $Position);
            $Debug_VarType = "integer";
            break;

          case 2:
            $this->RegisterVariableFloat($VarIdent, $VarName, $VarProfile, $Position);
            $Debug_VarType = "float";
            break;

          case 3:
            $this->RegisterVariableString($VarIdent, $VarName, $VarProfile, $Position);
            $Debug_VarType = "string";
            break;
      }

      if ($VarIcon !== '') {
          IPS_SetIcon($this->GetIDForIdent($VarIdent), $VarIcon);
      }

      if ($EnableAction === true) {
          $this->EnableAction($VarIdent);
      }

      if ($Debug === true) {
        $Debug_Msg = "Create Variable with Type=".$Debug_VarType." (".$this->GetIDForIdent($VarIdent)."), EnableAction="."$EnableAction".",Icon=\""."$VarIcon"."\",Position="."$Position".".";
        $this->SendDebug("Variable_Register", $Debug_Msg, 0);
      }
    }

}