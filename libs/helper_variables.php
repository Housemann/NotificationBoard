<?php

trait STNB_HelperVariables
{

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
    protected function Variable_Register($VarIdent, $VarName, $VarProfile, $VarIcon, $VarType, $EnableAction, $ParentId = "", $PositionX = false, $Debug = false)
    {
      if ($PositionX === false) {
          $Position = 0;
      } else {
          $Position = $PositionX;
      }

      switch ($VarType) {
          case 0:
            $varId = $this->RegisterVariableBoolean($VarIdent, $VarName, $VarProfile, $Position);
            $Debug_VarType = "bool";
            break;

          case 1:
            $varId = $this->RegisterVariableInteger($VarIdent, $VarName, $VarProfile, $Position);
            $Debug_VarType = "integer";
            break;

          case 2:
            $varId = $this->RegisterVariableFloat($VarIdent, $VarName, $VarProfile, $Position);
            $Debug_VarType = "float";
            break;

          case 3:
            $varId = $this->RegisterVariableString($VarIdent, $VarName, $VarProfile, $Position);
            $Debug_VarType = "string";
            break;
      }

      if ($VarIcon !== '') {
          IPS_SetIcon($this->GetIDForIdent($VarIdent), $VarIcon);
      }

      if ($EnableAction === true) {
          $this->EnableAction($VarIdent);
      }

      if ($ParentId !== '') {
        IPS_SetParent($varId, $ParentId);
      }

      if ($Debug === true) {
        $Debug_Msg = "Create Variable with Type=".$Debug_VarType." (".$this->GetIDForIdent($VarIdent)."), EnableAction="."$EnableAction".",Icon=\""."$VarIcon"."\",Position="."$Position".".";
        $this->SendDebug("Variable_Register", $Debug_Msg, 0);
      }
    }

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


}