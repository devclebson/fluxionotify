<?php
class PluginFluxionotifyPushtoken extends CommonDBTM {
   
   static $rightname = 'plugin_fluxionotify';

   static function getTypeName($nb = 0) {
      return 'Token de Push FluxIO Notify';
   }

   static function canCreate(): bool {
      // Permite via API v2 (OAuth as vezes falha no Session::getLoginUserID)
      return true;
   }

   static function canView(): bool {
      return true;
   }

   static function canUpdate(): bool {
      return true;
   }

   function canCreateItem(): bool {
      return true;
   }

   function canUpdateItem(): bool {
      return true;
   }

   function canViewItem(): bool {
      return true;
   }

   function prepareInputForAdd($input) {
      return parent::prepareInputForAdd($input);
   }
}
