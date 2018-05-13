<?php
namespace Limbonia\Module;

/**
 * Limbonia Ticket Category Module class
 *
 * Admin module for handling ticket categories
 *
 * @author Lonnie Blansett <lonnie@limbonia.tech>
 * @package Limbonia
 */
class TicketCategory extends \Limbonia\Module
{
  use \Limbonia\Traits\ItemModule
  {
    \Limbonia\Traits\ItemModule::getColumns as originalGetColumns;
  }

  /**
   * List of column names in the order required
   *
   * @return array
   */
  protected function columnOrder()
  {
    return
    [
      'Name',
      'ParentID',
      'AssignmentMethod',
      'UserID',
      'RoleID',
      'KeyID',
      'Level'
    ];
  }

  /**
   * Generate and return whatever HTML and JavaScript is needed to make the module run in the browser
   *
   * @return string
   */
  protected function getAdminHeader()
  {
    $sHeader = parent::getAdminHeader();
    $sHeader .= "\n<style>
#TicketCategoryUserIDField,
#TicketCategoryRoleIDField,
#TicketCategoryKeyIDField,
#TicketCategoryLevelField
{
  display: none;
}
</style>\n
<script type=\"text/javascript\">
/**
 * Toggle the associated data when the Asignment Method is changed
 *
 * @param {String} sOption
 * @returns {Boolean}
 */
function toggleMethod(sOption)
{
  userDiv = document.getElementById('TicketCategoryUserIDField');
  roleDiv = document.getElementById('TicketCategoryRoleIDField');
  keyDiv = document.getElementById('TicketCategoryKeyIDField');
  levelDiv = document.getElementById('TicketCategoryLevelField');

  if (sOption === 'unassigned')
  {
    userDiv.style.display = roleDiv.style.display = keyDiv.style.display = levelDiv.style.display = 'none';
  }

  if (sOption === 'least tickets by resource' || sOption === 'round robin by resource')
  {
    userDiv.style.display = roleDiv.style.display = 'none';
    keyDiv.style.display = levelDiv.style.display = 'block';
  }

  if (sOption === 'least tickets by role' || sOption === 'round robin by role')
  {
    userDiv.style.display = keyDiv.style.display = levelDiv.style.display = 'none';
    roleDiv.style.display = 'block';
  }

  if (sOption === 'direct')
  {
    userDiv.style.display = 'block';
    roleDiv.style.display = keyDiv.style.display = levelDiv.style.display = 'none';
  }
}

$('#TicketCategoryAssignmentMethod').change(function()
{
  toggleMethod($(this).val());
});

toggleMethod($('#TicketCategoryAssignmentMethod').val());
</script>\n";

    return $sHeader;
  }

  /**
   * Generate and return a list of columns based on the specified type
   *
   * @param string $sType (optional)
   * @return array
   */
  public function getColumns($sType = null)
  {
    $sLowerType = strtolower($sType);

    if ($sLowerType == 'view')
    {
      switch ($this->oItem->assignmentMethod)
      {
        case 'direct':
          $this->aIgnore['view'] =
          [
            'RoleID',
            'KeyID',
            'Level'
          ];
          break;

        case 'unassigned':
          $this->aIgnore['view'] =
          [
            'UserID',
            'RoleID',
            'KeyID',
            'Level'
          ];
          break;

        case 'round robin by role':
        case 'least tickets by role':
          $this->aIgnore['view'] =
          [
            'UserID',
            'KeyID',
            'Level'
          ];
          break;

        case 'round robin by resource':
        case 'least tickets by resource':
          $this->aIgnore['view'] =
          [
            'KeyID',
            'Level'
          ];
          break;
      }
    }
    elseif ($sLowerType == 'search')
    {
      if ($this->sCurrentAction == 'list' || $this->oApi->method == 'post')
      {
        $this->aIgnore['search'] =
        [
          'ParentID',
          'UserID',
          'RoleID',
          'KeyID',
          'Level'
        ];
      }
    }

    return $this->originalGetColumns($sType);
  }

  /**
   * Generate and return the HTML for the specified form field based on the specified information
   *
   * @param string $sName
   * @param string $sValue
   * @param array $hData
   * @return string
   */
  public function getFormField($sName, $sValue = null, $hData = [])
  {
    if ($sName == 'ParentID')
    {
      $oSelect = $this->oController->widgetFactory('Select', "$this->sType[ParentID]");
      $oSelect->addOption('None', '');
      $oCategories = \Limbonia\Item::search('TicketCategory', ['CategoryID' => '!=:' . $this->oItem->id], 'Name');

      foreach ($oCategories as $oCat)
      {
        if ($sValue == $oCat->id)
        {
          $oSelect->setSelected($oCat->id);
        }

        $oSelect->addOption($oCat->name, $oCat->id);
      }

      return self::widgetField($oSelect, 'Parent');
    }

    return parent::getFormField($sName, $sValue, $hData);
  }

  /**
   * Generate and return the HTML for the specified form field based on the specified information
   *
   * @param string $sName
   * @param string $sValue
   * @param array $hData
   * @return string
   */
  public function getField($sName, $sValue = null, $hData = [])
  {
    if ($sName == 'ParentID')
    {
      return '';
    }

    if ($sName == 'Name')
    {
      if (empty($sValue))
      {
        return '';
      }

      $sPath = $this->oItem->parentId > 0 ? "{$this->oItem->path}: " : '';
      return $this->field("$sPath<b>$sValue</b>", $sName, $this->sType . $sName);
    }

    return parent::getField($sName, $sValue, $hData);
  }

  /**
   * Generate and return the value of the specified column
   *
   * @param \Limbonia\Item $oItem
   * @param string $sColumn
   * @return mixed
   */
  public function getColumnValue(\Limbonia\Item $oItem, $sColumn)
  {
    if ($sColumn == 'Name')
    {
      $sPath = $oItem->parentId > 0 ? "$oItem->path > " : '';
      return "$sPath<b>$oItem->name</b>";
    }

    if ($sColumn == 'AssignmentMethod')
    {
      if ($oItem->assignmentMethod == 'direct')
      {
        return 'Direct to ' . $oItem->user->name;
      }

      if ($oItem->assignmentMethod == 'unassigned')
      {
        return 'Leave Unassigned';
      }

      preg_match("/(.*?) by (.*)/", $oItem->assignmentMethod, $aMatch);
      $sMethod = ucwords($aMatch[1]) . ' between ';
      $sGroup = 'all internal users';

      if ($aMatch[2] == 'resource' && $oItem->keyID > 0)
      {
        $sGroup = 'internal users with ' . $oItem->key->name . ' access ' . ($oItem->level > 0 ? ' at level ' . $oItem->level . ' or above' : '');
      }

      if ($aMatch[2] == 'role' && $oItem->roleId > 0)
      {
        $sGroup = 'internal users with role: ' . $oItem->role->name;
      }

      return $sMethod . $sGroup;
    }

    parent::getColumnValue($oItem, $sColumn);
  }
}