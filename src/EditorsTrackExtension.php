<?php

namespace Bolt\Extension\TwoKings\EditorsTrack;

use Bolt\Asset\Target;
use Bolt\Asset\Widget\Queue;
use Bolt\Asset\Widget\Widget;
use Bolt\Controller\Zone;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\TwoKings\EditorsTrack\Controller\EditorsTrackController;
use Bolt\Extension\TwoKings\EditorsTrack\Service\EditorsTrackService;
use Bolt\Extension\TwoKings\EditorsTrack\Storage;
use Bolt\Storage\Entity\Content;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
            '/' => new EditorsTrackController(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates' => [
                'position'  => 'prepend',
                'namespace' => 'editorstrack',
            ]
        ];
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

        $actions = $app['editorstrack.service']->fetchActions($request, $request->get('contenttypeslug'), $request->get('id'), $user['id']);

        return $this->renderTemplate('actions_widget.twig', [
            'actions' => $actions,
            'actionsmetadata' => $app['editorstrack.service']->getActionsMetaData(),
        ]);

    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $this->extendDatabaseSchemaServices();

        $app['editorstrack.service'] = $app->share(
            function ($app) {
                return new EditorsTrackService($app['storage']->getConnection());
            }
         );
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
        $user = $app['users']->getCurrentUser();

        $app['editorstrack.service']->update($event->getContentType(), $event->getId(), $user['id'], 'update');
    }

    /**
     * StorageEvents::POST_DELETE event callback.
     *
     * @param StorageEvent $event
     */
    public function onDelete(StorageEvent $event)
    {
        $app = $this->getContainer();
        $user = $app['users']->getCurrentUser();

        $app['editorstrack.service']->update($event->getContentType(), $event->getId(), $user['id'], 'delete');
    }

}
