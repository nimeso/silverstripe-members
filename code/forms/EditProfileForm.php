<?php

class EditProfileForm extends Form{

	protected $member;
	
	public function __construct($controller, $name, Member $member) {
		$this->member = $member;

		$fields = $this->member->getMemberFormFields();

		if($passwordfield = $this->getChangePasswordField()){
			$fields->push($passwordfield);
		}

		$fields->push(new HiddenField('ID','ID',$this->member->ID));
		$fields->removeByName('Password');
		$actions = new FieldList(
			new FormAction('updatedetails','Update')
		);

		//TODO: add validator to check if changed email is taken
		$validator = new RequiredFields(
			'FirstName',
			'Surname',
			'Email'
		);
		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->loadDataFrom($this->member);
		$this->member->extend('updateEditProfileForm',$form);
	}

	public function updatedetails($data, $form) {
		$form->saveInto($this->member);
		if(Member::config()->send_frontend_update_notifications){
			$this->sendUpdateNotification();
		}
		$this->member->write();
		$form->sessionMessage("Your member details have been updated.", "good");
		return $this->controller->redirectBack();
	}

	public function sendUpdateNotification() {
		$name = $data['FirstName']." ".$data['Surname'];
		$body = "$name has updated their details via the website. Here is the new information:<br/>";
		foreach($this->member->getAllFields() as $key => $field){
			if(isset($data[$key])){
				$body .= "<br/>$key: ".$data[$key];
				$body .= ($field != $data[$key])? "  <span style='color:red;'>(changed)</span>" : "";
			}
		}
		$email = new Email(
			Email::getAdminEmail(),
			Email::getAdminEmail(),
			"Member details update: $name",
			$body
		);
		$email->send();
	}

	protected function getChangePasswordField(){
		if($this->member->ID != Member::currentUserID()){
			return;
		}
		return new LiteralField('ChangePasswordLink', 
			'<div class="field"><p>
					<a href="Security/changepassword">change password</a>
				</p>
			</div>'
		);
	}

}