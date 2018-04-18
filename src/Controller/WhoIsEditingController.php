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
     * @param Request $request
     *
     * @return \Response
     */
    public function getEditorsActions(Application $app, Request $request)
    {
        $user = $app['users']->getCurrentUser();
        $recordId = $request->query->get('recordID');
        $hourstoSubstract = $app['whoisediting.config']['lastActions'];

        $database = $app['storage']->getConnection();

        if ($request->query->get('action') == 'close') {
            $action = 'close';
        }
        else {
            $action = 'editcontent';
        }

        $app['whoisediting.service']->update(
            $request->query->get('contenttype'),
            $request->query->get('recordID'),
            $user['id'],
            $action
        );

        $actions = $app['whoisediting.service']->fetchActions(
            $request,
            $request->query->get('contenttype'),
            $request->query->get('recordID'),
            $user['id'],
            $hourstoSubstract
        );

        // If we don't have actions to show, show nothing and set ajax request data
        if(!$actions) {
            $editcontentRecord = parse_url($request->server->get('HTTP_REFERER'));
            $contenttype = explode('/', $editcontentRecord['path'])[3];
            $id = explode('/', $editcontentRecord['path'])[4];
            return $app['twig']->render('@whoisediting/no_actions.twig', [
                'contenttype'        => $contenttype,
                'id'                 => $id,
                'whoiseditingconfig' => $app['whoisediting.config'],
            ]);
        }

        return $app['twig']->render('@whoisediting/actions_widget.twig', [
            'actions'            => $actions,
            'actionsmetadata'    => $app['whoisediting.service']->getActionsMetaData(),
            'whoiseditingconfig' => $app['whoisediting.config'],
        ], []);

    }

}
