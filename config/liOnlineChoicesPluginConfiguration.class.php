<?php
/**********************************************************************************
*
*	    This file is part of e-venement.
*
*    e-venement is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License.
*
*    e-venement is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with e-venement; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
*    Copyright (c) 2006-2017 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2017 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php
class liOnlineChoicesPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    $this->initializeSubmenus();
    
    // this should usually be useless because the require is made by the config/autoload.inc.php
    $this->initializeAutoload();
    
    return parent::initialize();
  }

  public function initializeSubmenus()
  {
    // add submenus
    $this->configuration->appendMenus(array(
      'setup_extra' => array(
        'Define Time Slots' => array(
          'url'   => array(
            'app' => 'tck',
            'route' => 'oc_time_slot/index'
          ),
          'credential' => array(),
          'i18n'  => 'li_oc',
        ),
        'Context Setup' => array(
          'url'   => array(
            'app' => 'tck',
            'route' => 'ocSetup/index'
          ),
          'credential' => array(),
          'i18n'  => 'li_oc',
        ),
        'Authorize Applications' => array(
          'url'   => array(
            'app' => 'tck',
            'route' => 'ocApplication/index'
          ),
          'credential' => array(),
          'i18n'  => 'li_oc',
        ),
      ),
      'ticketting' => array(
        'Validate placements' => array(
          'url'   => array(
            'app' => 'tck',
            'route' => 'oc_snapshot/index'
          ),
          'credential' => array(),
          'i18n'  => 'li_oc',
        ),
        'Manage placements' => array(
          'url'   => array(
            'app' => 'tck',
            'route' => 'oc_backend/index'
          ),
          'credential' => array(),
          'i18n'  => 'li_oc',
        ),
      )
    ));
  }
}
