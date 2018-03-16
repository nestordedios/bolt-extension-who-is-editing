<?php

namespace Bolt\Extension\TwoKings\WhoIsEditing\Service;

use Bolt\Application;
use Bolt\Users;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service class that handles CRUD functions
 *
 * @todo: Logic for adding widget when editing config files
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */

class WhoIsEditingService {

    /**
     * @var Doctrine\DBAL\Connection
     */
    private $database;

    /**
     * @var array
     */
    private $actionsMetaData = [
        'editcontent' => ['text' => 'is editing', 'class' => 'alert-warning'],
        'update'      => ['text' => 'updated',    'class' => 'alert-success'],
        'close'       => ['text' => 'closed',     'class' => 'alert-info'],
        'delete'      => ['text' => 'deleted',    'class' => 'alert-danger'],
    ];


    function __construct($database)
    {
        $this->database = $database;
    }

    /**
    * Fetch the actions from database
    *
    * @param \Request $request     The Request object
    * @param string   $contenttype The slug of the contenttype
    * @param int      $contenid    The id of the record
    * @param int      $user_id     The id of the current user viewing the record
    *
    * @return array The array of actions
    */
    public function fetchActions($request, $contenttype, $contentid, $user_id)
    {

        if($request->get('_route') == 'editcontent') {

            if ($this->exist($contenttype, $contentid, $user_id)) {

                $this->update($contenttype, $contentid, $user_id, 'editcontent');

            } else {

                $this->insert($contenttype, $contentid, $user_id);

            }

        }
        elseif ($request->get('_route') == 'fileedit') {

        }

        $actionsSelectSQL = "SELECT user.displayname, action.action, action.contenttype, action.record_id FROM bolt_users user, bolt_extension_who_is_editing action";
        $actionsSelectSQL .= " WHERE action.user_id = user.id and action.action != 'close'";
        $actionsSelectSQL .= " AND action.record_id = :record_id";
        $actionsSelectSQL .= " AND action.contenttype = :contenttype";
        $actionsSelectSQL .= " AND action.user_id != :action_user_id";
        $actionsSelectSQL .= " AND user.id != :user_id";

        $statement = $this->database->prepare($actionsSelectSQL);
        $statement->bindParam('record_id', $contentid);
        $statement->bindParam('contenttype', $contenttype);
        $statement->bindParam('action_user_id', $user_id);
        $statement->bindParam('user_id', $user_id);
        $statement->execute();
        $actions = $statement->fetchAll();

        return $actions;
    }

    /**
     * Check if an Action record exist in the database
     *
     * @param string $contenttype The slug of the contenttype
     * @param int    $contenid    The id of the record
     * @param int    $user_id     The id of the current user viewing the record
     *
     * @return boolean
     */
    public function exist($contenttype, $contentid, $user_id)
    {
        $selectQueryBuilder = $this->database->createQueryBuilder();

        $selectQueryBuilder
            ->select('*')
            ->from('bolt_extension_who_is_editing')
            ->where('user_id = :user_id', 'contenttype = :contenttype', 'record_id = :record_id')
            ->setParameter('user_id', $user_id)
            ->setParameter('contenttype', $contenttype)
            ->setParameter('record_id', $contentid)
        ;

        $selectQueryBuilderResults = $selectQueryBuilder->execute()->fetchAll();

        if( empty($selectQueryBuilderResults) )
        {
            return false;
        }

        return true;
    }

    /**
     * Insert a new Action record in the database
     *
     * @param string $contenttype The slug of the contenttype
     * @param int    $contenid    The id of the record
     * @param int    $user_id     The id of the current user viewing the record
     *
     * @return void
     */
    public function insert($contenttype, $contentid, $user_id)
    {
        $this->database
            ->insert('bolt_extension_who_is_editing', [
                'user_id' => $user_id,
                'contenttype' => $contenttype,
                'record_id' => $contentid,
                'action' => 'editcontent',
                'date' => date("Y-m-d H:i:s")
            ])
        ;
    }

    /**
     * Modify an Action record in the database
     *
     * @param string $contenttype The slug of the contenttype
     * @param int    $contenid    The id of the record
     * @param int    $user_id     The id of the current user viewing the record
     * @param string $action      The action performed
     *
     * @return void
     */
    public function update($contenttype, $contentid, $user_id, $action = 'editcontent')
    {
        $updateQueryBuilder = $this->database->createQueryBuilder();
        $updateQueryBuilder
            ->update('bolt_extension_who_is_editing')
            ->set('action', ':action')
            ->set('date', ':date')
            ->where('user_id = :user_id', 'contenttype = :contenttype', 'record_id = :record_id')
            ->setParameter('action', $action)
            ->setParameter('date', date("Y-m-d H:i:s"))
            ->setParameter('user_id', $user_id)
            ->setParameter('contenttype', $contenttype)
            ->setParameter('record_id', $contentid)
        ;
        $updateQueryBuilder->execute();
    }

    /**
     * Get the actions metadata.
     *
     * @return array
     */
    public function getActionsMetaData()
    {
        return $this->actionsMetaData;
    }

}