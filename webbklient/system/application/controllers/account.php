<?php

/*
* Class Account
*/

class Account extends Controller {

	function __construct()
	{
		parent::Controller();	
		
		$this->load->library(array('validation', 'emailsender'));
		$this->load->library('project_lib', null, 'project');
	}
	
    /**
    * Function: Reset password
    *
    * Description: Will begin the process to reset password.
    * An email will be sent with a confirmation-code first.
    * 
    * If $UserID and $code is sent then confirm code and
    * create a new password.
    * 
    * @param int $UserID
    * @param int $code
    */
    function ResetPassword($UserID='', $code='')
    {
        // add a tracemessage to log
        log_message('debug','#### => Controller Account->ResetPassword');
        
        // show form or reset password?
        if ( empty($UserID) && empty($code) )
        {
            // ---------------------
            // show form
        
            $formData = array(); 
            
            /*
            * Rules for the inputfields
            */
            $rules = array(
                "email" => "trim|max_length[100]|valid_email|xss_clean",
                "username" => "trim|max_length[100]|xss_clean"
            );   
           $this->validation->set_rules($rules);  
           
           // do validation
           $status = $this->validation->run(); 
          
            // is validation ok?
           if ($status)
           { 
                // fetch post data and filter with xss_clean
                $email = (isset($this->validation->email) ? $this->validation->email : '');
                $username = (isset($this->validation->username) ? $this->validation->username : '');
                
                //  is username or email set?
                if ( empty($email) && empty($username) )
                {
                    // no, show error
                    $formData = array(
                                    "status" => "error",
                                    "status_message" => "Error(s): Please enter email or username"
                    );
                }
                else
                {
                    // begin reset process in user library    
                    if( $this->user->ResetPassword($email, $username) )
                    {
                    
                        // all ok!    
                        $formData = array(
                            "status" => "ok",
                            "status_message" => "Information has been sent to your email to reset password"
                         );
                            
                    }
                    else
                    {
                        $error_msg = $this->user->GetLastError();
                        // something went wrong...    
                        $formData = array(
                            "status" => "error",
                            "status_message" => "Error(s): ".$error_msg
                         );
                         $this->error->log($error_msg, $_SERVER['REMOTE_ADDR'], 'account/ResetPassword', 'user/ResetPassword', array('email' => $email, 'username' => $username));
                    }
                    
                }
           }
           else
           {
               // any validation error?
               $errors = validation_errors();
               if ( empty($errors) == false || empty($this->validation->error_string) == false )
               {
                   
                    // set error
                    $formData = array(
                        "status" => "error",
                        "status_message" => 'Error(s): '.strip_tags($errors.$this->validation->error_string)
                     );
                           
               }
           }
           
           // should we re-populate form?
           if ( isset($formData['status']) && $formData['status'] == 'error')
           {
                if (isset($this->validation->email)) $formData['email'] = $this->validation->email;
                if (isset($this->validation->username)) $formData['username'] = $this->validation->username;
           }
           
        }
        else
        {
            // ---------------------
            // reset password
            
            
            // reset process in user library    
            $new_password = $this->user->ConfirmResetPassword($UserID, $code);
            if( $new_password != false )
            {
            
                // all ok!    
                $formData = array(
                    "status" => "ok",
                    "status_message" => "Password reset successful! New password: ".$new_password,
                    "hideForm" => true
                 );
                    
            }
            else
            {
								$error_msg = $this->user->GetLastError();
                // something went wrong...    
                $formData = array(
                    "status" => "error",
                    "status_message" => "Error(s): ".$error_msg
                 );
								 $this->error->log($error_msg, $_SERVER['REMOTE_ADDR'], 'account/ResetPassword', 'user/ConfirmResetPassword', array('User_id' => $UserID, 'Code' => $code));
            }
            
        }
       
       // show view with pre and post content
       $this->theme->view('user/reset_password', $formData); 
    }
    
    /**
     * Autenticating the user.
     */
	 function Login()
	 {
        // add a tracemessage to log
        log_message('debug','#### => Controller Account->Login');
        
		$data = array();
		
		$username = (isset($_POST["username"])) ? trim($_POST["username"]) : null;
		$password = (isset($_POST["password"])) ? trim($_POST["password"]) : null;
		
		//If we're already logged in
		if($this->user->IsLoggedIn()) {
			redirect('project/index');
		}
		
		if(isset($_POST['login_btn'])) {
			
			if(($username == null || $password == null)) {
				$data = array(
						"status" => "error",
						"status_message" => "Please fill the form."
				);
			} else {
				
				if($this->user->IsActivated($username) == false && isset($_POST['login_btn'])) {
					$data = array(
							"status" => "error",
							"status_message" => "Your account is not activated yet! "
					);
				}
				
				if(isset($data['status']) == false) {
					if($this->user->Login($username, $password) == true) {
						redirect('project/index');
					} else {
						$data = array(
								"status" => "error",
								"status_message" => "Failed to login, Wrong username or password."
						);
					}
				}
			}
		}
			
        // any error message from authentication error?
        $error_message = $this->session->userdata('errormessage');
        if ($error_message!=false && $error_message != "")
        {
            $data['status'] = 'error';
            $data['status_message'] = $error_message;
            $this->session->unset_userdata('errormessage');
        }
        // any other message?
        $ok_message = $this->session->userdata('message');
        if ($ok_message!=false && $ok_message != "")
        {
            $data['status'] = 'ok';
            $data['status_message'] = $ok_message;
            $this->session->unset_userdata('message');
        }
        
        // show view    
		$this->theme->view('user/login_view', $data);
	 }
	 
		/**
			* Logging the user out.
			*/
   function Logout()
   {
        // add a tracemessage to log
        log_message('debug','#### => Controller Account->Logout');
        
   		$this->user->logout();
   		redirect('account/login');
   }
	 
	/**
	* Function: Register
	* 
	* Description: Will show the user/register.php view and
	* catch the formvalues if the submit button is clicked.
	*/
	function Register()
	{
		if($this->user->IsLoggedIn() !== false) {
			redirect("","");
		}
        // add a tracemessage to log
        log_message('debug','#### => Controller Account->Register');
        
		/*
		* Rules for the inputfields
		*/
		$rules = array(
			"firstname" => "trim|required|max_length[100]|alpha|xss_clean",
			"lastname" => "trim|required|max_length[100]|alpha|xss_clean",
			"email" => "trim|required|max_length[100]|xss_clean|valid_email|callback_email_check",
			"username" => "trim|required|max_length[100]|xss_clean|callback_username_check",
			"password" => "trim|required|min_length[6]|max_length[32]|xss_clean",
			"password2" => "trim|required|min_length[6]|max_length[32]|xss_clean|matches[password]",
			"streetadress" => "trim|max_length[100]|xss_clean",
			"postalcode" => "trim|max_length[5]|integer",
			"hometown" => "trim|max_length[130]|xss_clean"
		);
		
		$this->validation->set_rules($rules);
		
		/*
		* Human names for the inputfields
		*/
		$field = array(
			"firstname" => "Firstname",
			"lastname" => "Lastname",
			"email" => "Email",
			"username" => "Username",
			"password" => "Password",
			"password2" => "Repeat password",
			"streetadress" => "Streetadress",
			"postalcode" => "Postalcode",
			"hometown" => "Hometown"
		);
		
		$this->validation->set_fields($field);    
		
		$status = $this->validation->run();
		
		$data = array();
		
		if($status) {
			$insert = array(
				"Firstname" => $this->validation->firstname,
				"Lastname" => $this->validation->lastname,
				"Email" => $this->validation->email,
				"Username" => $this->validation->username,
				"Password" => $this->validation->password,
				"Streetadress" => $this->validation->streetadress,
				"Postalcode" => $this->validation->postalcode,
				"Hometown" => $this->validation->hometown
			);
			
			//Generates a random activation code
			$key = "";
			for($i = 0; $i < 5; $i++) {
				$key .= rand(1,9);
			}
			$key = md5($key);
			
			/*
			*If validation is ok => send to library
			*/
			$userid = $this->user->Register($insert, $key);
			if($userid != false && $userid > 0)
            {	
				// Sends an activationemail
				if ( $this->emailsender->SendActivationMail($insert['Firstname'], $insert['Email'], $key) == false)
				{
					$data = array(
							"status" => "error",
							"status_message" => "Failed to send activation email"
					);
					$this->user->removeUser($userid);
				}
				else
				{
						// all ok
						$data = array(
								"status" => "ok",
								"status_message" => "Registration was successful!"
						);
				}
			}
            else
            {
                // registration failed
								$this->error->log('Failed to register user. Database return false.', $_SERVER['REMOTE_ADDR'], 'account/Register', 'user/Register', $insert);
                $status = false;
            }
		}
		
        // re-populate form if error
		if($status === false && isset($_POST['register_btn'])) {
			$data = array(
				"firstname" => $this->validation->firstname,
				"lastname" => $this->validation->lastname,
				"email" => $this->validation->email,
				"username" => $this->validation->username,
				"streetadress" => $this->validation->streetadress,
				"postalcode" => $this->validation->postalcode,
				"hometown" => $this->validation->hometown,
				"status" => "error",
				"status_message" => "Registration failed!"
			);
		}
		
		$this->theme->view('user/register', $data);
	}
    
	/**
	* This function is part of the register validation. It will stop any
	* registration with an email that already exist
	* 
	*@param string $str
	*@return bool
	*/
	function email_check($str)
	{
		if($this->user->checkIfExist("Email", $str) == true) {
			$this->validation->set_message('email_check', 'That emailadress already exist in our database.');
			return false;
		}
		return true;
	}
	
	/**
	* This function is part of the register validation. It will stop any
	* registration with an username that already exist
	* 
	*@param string $str
	*@return bool
	*/
	function username_check($str)
	{   
		if($this->user->checkIfExist("Username", $str)) {
			$this->validation->set_message('username_check', 'That username already exist in our database.');
			return false;
		}
		return true;
	}
	/**
	* This function is part of the edit account validation. It will stop the user
	* from editing his information if he didnt type the right password compared
	* to his current one.
	* 
	*@param string $str
	*@return bool
	*/
	function password_check($str)
	{
		$user = $this->user->getLoggedInUser();
		if($this->user->TransformPassword($str) !== $user['Password']) {
			$this->validation->set_message('password_check', 'Please enter your current password!');
			return false;
		}
		return true;
	}
	
    
	/**
	* Function: Activate
	* This function will catch the third section of the uri
	* and activate the user who has that activationcode.
	* Will redirect the klient to the homepage if the uri is'nt
	* valid.
	*/
	function Activate()
	{
        // add a tracemessage to log
        log_message('debug','#### => Controller Account->Activate');
        
		if($this->uri->segment(3) != "") {
			if($this->user->ActivateUser(trim($this->uri->segment(3)))) {
				$this->theme->view('user/activated');
			} else {
				$this->theme->view('user/notactivated');
			}
		} else {
			redirect("","");
		}
	}
	
	/**
		* This function will send an recommendation email
		* to the emailadress in the inputfield.
		* 
		*/
	function RecommendNewUser()
	{
        // add a tracemessage to log
        log_message('debug','#### => Controller Account->RecommendNewUser');
        
		if($this->user->IsLoggedIn() === false) {
			redirect("","");
		}
		
		/*
		* Rules for the inputfields
		*/
		$rules = array(
			"recEmail" => "trim|required|xss_clean|valid_email"
		);
		$this->validation->set_rules($rules);
		
		/*
		* Human names for the inputfields
		*/
		$field = array(
			"recEmail" => "Email"
		);
		$this->validation->set_fields($field);
		
		$status = $this->validation->run();
		
		$data = array();
		
		if($status) {
			$insert = array(
				"recEmail" => $this->validation->recEmail
			);
			
			// Gets the autherized userinformation
			$user = $this->user->getLoggedInUser();
			$name = $user['Firstname'] . " " . $user['Lastname'];
			
			// Sends an activationemail
			if($this->emailsender->SendRecommendationMail($name, $insert['recEmail'])) {
				$data = array(
					"status" => "ok",
					"status_message" => "The recommendation was sent!"
				);
			}
            else {
                // failed
                $status = false;
            }
		}
		
		if($status == false && isset($_POST['recSubmit'])) {
			$data = array(
				"recEmail" => $this->validation->recEmail,
				"status" => "error",
				"status_message" => "Failed to send!"
			);
		}
		
		$this->theme->view('user/recommend', $data);
	}
	
	/**
		* This function will open the possability for the user
		* to edit his account-information
		*/
	function Edit() {
		if($this->user->IsLoggedIn() === false) {
			redirect("","");
		}
		
		$this->project->clearCurrentProject();
		
		$user = $this->user->getLoggedInUser();
		
		$data = array();
		
		// Checks wich submit button that has been clicked
		if($this->input->post('edit_info_btn')) {
			/*
			* Rules for the inputfields
			*/
			$rules = array(
				"old_password" => "trim|required|xss_clean|callback_password_check",
				"firstname" => "trim|required|max_length[100]|alpha|xss_clean",
				"lastname" => "trim|required|max_length[100]|alpha|xss_clean",
				"streetadress" => "trim|max_length[100]|xss_clean",
				"postalcode" => "trim|max_length[5]|integer",
				"hometown" => "trim|max_length[130]|xss_clean"
			);
			
			$this->validation->set_rules($rules);
			
			/*
			* Human names for the inputfields
			*/
			$field = array(
				"old_password" =>"Old password",
				"firstname" => "Firstname",
				"lastname" => "Lastname",
				"streetadress" => "Streetadress",
				"postalcode" => "Postalcode",
				"hometown" => "Hometown"
			);
			
			$this->validation->set_fields($field);
			
			$status = $this->validation->run();
			
			if($status) {
				$update = array(
					"User_id" => $user['User_id'],
					"Firstname" => $this->validation->firstname,
					"Lastname" => $this->validation->lastname,
					"Email" => $user['Email'],
					"Username" => $user['Username'],
					"Password" => $user['Password'],
					"Streetadress" => $this->validation->streetadress,
					"Postalcode" => $this->validation->postalcode,
					"Hometown" => $this->validation->hometown
				);
			}
			
		} else if($this->input->post('edit_pass_btn')) {
			/*
			* Rules for the inputfields
			*/
			$rules = array(
				"old_password" => "trim|required|xss_clean|callback_password_check",
				"new_password" => "trim|required|min_length[6]|max_length[32]|xss_clean",
				"new_again_password" => "trim|required|min_length[6]|max_length[32]|xss_clean|matches[new_password]"
			);
			
			$this->validation->set_rules($rules);
			
			/*
			* Human names for the inputfields
			*/
			$field = array(
				"old_password" =>"Old password",
				"new_password" => "New password",
				"new_again_password" => "New password again"
			);
			
			$this->validation->set_fields($field);
			
			$status = $this->validation->run();
			
			if($status) {
				$update = array(
					"User_id" => $user['User_id'],
					"Firstname" => $user['Firstname'],
					"Lastname" => $user['Lastname'],
					"Email" => $user['Email'],
					"Username" => $user['Username'],
					"Password" => $this->user->TransformPassword($this->validation->new_password),
					"Streetadress" => $user['Streetadress'],
					"Postalcode" => $user['Postalcode'],
					"Hometown" => $user['Hometown']
				);
			}
		}
		if(isset($status)) {
			if($status) {
				if($this->user->updateUser($update)) {
					// all ok
					$data = array(
							"status" => "ok",
							"status_message" => "Your information has been updated!"
					);
					$user = $this->user->getLoggedInUser();
				} else {
					// update failed
					$data = array(
							"status" => "error",
							"status_message" => "An error occured!"
					);
					$this->error->log('Failed to update userinformation. Database return false.', $_SERVER['REMOTE_ADDR'], 'account/Edit', 'user/updateUser', $update);
				}
			} else {
				// Validation failed
				$data = array(
						"status" => "error",
						"status_message" => "Please correct the following errors:"
				);
			}
		}
		
		$data = array_merge($user, $data);
		
		$this->theme->view('user/edit', $data);
	}
}

/* End of file user_controller.php */
/* Location: ./system/application/controllers/user_controller.php */