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


}