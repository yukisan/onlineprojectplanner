
<?php
    if(isset($status)) {
        echo "<div class='" . $status . "'><h3>" . $status_message . "</h3>" . $this->validation->error_string . "<p>" . validation_errors() . "</p></div>";
    }
?>
                
<h1 class="contentheader">Project members in "<?php echo (isset($title)) ? $title : ""; ?>"</h1>

<div id="contentboxwrapper">
	<div id="leftbox">
	
	        <h3>Invite a new member</h3>
	
	        <form action="http://localhost/projekt/webbklient/index.php/widget/project_members/pm_controller/save" method="POST">
	            <input type="hidden" name="projectID" value="<?php echo (isset($projectID)) ? $projectID : ""; ?>" />
	            <label for="email">E-mail: </label><input type="text" name="email" value="" />*<br/>
	            <label for="projectRoleID">Role in project: </label>
	            <select name="projectRoleID">
	
	                <?php foreach($roles as $role): ?>
	
	                    <option value="<?php echo($role['Project_role_id']);?>"><?php echo($role['Role']);?></option>
	
	                <?php endforeach; ?>
	
	            </select>
	
	            <br/>
	            <label>&nbsp;</label><input type="submit" value="Invite" name="invite_btn" />
	        </form>
	</div>
	    <div id="rightbox">
	
	    	<?php foreach($members as $member): ?>

                    <?php // $memberInfo = end($member) ?>

		        <div class="projectmemberbox">

                            <?php if($member['IsLoggedInUser'] != false && $isGeneral == false) { ?>
                            <h3><?php echo($member['Username'])." (".$member['Role'].")" ?> [<a href="<?php echo site_url('project/leave/'.$projectID.''); ?>">Leave</a>]</h3>
                            <?php } else { ?>
		            <h3><?php echo($member['Username'])." (".$member['Role'].")"; ?></h3>
                            <?php } ?>
		            <p>Name: <?php echo($member['Firstname']);?></p>
		            <p>Surname: <?php echo($member['Lastname']);?></p>
		            <p>E-mail: <?php echo($member['Email']);?></p>
                            <?php if($member['IsLoggedInUser'] == false && $isGeneral != false) { ?>
                            <p><a href="<?php echo site_url('project/kickout/'.$member['User_id'].'/'.$projectID.''); ?>">Kick out</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo site_url('project/switchgeneral/'.$member['User_id'].'/'.$projectID.''); ?>">Make General</a></p>
                            <?php } ?>
		
		        </div>
	
	    	<?php endforeach; ?>
	
	    </div>

            <br style="clear:both;" />

	</div>
</div>