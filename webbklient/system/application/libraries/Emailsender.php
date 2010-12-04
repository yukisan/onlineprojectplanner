<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* This class handles all functions that will send 
* autogenerated emails
*
* @link https://code.google.com/p/onlineprojectplanner/
*/
class Emailsender
{ 
	private $_CI = null;
    private $_last_error = "";
    
	function __construct()
	{
		// get CI instance
		$this->_CI = & get_instance();
		
		// Load the email library
		$this->_CI->load->library('email');
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
	* Function: SendActivationMail
	* This function will send a activation-email
	* 
	* @param string $name
	* @param string $email
	* @param string $code
	* @return bool
	*/
	function SendActivationMail($name, $email, $code)
	{
        // fetch settings from config
        $system_email = $this->_CI->config->item('system_email', 'webclient');
        $system_email_name = $this->_CI->config->item('system_email_name', 'webclient');
        $email_template = $this->_CI->config->item('activation_template', 'webclient');  
        $email_subject = $this->_CI->config->item('activation_template_subject', 'webclient');
        $activation_url =  $this->_CI->config->item('activation_url', 'webclient');
        
        // insert data
        $url = site_url();
        $activation_url = site_url($activation_url)."/$code";
        $email_subject = sprintf($email_subject, $name);
        $email_template = sprintf($email_template, $name, $url, $activation_url, $activation_url);
        
        // setup CI email library
        $this->_CI->email->from($system_email, $system_email_name);
		$this->_CI->email->to($email); 

		$this->_CI->email->subject($email_subject);
		$this->_CI->email->message($email_template);

		return $this->_CI->email->send();
	}
	
	/**
	* Function: SendRecommendationMail
	* This function will send a recommendation mail
	* 
	* @param string $senderName
	* @param string $email
	* @return bool
	*/
	function SendRecommendationMail($senderName, $email)
	{
        // fetch settings from config
        $system_email = $this->_CI->config->item('system_email', 'webclient');
        $system_email_name = $this->_CI->config->item('system_email_name', 'webclient');
        $email_template = $this->_CI->config->item('recommendation_template', 'webclient');  
        $email_subject = $this->_CI->config->item('recommendation_template_subject', 'webclient');  
        
        // insert data
        $url = site_url();
        $email_subject = sprintf($email_subject, $senderName);
        $email_template = sprintf($email_template, $senderName, $url, $url);
        
        // setup CI email library
		$this->_CI->email->from($system_email, $system_email_name);
		$this->_CI->email->to($email); 

		$this->_CI->email->subject($email_subject);
		$this->_CI->email->message($email_template);

        // send
		return $this->_CI->email->send();
	}
    
    
    
    /**
    * This will send the reset password email
    * with the initial confirmation code.
    * 
    * @param string $name
    * @param string $email
    * @param int $code
    * $param int $uid
    * @return bool
    */
    function SendResetPasswordMail($name, $email, $code, $uid)
    {
        
        // prepare email to send
        $system_email = $this->_CI->config->item('system_email', 'webclient');
        $system_email_name = $this->_CI->config->item('system_email_name', 'webclient');
        $email_template = $this->_CI->config->item('reset_password_template', 'webclient');
        $confirm_url = $this->_CI->config->item('confirm_reset_url', 'webclient');
        $subject = $this->_CI->config->item('reset_password_template_subject', 'webclient');
        
        // insert data 
        $confirm_url = site_url($confirm_url)."/$uid/$code";
        $email_template = sprintf($email_template, $name, $confirm_url, $system_email_name);

        // setup CI email library
        $this->_CI->email->from($system_email, $system_email_name);
        $this->_CI->email->to($email); 
        $this->_CI->email->subject($subject);
        $this->_CI->email->message($email_template); 
        
        // send
        return $this->_CI->email->send();
    }
    
    
    /**
    * This function will send an email with the newly
    * generated password.
    * 
    * @param string $name
    * @param string $email
    * @param string $new_password
    * @return bool
    */
    function SendNewPasswordEmail($name, $email, $new_password)
    {
        
        // email new password to user
        $system_email = $this->_CI->config->item('system_email', 'webclient');
        $system_email_name = $this->_CI->config->item('system_email_name', 'webclient');
        $email_template = $this->_CI->config->item('new_password_template', 'webclient');
        $subject = $this->_CI->config->item('new_password_template_subject', 'webclient');
        
        // insert data
        $email_template = sprintf($email_template, $name, $new_password, $system_email_name);

        // setup CI email library 
        $this->_CI->email->from($system_email, $system_email_name);
        $this->_CI->email->to($email); 
        $this->_CI->email->subject($subject);
        $this->_CI->email->message($email_template); 
        
        // send
        return $this->_CI->email->send();
    }

    function SendInvitationMail($email, $code)
    {

        // Fetch settings from config

        $system_email = $this->_CI->config->item('system_email', 'webclient');
        $system_email_name = $this->_CI->config->item('system_email_name', 'webclient');
        $email_template = $this->_CI->config->item('invitation_template', 'webclient');
        $email_subject = $this->_CI->config->item('invitation_template_subject', 'webclient');

        // Insert data

        $url = site_url();
        $email_subject = sprintf($email_subject);
        $email_template = sprintf($email_template, $url, $code);

        // Setup CI email library

        $this->_CI->email->from($system_email, $system_email_name);
        $this->_CI->email->to($email);

        $this->_CI->email->subject($email_subject);
        $this->_CI->email->message($email_template);

        // Send

        return $this->_CI->email->send();

    }

}

?>