<?php

namespace Bolt\Extension\TwoKings\WhoIsEditing\Controller;

use Bolt\Controller\Base;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller class
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class WhoIsEditingController extends Base
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $ctr)
    {
        $ctr
            ->get('/editorsActions', [$this, 'getEditorsActions'])
            ->bind('whoisediting.actions')
        ;

        return $ctr;
    }

    /**
     * The callback function to render the widget template.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return Response
     */
    public function getEditorsActions(Application $app, Request $request)
    {
        $user = $app['users']->getCurrentUser();
        $recordId = $request->query->get('recordID');
        $contenttype = $request->query->get('contenttype');
        $hoursToSubstract = $app['whoisediting.config']['lastActions'];
        
        $token = $request->query->get('token');
        $internal_token = $app['csrf']->getToken('content_edit');
        if($token == $internal_token) {
            $token_valid = true;
        } else {
            $token_valid = false;
        }

        if($token_valid === false || $user === null) {
            $options =  [
                'contenttype'        => $contenttype,
                'id'                 => $recordId,
                'whoiseditingconfig' => $app['whoisediting.config'],
            ];
            return $app['twig']->render('@whoisediting/invalid_token.twig', $options);
        }

        if ($request->query->get('action') == 'close') {
            $action = 'close';
        } else {
            $action = 'editcontent';
        }

        $userId = $user['id'];
        $app['whoisediting.service']->update(
            $contenttype,
            $recordId,
            $userId,
            $action
        );

        $actions = $app['whoisediting.service']->fetchActions(
            $request,
            $contenttype,
            $recordId,
            $userId,
            $hoursToSubstract
        );

        // If we don't have actions to show, show nothing and set ajax request data
        if (empty($actions)) {
            if($request->server->get('HTTP_REFERER') !== null) {
              $editcontentRecord = parse_url($request->server->get('HTTP_REFERER'));
              $contenttype = explode('/', $editcontentRecord['path'])[3];
              $recordId = explode('/', $editcontentRecord['path'])[4];
            }
            $options =  [
                'contenttype'        => $contenttype,
                'id'                 => $recordId,
                'whoiseditingconfig' => $app['whoisediting.config'],
                'userId'             => $userId,
            ];
            
            return $app['twig']->render('@whoisediting/no_actions.twig', $options);
        }

        return $app['twig']->render('@whoisediting/actions_widget.twig', [
            'actions'            => $actions,
            'actionsmetadata'    => $app['whoisediting.service']->getActionsMetaData(),
            'whoiseditingconfig' => $app['whoisediting.config'],
            'userId'             => $userId,
        ]);
    }
}
