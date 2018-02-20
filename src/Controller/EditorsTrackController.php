<?php

namespace Bolt\Extension\TwoKings\EditorsTrack\Controller;

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

class EditorsTrackController extends Base
{

    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $ctr)
    {
        $ctr
            ->get('/editorsActions', [$this, 'getEditorsActions'])
            ->bind('editorstrack.actions')
        ;

        return $ctr;
    }

    public function getEditorsActions(Application $app, Request $request)
    {
        $user = $app['users']->getCurrentUser();
        $recordId = $request->query->get('recordID');

        $database = $app['storage']->getConnection();

        if ($request->query->get('action') == 'close') {
            $action = 'close';
        }
        else {
            $action = 'editcontent';
        }

        $app['editorstrack.service']->update($request->query->get('contenttype'), $request->query->get('recordID'), $user['id'], $action);

        $actions = $app['editorstrack.service']->fetchActions($request, $request->query->get('contenttype'), $request->query->get('recordID'), $user['id']);

        return $this->render('@editorstrack/actions_widget.twig', [
            'actions' => $actions,
            'actionsmetadata' => $app['editorstrack.service']->getActionsMetaData(),
        ], []);

    }

}