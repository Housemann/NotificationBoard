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

}