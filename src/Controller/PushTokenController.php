<?php
namespace GlpiPlugin\Fluxionotify\Controller;

use Glpi\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PluginFluxionotifyPushtoken;

class PushTokenController extends AbstractController {

    #[Route("/fluxionotify/pushtoken", name: "plugin_fluxionotify_pushtoken_update", methods: ["POST", "PUT"])]
    public function updateToken(Request $request): JsonResponse {
        $user_id = \Session::getLoginUserID();
        
        if (!$user_id) {
            return new JsonResponse(['error' => 'Acesso negado ou sessÃ£o invÃ¡lida.'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $pushToken = $data['pushtoken'] ?? '';

        if (empty($pushToken)) {
            return new JsonResponse(['error' => 'Token nÃ£o fornecido.'], 400);
        }

        $tokenItem = new PluginFluxionotifyPushtoken();
        
        // Verifica se o token jÃ¡ existe para este usuÃ¡rio
        $found = $tokenItem->find(['users_id' => $user_id]);

        if (count($found) > 0) {
            // Atualiza
            $existing = reset($found);
            $tokenItem->update([
                'id'        => $existing['id'],
                'pushtoken' => $pushToken
            ]);
        } else {
            // Cria
            $tokenItem->add([
                'users_id'  => $user_id,
                'pushtoken' => $pushToken
            ]);
        }

        return new JsonResponse(['success' => true, 'message' => 'Token salvo com sucesso.']);
    }
}

