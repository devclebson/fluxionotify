<?php
class PluginIfluxPushtoken extends CommonDBTM {
   
   static $rightname = 'plugin_iflux';

   static function getTypeName($nb = 0) {
      return 'Token de Push iFlux';
   }

   static function canCreate(): bool {
      // Permite que qualquer usuário logado inicie a criação do token
      return Session::getLoginUserID() > 0;
   }

   static function canView(): bool {
      // Permite que qualquer usuário logado inicie a busca do token
      return Session::getLoginUserID() > 0;
   }

   static function canUpdate(): bool {
      // Permite que qualquer usuário logado inicie a atualização do token
      return Session::getLoginUserID() > 0;
   }

   function canCreateItem(): bool {
      // Permite criar se o usuário estiver logado
      return Session::getLoginUserID() > 0;
   }

   function canUpdateItem(): bool {
      // Permite atualizar se for o próprio dono ou admin
      return Session::getLoginUserID() > 0 && 
             ($this->fields['users_id'] == Session::getLoginUserID() || Session::haveRight('plugin_iflux', UPDATE));
   }

   function canViewItem(): bool {
      // Permite visualizar se for o próprio dono ou admin
      return Session::getLoginUserID() > 0 && 
             ($this->fields['users_id'] == Session::getLoginUserID() || Session::haveRight('plugin_iflux', READ));
   }

   function prepareInputForAdd($input) {
      if (isset($input['users_id'])) {
         $targetUserId = (int)$input['users_id'];
         
         // Se não for admin e tentar salvar para outro usuário, cancela a operação
         if (!Session::haveRight('plugin_iflux', UPDATE) && $targetUserId !== Session::getLoginUserID()) {
            return false;
         }
      }
      return parent::prepareInputForAdd($input);
   }
}
