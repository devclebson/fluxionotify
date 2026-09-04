<?php
class PluginFluxionotifyProfile extends CommonGLPI {

   public static function getIcon() {
      return 'ti ti-device-mobile';
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() === 'Profile') {
         return '<span class="d-flex align-items-center"><i class="ti ti-device-mobile me-2"></i> FluxIO Notify</span>';
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() === 'Profile') {
         self::showRightsForm($item);
      }
      return true;
   }

   public static function showRightsForm(Profile $profile) {
      global $DB;

      $profiles_id = $profile->getID();

      // Buscar direitos atuais do plugin 'plugin_fluxionotify'
      $result = $DB->request([
         'FROM'  => 'glpi_profilerights',
         'WHERE' => [
            'profiles_id' => $profiles_id,
            'name'        => 'plugin_fluxionotify'
         ]
      ]);

      $current_rights = 0;
      if ($row = $result->current()) {
         $current_rights = $row['rights'];
      }

      // Renderizar o formulário enviando para o nosso controller de config
      echo "<form action='../plugins/fluxionotify/front/config.php' method='post'>";
      echo "<input type='hidden' name='profiles_id' value='$profiles_id'>";
      echo "<input type='hidden' name='_glpi_csrf_token' value='".Session::getNewCSRFToken()."'>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='6'>Diretrizes de Permissão do FluxIO Notify</th></tr>";
      echo "<tr class='tab_bg_2' style='font-weight: bold; text-align: center;'>";
      echo "<td style='width: 30%;'>Funcionalidade</td>";
      echo "<td>LER (Visualizar)</td>";
      echo "<td>ATUALIZAR (Configurar)</td>";
      echo "<td>CRIAR (Registrar)</td>";
      echo "<td>EXCLUIR (Revogar)</td>";
      echo "<td>APAGAR (Limpar Logs)</td>";
      echo "</tr>";

      // Linha de Configuração do FluxIO Notify
      echo "<tr class='tab_bg_1' style='text-align: center;'>";
      echo "<td style='font-weight: bold; text-align: left; padding-left: 15px;'>Sincronização e Logs do FluxIO Notify</td>";
      echo "<td><input type='checkbox' name='fluxionotify_read' value='1' ".($current_rights & READ ? "checked" : "")."></td>";
      echo "<td><input type='checkbox' name='fluxionotify_update' value='1' ".($current_rights & UPDATE ? "checked" : "")."></td>";
      echo "<td><input type='checkbox' name='fluxionotify_create' value='1' ".($current_rights & CREATE ? "checked" : "")."></td>";
      echo "<td><input type='checkbox' name='fluxionotify_delete' value='1' ".($current_rights & DELETE ? "checked" : "")."></td>";
      echo "<td><input type='checkbox' name='fluxionotify_purge' value='1' ".($current_rights & PURGE ? "checked" : "")."></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td colspan='6' class='center' style='padding: 15px;'>";
      echo "<input type='submit' name='update_fluxionotify_profile' class='submit' value='Salvar Permissões' style='background-color: #143860; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: bold; cursor: pointer;'>";
      echo "</td></tr></table></form>";
   }
}
