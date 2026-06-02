<?php
class PluginIfluxPushtoken extends CommonDBTM {
   
   static $rightname = 'config';

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
             ($this->fields['users_id'] == Session::getLoginUserID() || Session::haveRight('config', UPDATE));
   }

   function canViewItem(): bool {
      // Permite visualizar se for o próprio dono ou admin
      return Session::getLoginUserID() > 0 && 
             ($this->fields['users_id'] == Session::getLoginUserID() || Session::haveRight('config', READ));
   }
}
