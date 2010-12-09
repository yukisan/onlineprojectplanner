<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* This class has all database-logic for
* the class Widgets
*
* @link https://code.google.com/p/onlineprojectplanner/
*/

class Widgets_model extends Model  {
    
     private $_table = "Project_Widgets";
     private $_table2 = "Widgets";
     
     // will be set to true in delete-query if widget
     // is in development
     private $_deleteQuery_InDevMode = false;
     
    /**
    * Get all widgets for a specific project
    * 
    * @param string $projectID
    * @param bool active (optional, default true)
    * @return mixed
    */
     function GetProjectWidgets($projectID, $active=true)
     {
         // prepare
         $active = ($active === true ? 1 : 0);
         
         // run query
         $table1 = $this->_table;
         $table2 = $this->_table2;
         $this->db->select("$table1.*, $table2.Widget_name");
         $this->db->from($this->_table);
         $this->db->join($table2, "$table1.Widget_id = $table2.Widget_id");
         $this->db->where(array("$table1.Is_active" => $active, "$table1.Project_id" => $projectID));
         $this->db->order_by("$table1.Order ASC");
         $query = $this->db->get();
         
         // any result?
         if ($query && $query->num_rows() > 0)
            return $query->result();
         else
            return false;
     }
    
    /**
    * This will get all widgets in the database
    * @return mixed
    */
    function GetStoredWidgets()
    {
         // run query
         $query = $this->db->get_where($this->_table2);
         
         // any result?
         if ($query && $query->num_rows() > 0)
            return $query->result();
         else
            return false; 
    }
    
    /**
    * This will add a list of names to the database (table Widgets)
    * 
    * @param array $names
    * @return bool
    */
    function AddStoredWidgets($names)
    {
        // start a transaction; all or nothing
        $this->db->trans_begin();
        
        foreach($names as $row)
        {
            $this->db->insert($this->_table2, array('Widget_name' => $row) );
        
            // nothing changed?
            if ( $this->db->affected_rows() == 0 )
            {
                // roll back transaction and return false
                $this->db->trans_rollback();
                return false;
            }
        } 
        
        // else; all ok! commit transaction and return true
        $this->db->trans_commit();
        return true;
    }
    
    /**
    * This will delete a list of names to the database (table Widgets)
    * 
    * @param array $names
    * @return bool
    */
    function DeleteStoredWidgets($names)
    {
        
        // start a transaction; all or nothing
        $this->db->trans_begin();
        
        foreach($names as $row)
        {
            // is widget in devmode?
            $query = $this->db->get_where($this->_table2, array('Widget_name' => $row));
            $result = $query->result();
            if ($result[0]->In_development == '1')
            {
                // yes, do a override
                $this->_deleteQuery_InDevMode = true;
                return false;
            }
            
            
            $res = $this->db->delete($this->_table2, array('Widget_name' => $row) );
        
            // nothing changed?
            if ( $res == false )
            {
                // roll back transaction and return false
                $this->db->trans_rollback();
                return false;
            }
        } 
        
        // else; all ok! commit transaction and return true
        $this->db->trans_commit();
        return true;
         
    }
    
    /**
    * Checks if last delete-query returned
    * that the widget is in development. Will
    * also reset the status upon exit.
    * 
    * @return bool
    */
    function CheckDeleteQuery()
    {
        $return_value = $this->_deleteQuery_InDevMode;
        $this->_deleteQuery_InDevMode = false;   
        return $return_value;
    }
}