<?php
 
Class Wiki_lib
{
    private $_CI = null;
    private $_Current_Project_id = "";
    private $_changelog_filename = "../changelog.xml"; // relative to this file
    private $_last_error = "";
    private $_upload_dir = "uploads/project_%s/instance_%s"; // from base widget-dir
    private $_upload_dir_project = "uploads/project_%s"; // from base widget-dir  
    
    function __construct()
    {
        // fetch CI instance and model for library
        $this->_CI = & get_instance();
        $this->_CI->load->model_widget('wiki_model', 'Wiki_model'); 
        
        // fetch current project id
        $this->_CI->load->library('Project_lib', null, 'Project');
        $this->_Current_Project_id = $this->_CI->Project->checkCurrentProject();
    }
    
    /**
    * This function will return the last error
    * this class has set.
    */
    function GetLastError()
    {
        // save error, clear message and return

        $returnStr = $this->_last_error;
        $this->_last_error = "";
        return $returnStr;
    }
    
    /**
    * Will return the changelog.xml
    * as simplexml or false if not found.
    * 
    * @return mixed
    */
    function GetChangelog()
    {
        $dir = dirname(__FILE__);    
        if ( file_exists($dir.'/'.$this->_changelog_filename) )
        {
            // read file and return    
            return @simplexml_load_file($dir.'/'.$this->_changelog_filename);
        }
        else
        {
            // file was not found
            return false;
        }
    }
    
    /**
    * Fetch data for creating a menu.
    * Will return an empty array of
    * none found.
    * 
    * @return array
    */
    function GetMenuTitles($instance_id)
    {
        // get results from db
        $result = $this->_CI->Wiki_model->FetchAllMenuTitles($this->_Current_Project_id, $instance_id); 
        
        // sort children
        $unset_wiki_page_id = array();
        foreach ($result as $row)
        {
            
            // any parent set?
            if ( empty($row->Parent_wiki_page_id) == false )
            {
                // find correct parent
                foreach($result as $row2)
                {
                
                    // does id match?
                    if ( empty($row2)==false && (int)$row->Parent_wiki_page_id==(int)$row2->Wiki_page_id)
                    {
                        // add children array if not found
                        if ( isset($row2->children)==false)
                        {
                            $row2->children = array();    
                        }
                        
                        // save to parent
                        array_push($row2->children, $row);        
                        
                        // save id to be excluded
                        array_push($unset_wiki_page_id, $row->Wiki_page_id);
                    }
                    
                }
            }
         
        }
      
        $final_result = array();
      
        // create a new array and exclude id's that have been moved to a parent
        $len = count($result);
        foreach ($result as $row)     
        {
              if ( in_array($row->Wiki_page_id, $unset_wiki_page_id) == false )
              {
                array_push($final_result, $row);    
              }
        }  
           
        // return the sorted result
        return $final_result;
    }
    
    /**
    * Fetch all new pages for startpage
    * Will return an empty array of
    * none found.
    * 
    * @return array
    */
    function GetNewPages($instance_id)
    {
         return $this->_CI->Wiki_model->FetchAllNewPages($this->_Current_Project_id, $instance_id);   
    }
    
    /**
    * Fetch all updated pages for startpage
    * Will return an empty array of
    * none found.
    * 
    * @return array
    */
    function GetLastUpdatedPages($instance_id)
    {
        return $this->_CI->Wiki_model->FetchAllUpdatedPages($this->_Current_Project_id, $instance_id);  
    }
    
    
    /**
    * Fetch a page by id from db.
    * Will return empty array if not found.
    * 
    * @param int $id
    * @return mixed
    */
    function GetPage($id, $instance_id)
    {
        // get page
        $page = $this->_CI->Wiki_model->FetchPage($id, $instance_id);     
        
        // was page found?
        if ($page === false)
        {
            // no, quit
            return false;
        }
        
        // get tags for page
        $page->Tags = $this->_CI->Wiki_model->FetchPageTags($id); 
        
        // mash up tags for a string (edit page)
        if ( empty($page->Tags) == false)
        {
            $page->Tags_string = "";
            
            $len = count($page->Tags);
            for($n=0; $n<$len; $n++)
            {
                $page->Tags_string .= $page->Tags[$n]->Tag;    
                if ( $n+1<$len)
                {
                    $page->Tags_string .= ", ";
                }
            }
        }
        else
        {
            $page->Tags_string = "";    
        }
        
        // return data
        return $page;
    }
    
    
    /**
    * Fetch history for a page by id
    * Will return false if no history
    * 
    * @param int $id
    * @return mixed  
    */
    function GetHistory($id, $instance_id)
    {
        return $this->_CI->Wiki_model->FetchHistory($id, $instance_id);    
    }
    
    /**
    * Fetch a page from history to display it
    * 
    * @param int $id
    * @return mixed  
    */
    function GetHistoryPage($id, $instance_id)
    {
        // get page 
        $page = $this->_CI->Wiki_model->FetchHistoryPage($id, $instance_id);    
        
        // get tags for page
        $page->Tags = $this->_CI->Wiki_model->FetchPageTagsHistory($id); 

        // return data
        return $page;
    }
    
    /**
    * Fetch all titles that doesn't have
    * any children to use in a select-list.
    * 
    * @return mixed
    */
    function GetTitlesWithoutChildren($instance_id)
    {
        return $this->_CI->Wiki_model->FetchTitlesWithoutChildren($this->_Current_Project_id, $instance_id);   
    }
    
    /**
    * Save a new wikipage; will return false or new wiki_page_id
    * 
    * @param int $instance_id
    * @param string $title
    * @param string $text
    * @param string $tags
    * @param int $parent
    * @param int $order
    * @return mixed
    */
    function SaveNewPage($instance_id, $title, $text, $tags, $parent, $order)
    {
        // apply business rules
        $author = $this->_CI->user->getUserID();
        $project = $this->_Current_Project_id;
        $order = (empty($order) ? 0 : (int)$order); // default order: 0
        $parent = (empty($parent) ? '' : (int)$parent); // default parent: none
        $version = 1;
        
        // prepare tags
        if (empty($tags)==false)
        {
            // more than one tag?
            if (preg_match('/,/', $tags))
            {
                // split tags based on comma
                $tags = explode(',', strtolower($tags));    
                
                // loop thru result and kill spaces
                $tags_new = array();
                foreach ($tags as $tag)
                {
                    array_push($tags_new, trim($tag));    
                }
                $tags = $tags_new;
            }
            else
            {
                // manually setup only one tag (kill spaces also) 
                $tags = array( trim(strtolower($tags)) );
            }
        }
        else
        {
            // no tags to save
            $tags = array();
        }
        
        // save page in model
        $new_wiki_page_id = $this->_CI->Wiki_model->SaveNewWikiPage($instance_id, $title, $text, $parent, $order, $version, $author, $project, $tags);
        
        // any error?
        if ( $new_wiki_page_id != false )
        {
            // no, return new id
            return $new_wiki_page_id;
        }
        else
        {
            // set message and return false
            $this->_last_error = "Database error - unable to save new page";
            return false;
        }
    }
    
    /**
    * This will delete a page and all history 
    * 
    * @return bool
    */
    function DeletePage($id)
    {
        // delete with model
        $result = $this->_CI->Wiki_model->DeletePage($id);
        
        // what was the result?
        if ( $result == false )
        {
            // set message
            $this->_last_error = "Database error - unable to delete page";
            return false;
        }
        else
        {
            // run a search/update if any child is without parent
            $this->_CI->Wiki_model->UpdateNoParent();
            
            // all ok!
            return true;
        }
        
    }
    
    /**
    * Will search in the wiki for a word (full-text).
    * Returns false or a db-result
    * 
    * @param string $word
    * @return mixed
    */
    function SearchByWord($word, $instance_id)
    {
        $project_id = $this->_Current_Project_id;
        $word = strtolower($word);
        return $this->_CI->Wiki_model->SearchByWord($word, $project_id, $instance_id);
    }
    
    /**
    * Will search in the wiki for a tag
    * Returns false or a db-result
    * 
    * @param string $tag
    * @return mixed
    */
    function SearchByTag($tag, $instance_id)
    {
        $project_id = $this->_Current_Project_id;
        $tag = strtolower($tag);
        return $this->_CI->Wiki_model->SearchByTag($tag, $project_id, $instance_id);   
    }
    
    /**
    * Update a page, tags, move current version to history
    * 
    * @param int $Wiki_page_id
    * @param int $instance_id
    * @param string $title
    * @param string $text
    * @param string $tags
    * @param string $parent
    * @param string $order
    * @return bool
    */
    function UpdatePage($Wiki_page_id, $instance_id, $title, $text, $tags, $parent, $order)
    {
        // business rules  
        $author = $this->_CI->user->getUserID();
        $updated = date('Y-m-d H:i:s');
        
        // prepare tags
        if (empty($tags)==false)
        {
            // more than one tag?
            if (preg_match('/,/', $tags))
            {
                // split tags based on comma
                $tags = explode(',', strtolower($tags));    
                
                // loop thru result and kill spaces
                $tags_new = array();
                foreach ($tags as $tag)
                {
                    array_push($tags_new, trim($tag));    
                }
                $tags = $tags_new;
            }
            else
            {
                // manually setup only one tag
                $tags = array( trim(strtolower($tags)) );
            }
        }
        else
        {
            // no tags to save
            $tags = array();
        }
        
        // update
        $result = $this->_CI->Wiki_model->UpdatePageAndTags($Wiki_page_id, $instance_id, $title, $text, $tags, $parent, $order, $author, $updated);
        
        // what was the result?
        if ( $result == false )
        {
            // set message
            $this->_last_error = "Database error - unable to update page";
            return false;
        }
        else
        {
            // all ok!
            return true;
        }
    }
    
    /**
    * Get uploaded images for project and instance.
    * Files are uploaded to widgets/wiki/uploads/project_[ID:int]/instance_[ID:int]/[filename]
    * 
    * @param int $instance_id
    * @return array
    */
    function getUploadedImages($instance_id) {
        
        $returnImages = array();
        
        // prepare path 
        $dir = dirname(__FILE__).'/../'.sprintf($this->_upload_dir, $this->_Current_Project_id, $instance_id);    
        
        // does path exist?
        if ( file_exists($dir) ) {
            // read dir
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) { 
                    if ($file != '.' &&  $file != ".." && $file != ".svn") {
                        array_push($returnImages, $file);    
                    }
                }    
            }
        }
        
        // return result
        return $returnImages;
    }
    
    /**
    * Return the path to uploaded files
    * 
    * @param int $instance_id 
    * @return string
    */
    function getUploadedPath($instance_id) {
        return base_url().'system/application/widgets/wiki/'.sprintf($this->_upload_dir, $this->_Current_Project_id, $instance_id);    
    }
    
    /**
    * Process a upload form. will return false or en array
    * 
    * @param int $instance_id
    * @return mixed
    */
    function processUpload($instance_id) {
        // prepare directory names
        $full_upload_dir = dirname(__FILE__).'/../'.sprintf($this->_upload_dir, $this->_Current_Project_id, $instance_id);
        $project_upload_dir = dirname(__FILE__).'/../'.sprintf($this->_upload_dir_project, $this->_Current_Project_id);
        
        // create directories if not found
        if (file_exists($project_upload_dir) == false || file_exists($full_upload_dir) == false) {
        
            // create project directory also?
            if (file_exists($project_upload_dir) == false) {
                mkdir($project_upload_dir);
            }
            
            // create for instance
            mkdir($full_upload_dir);
        }
        
        
        // prepare CI upload library         
        $config['upload_path'] = $full_upload_dir;
        $config['allowed_types'] = 'gif|jpg|png|jpeg|tif|bmp|wmf';
        $config['max_size']    = '1500';
        $config['max_width']  = '2048';
        $config['max_height']  = '1024';
        
        // load dir and set preferences
        $this->_CI->load->library('upload', $config);
        
        // do upload
        if ( $this->_CI->upload->do_upload() == false )
        {
            // get error(s) and return false
            $this->_last_error = $this->_CI->upload->display_errors();
            return false;
        }    
        else
        {
            // all ok, return saved data
            return $this->_CI->upload->data();
        }
    }
	
	/**
	* Calculate a md5-token (10 chars) for delete image
	*
	* @param string $image_filename
	* @param int $instance_id
	* @return string
	*/
	function getImageMD5($image_filename, $instance_id) {
		$full_upload_dir = dirname(__FILE__).'/../'.sprintf($this->_upload_dir, $this->_Current_Project_id, $instance_id);
		$md5 = md5($full_upload_dir.'/'.$image_filename);
		return substr($md5, 17, 10); // md5 is 32 chars
	}
    
    /**
    * Check MD5-token that was returned
    * 
    * @return bool 
    */
    function checkMD5Token() {
        $filename = $this->_CI->input->post('filename', true);    
        $token = $this->_CI->input->post('token', true);    
        $instance_id = $this->_CI->input->post('instance_id', true);
        
        $full_upload_dir = dirname(__FILE__).'/../'.sprintf($this->_upload_dir, $this->_Current_Project_id, $instance_id);
        $md5 = md5($full_upload_dir.'/'.$filename);
        $md5 = substr($md5, 17, 10);
        
        if ( $md5  == $token ) {
            return true;
        } else {
            return false;
        }
        
    }
    
    /**
    * Delete an image
    * 
    * @return bool 
    */
    function deleteImage() {
        $filename = $this->_CI->input->post('filename', true);      
        $instance_id = $this->_CI->input->post('instance_id', true);  
        
        $full_upload_dir = dirname(__FILE__).'/../'.sprintf($this->_upload_dir, $this->_Current_Project_id, $instance_id);
        $filename = $full_upload_dir.'/'.$filename;
       
        return unlink($filename); 
    }
}
  