<?php

namespace Bolt\Extension\TwoKings\WhoIsEditing\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service class that handles CRUD functions
 *
 * @todo Logic for adding widget when editing config files
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class WhoIsEditingService
{
    /**
     * @var Connection
     */
    private $database;

    /**
     * @var array
     */
    private $actionsMetaData = [
        'editcontent' => ['text' => 'is editing', 'class' => 'alert-warning'],
        'update'      => ['text' => 'updated', 'class' => 'alert-danger'],
        'close'       => ['text' => 'closed', 'class' => 'alert-info'],
        'delete'      => ['text' => 'deleted', 'class' => 'alert-danger'],
    ];

    function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Fetch the actions from database
     *
     * @param Request $request          The Request object
     * @param string  $contenttype      The slug of the contenttype
     * @param int     $contentId        The id of the record
     * @param int     $userId           The id of the current user viewing the record
     * @param int     $hoursToSubstract The interval of hours to substract to query actions within the hours interval
     *
     * @return array The array of actions
     */
    public function fetchActions($request, $contenttype, $contentId, $userId, $hoursToSubstract)
    {

        if ($request->get('_route') == 'editcontent') {
            if ($this->exist($contenttype, $contentId, $userId)) {
                $this->update($contenttype, $contentId, $userId, 'editcontent');
            } else {
                $this->insert($contenttype, $contentId, $userId);
            }
        }

        $actionsSelectSQL = "SELECT user_table.displayname, action.action, action.contenttype, action.record_id FROM bolt_users user_table, bolt_extension_who_is_editing action";
        $actionsSelectSQL .= " WHERE action.user_id = user_table.id and action.action != 'close'";
        $actionsSelectSQL .= " AND action.record_id = :record_id";
        $actionsSelectSQL .= " AND action.contenttype = :contenttype";
        $actionsSelectSQL .= " AND action.date >= '".(new \DateTime())->modify('-'.$hoursToSubstract.' hours')->format('Y-m-d H:i:s')."'";
        $actionsSelectSQL .= " AND action.user_id != :action_user_id";
        $actionsSelectSQL .= " AND user_table.id != :user_id";

        $statement = $this->database->prepare($actionsSelectSQL);
        $statement->bindParam('record_id', $contentId);
        $statement->bindParam('contenttype', $contenttype);
        $statement->bindParam('action_user_id', $userId);
        $statement->bindParam('user_id', $userId);
        $statement->execute();
        $actions = $statement->fetchAll();

        return $actions;
    }

    /**
     * Check if an Action record exist in the database
     *
     * @param string $contenttype The slug of the contenttype
     * @param int    $contentId    The id of the record
     * @param int    $userId      The id of the current user viewing the record
     *
     * @return boolean
     */
    public function exist($contenttype, $contentId, $userId)
    {
        $selectQueryBuilder = $this->database->createQueryBuilder();

        $selectQueryBuilder
            ->select('*')
            ->from('bolt_extension_who_is_editing')
            ->where('user_id = :user_id', 'contenttype = :contenttype', 'record_id = :record_id')
            ->setParameter('user_id', $userId)
            ->setParameter('contenttype', $contenttype)
            ->setParameter('record_id', $contentId)
        ;

        $selectQueryBuilderResults = $selectQueryBuilder->execute()->fetchAll();

        if (empty($selectQueryBuilderResults)) {
            return false;
        }

        return true;
    }

    /**
     * Insert a new Action record in the database
     *
     * @param string $contenttype The slug of the contenttype
     * @param int    $contentId    The id of the record
     * @param int    $userId      The id of the current user viewing the record
     *
     * @return void
     */
    public function insert($contenttype, $contentId, $userId)
    {
        $this->database
            ->insert('bolt_extension_who_is_editing', [
                'user_id'     => $userId,
                'contenttype' => $contenttype,
                'record_id'   => $contentId,
                'action'      => 'editcontent',
                'date'        => date("Y-m-d H:i:s"),
            ]);
    }

    /**
     * Modify an Action record in the database
     *
     * @param string $contenttype The slug of the contenttype
     * @param int    $contentId    The id of the record
     * @param int    $userId      The id of the current user viewing the record
     * @param string $action      The action performed
     *
     * @return void
     */
    public function update($contenttype, $contentId, $userId, $action = 'editcontent')
    {
        $updateQueryBuilder = $this->database->createQueryBuilder();
        $updateQueryBuilder
            ->update('bolt_extension_who_is_editing')
            ->set('action', ':action')
            ->set('date', ':date')
            ->where('user_id = :user_id', 'contenttype = :contenttype', 'record_id = :record_id')
            ->setParameter('action', $action)
            ->setParameter('date', date("Y-m-d H:i:s"))
            ->setParameter('user_id', $userId)
            ->setParameter('contenttype', $contenttype)
            ->setParameter('record_id', $contentId)
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
