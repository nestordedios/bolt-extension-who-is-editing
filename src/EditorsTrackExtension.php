<?php

namespace Bolt\Extension\TwoKings\EditorsTrack;

use Bolt\Asset\Target;
use Bolt\Asset\Widget\Widget;
use Bolt\Controller\Zone;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\TwoKings\EditorsTrack\Storage;
use Bolt\Storage\Entity\Content;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Bolt\Asset\Widget\Queue;

/**
 * EditorsTrack extension class.
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class EditorsTrackExtension extends SimpleExtension
{
    use DatabaseSchemaTrait;

    /**
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        $widget1 = Widget::create()
            ->setZone(Zone::BACKEND)
            ->setLocation(Target::WIDGET_BACK_EDITCONTENT_ASIDE_TOP)
            ->setCallback([$this, 'outputActionsWidget'])
            ->setClass('editors-actions-widget')
            ->setDefer(false)
        ;

        // $widget2 = Widget::create()
        //     ->setZone(Zone::BACKEND)
        //     ->setLocation(Target::WIDGET_BACK_EDITFILE_BELOW_HEADER)
        //     ->setCallback([$this, 'outputActionsWidget'])
        //     ->setClass('editors-actions-widget')
        //     ->setDefer(false)
        // ;

        return [
            $widget1,
            // $widget2,
        ];
    }

    protected function registerBackendRoutes(ControllerCollection $collection)
    {
        // GET request
        $collection->get('/editorsActions', [$this, 'getEditorsActions']);

    }

    public function getEditorsActions(Application $app, Request $request)
    {
        $actionsMetaData = [
            'editcontent' => ['text' => 'is editing', 'class' => 'alert-warning'],
            'update'      => ['text' => 'updated',    'class' => 'alert-success'],
            'close'       => ['text' => 'closed',     'class' => 'alert-info'],
            'delete'      => ['text' => 'deleted',    'class' => 'alert-danger'],
        ];

        $user = $app['users']->getCurrentUser();
        $recordId = $request->query->get('recordID');

        $database = $app['storage']->getConnection();
        $updateQueryBuilder = $database->createQueryBuilder();

        if($request->query->get('performedAction') == 'close')
        {
            $updateQueryBuilder
                ->update('bolt_extension_editors_track_actions')
                ->set('performed_action', '?')
                ->set('performed_date', '?')
                ->where('user_id = ?', 'performed_on_contenttype = ?', 'performed_on_record_id = ?')
                ->setParameter(0, 'close')
                ->setParameter(1, date("Y-m-d H:i:s"))
                ->setParameter(2, $user['id'])
                ->setParameter(3, $request->query->get('contenttype'))
                ->setParameter(4, $recordId)
            ;

        }else{

            $updateQueryBuilder
                ->update('bolt_extension_editors_track_actions')
                ->set('performed_action', '?')
                ->set('performed_date', '?')
                ->where('user_id = ?', 'performed_on_contenttype = ?', 'performed_on_record_id = ?')
                ->setParameter(0, 'editcontent')
                ->setParameter(1, date("Y-m-d H:i:s"))
                ->setParameter(2, $user['id'])
                ->setParameter(3, $request->query->get('contenttype'))
                ->setParameter(4, $recordId)
            ;

        }

        $updateQueryBuilder->execute();

        $actionsSelectSQL = "SELECT user.displayname, action.performed_action, action.performed_on_contenttype, action.performed_on_record_id FROM bolt_users user, bolt_extension_editors_track_actions action WHERE action.user_id = user.id AND action.performed_on_record_id = '$recordId' AND action.user_id != $user[id] AND user.id != $user[id]";
        $actionsQuery = $database->query($actionsSelectSQL);
        $actions = $actionsQuery->fetchAll();

        $response = $this->renderTemplate('actions_widget.twig', ['actions' => $actions, 'actionsmetadata' => $actionsMetaData]);

        return new Response($response, Response::HTTP_OK);
    }

    /**
     * The callback function to render the widget template.
     *
     * @return string
     */
    public function outputActionsWidget()
    {
        $app = $this->getContainer();
        $request = $app['request'];
        $user = $app['users']->getCurrentUser();
        $database = $app['storage']->getConnection();
        $contenttypeslug = $request->get('contenttypeslug');
        $recordId = $request->get('id');
        $actions = [];
        if($request->get('_route') == 'editcontent') {
            $selectQueryBuilder = $database->createQueryBuilder();
            $updateQueryBuilder = $database->createQueryBuilder();
            $selectQueryBuilder
                ->select('*')
                ->from('bolt_extension_editors_track_actions')
                ->where('user_id = ?', 'performed_on_contenttype = ?', 'performed_on_record_id = ?')
                ->setParameter(0, $user['id'])
                ->setParameter(1, $contenttypeslug)
                ->setParameter(2, $recordId)
            ;
            $selectQueryBuilderResults = $selectQueryBuilder->execute()->fetchAll();

            if(empty($selectQueryBuilderResults)) {
                $database
                    ->insert('bolt_extension_editors_track_actions',
                        array('user_id' => $user['id'],
                            'performed_on_contenttype' => $contenttypeslug,
                            'performed_on_record_id' => $recordId,
                            'performed_action' => 'editcontent',
                            'performed_date' => date("Y-m-d H:i:s")
                        ))
                ;
            } else {
                $updateQueryBuilder
                    ->update('bolt_extension_editors_track_actions')
                    ->set('performed_action', '?')
                    ->set('performed_date', '?')
                    ->where('user_id = ?', 'performed_on_contenttype = ?', 'performed_on_record_id = ?')
                    ->setParameter(0, 'editcontent')
                    ->setParameter(1, date("Y-m-d H:i:s"))
                    ->setParameter(2, $user['id'])
                    ->setParameter(3, $contenttypeslug)
                    ->setParameter(4, $recordId)
                ;

                $updateQueryBuilder->execute();

            }

        }elseif ($request->get('_route') == 'fileedit') {
            dump($app['request']);
        }

        $actionsSelectSQL = "SELECT user.displayname, action.performed_action, action.performed_on_contenttype FROM bolt_users user, bolt_extension_editors_track_actions action WHERE action.user_id = user.id AND action.performed_on_record_id = '$recordId' AND action.user_id != $user[id] AND user.id != $user[id]";
        $actionsQuery = $database->query($actionsSelectSQL);
        $actions = $actionsQuery->fetchAll();

        $actionsMetaData = [
            'editcontent' => ['text' => 'is editing', 'class' => 'alert-warning'],
            'update'      => ['text' => 'updated',    'class' => 'alert-success'],
            'close'       => ['text' => 'closed',     'class' => 'alert-info'],
            'delete'      => ['text' => 'deleted',    'class' => 'alert-danger'],
        ];

        // dump(json_encode($this->renderTemplate('actions_widget.twig', ['actions' => $actions, 'actionsmetadata' => $actionsMetaData])));
        return $this->renderTemplate('actions_widget.twig', ['actions' => $actions, 'actionsmetadata' => $actionsMetaData]);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $this->extendDatabaseSchemaServices();
    }

    /**
     * {@inheritdoc}
     */
    protected function registerExtensionTables()
    {
        return [
            'extension_editors_track_actions' => Storage\Schema\Table\ActionsTable::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $parentEvents = parent::getSubscribedEvents();
        $localEvents = [
            StorageEvents::POST_SAVE  => [
                ['onSave', 0],
            ],
            StorageEvents::POST_DELETE  => [
                ['onDelete', 0],
            ],
        ];

        return $parentEvents + $localEvents;
    }

    /**
     * StorageEvents::POST_SAVE event callback.
     *
     * @param StorageEvent $event
     */
    public function onSave(StorageEvent $event)
    {
        $app = $this->getContainer();
        $database = $app['storage']->getConnection();
        $user = $app['users']->getCurrentUser();

        $updateQueryBuilder = $database->createQueryBuilder();
        $updateQueryBuilder
            ->update('bolt_extension_editors_track_actions')
            ->set('performed_action', '?')
            ->set('performed_date', '?')
            ->where('user_id = ?', 'performed_on_contenttype = ?', 'performed_on_record_id = ?')
            ->setParameter(0, 'update')
            ->setParameter(1, date("Y-m-d H:i:s"))
            ->setParameter(2, $user['id'])
            ->setParameter(3, $event->getContentType())
            ->setParameter(4, $event->getId())
        ;

        $updateQueryBuilder->execute();

    }

    public function onDelete(StorageEvent $event)
    {
        $app = $this->getContainer();
        $database = $app['storage']->getConnection();
        $user = $app['users']->getCurrentUser();

        $updateQueryBuilder = $database->createQueryBuilder();
        $updateQueryBuilder
            ->update('bolt_extension_editors_track_actions')
            ->set('performed_action', '?')
            ->set('performed_date', '?')
            ->where('user_id = ?', 'performed_on_contenttype = ?', 'performed_on_record_id = ?')
            ->setParameter(0, 'delete')
            ->setParameter(1, date("Y-m-d H:i:s"))
            ->setParameter(2, $user['id'])
            ->setParameter(3, $event->getContentType())
            ->setParameter(4, $event->getId())
        ;

        $updateQueryBuilder->execute();

    }

}
