<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_MonthlypayrollController extends Zend_Controller_Action
{

	private $_options;
	public function preDispatch()
	{
		$session = sapp_Global::_readSession();
		if(!isset($session))
		{
			if($this->getRequest()->isXmlHttpRequest())
			{
				echo Zend_Json::encode( array('login' => 'failed') );
				die();
			}
			else
			{
				$this->_redirect('');
			}
		}
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('getdepartments', 'json')->initContext();
		$ajaxContext->addActionContext('getpositions', 'json')->initContext();

	}

	public function init()
	{
		$this->_options= $this->getInvokeArg('bootstrap')->getOptions();
	}

	public function indexAction()
	{

        $monthly_model = new Default_Model_Monthlypayroll();

        $call = $this->_getParam('call');
        if($call == 'ajaxcall')
            $this->_helper->layout->disableLayout();

        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity()){
            $loginUserId = $auth->getStorage()->read()->id;
            $loginuserGroup = $auth->getStorage()->read()->group_id;
        }


        $statusidstring =$this->_request->getParam('con');
        $refresh = $this->_getParam('refresh');
        $data = array();
        $searchQuery = '';
        $searchArray = array();
        $dashboardcall = $this->_getParam('dashboardcall');
        $tablecontent='';

        	$statusid =  sapp_Global::_decrypt($statusidstring);
		       if($statusid !='' && is_numeric($statusid))
               {
                   if($statusid == 1)
                       $req_type =1;
                   else if($statusid == 2)
                       $req_type = 2;
                   else if($statusid == 3)
                       $req_type = 3;
                   else
                       $req_type = 1;
               }
               else
               {
                   $req_type = 1;
               }

        if($refresh == 'refresh')
        {
            if($dashboardcall == 'Yes')
                $perPage = DASHBOARD_PERPAGE;
            else
                $perPage = PERPAGE;
            $sort = 'ASC';$by = 'r.id';$pageNo = 1;$searchData = '';$searchQuery = '';
            $searchArray = array();
        }
        else
        {
            $sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'ASC';
            $by = ($this->_getParam('by')!='')? $this->_getParam('by'):'r.id';
            if($dashboardcall == 'Yes')
                $perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
            else
                $perPage = $this->_getParam('per_page',PERPAGE);
            $pageNo = $this->_getParam('page', 1);
            $searchData = $this->_getParam('searchData');
        }

        // Monthly payroll Term Calc

        $term = $this->_getParam('term');
        $nowDate = new Zend_date();

        $term = $term?$term:$nowDate->get('YYYY-MM-dd');
        $this->view->term = $term;




        $dataTmp = $monthly_model->getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$loginUserId,$loginuserGroup,$req_type,'monthlypayroll',$dashboardcall);

        // Each Department Employees Count
        $opdepartments = $monthly_model->getDepartmentCount(1);
        $opdepartmentcount = $opdepartments['count'];
        $this->view->op_count = $opdepartmentcount;

        $nonopdepartments = $monthly_model->getDepartmentCount(2);
        $nonopdepartmentcount = $nonopdepartments['count'];
        $this->view->nonop_count = $nonopdepartmentcount;

        $admdepartments = $monthly_model->getDepartmentCount(3);
        $admdepartmentcount = $admdepartments['count'];
        $this->view->adm_count = $admdepartmentcount;

        $this->view->statusidstring = $statusidstring;

        array_push($data,$dataTmp);
        $this->view->dataArray = $dataTmp;
        $this->view->req_type=$req_type;
        $this->view->call = $call;
        $this->view->message = "this is monthly payroll page";
        $this->view->messages = $this->_helper->flashMessenger->getMessages();

	}

	public function addAction()
	{
		$auth = Zend_Auth::getInstance();
		$data = array();

		if($auth->hasIdentity())
		{
			$sess_vals = $auth->getStorage()->read();
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}

		$job_title_permission = sapp_Global::_checkprivileges(JOBTITLES,$loginuserGroup,$loginuserRole,'add');
		$positions_permission = sapp_Global::_checkprivileges(POSITIONS,$loginuserGroup,$loginuserRole,'add');
		$emp_status_permission = sapp_Global::_checkprivileges(EMPLOYMENTSTATUS,$loginuserGroup,$loginuserRole,'add');

		$form = new Default_Form_Monthlypayroll();
		$monthlypayroll_model = new Default_Model_Monthlypayroll();
		$user_model = new Default_Model_Usermanagement();

        $norec_arr = array();

        $elements = $form->getElements();

        $this->view->form = $form;
        $this->view->loginuserGroup = $loginuserGroup;
        $this->view->data = $data;

        // To check whether to display Employment Status configuration link or not
//        $employmentstatusmodel = new Default_Model_Employmentstatus();
//        $activeEmploymentStatusArr =  $employmentstatusmodel->getEmploymentStatuslist();
//        $empstatusstr = '';
//        if(!empty($activeEmploymentStatusArr))
//        {
//            for($i=0;$i<sizeof($activeEmploymentStatusArr);$i++)
//            {
//                $newarr1[] = $activeEmploymentStatusArr[$i]['workcodename'];
//            }
//            $empstatusstr = implode(",",$newarr1);
//        }

        $norec_arr['department'] = "Departments are not added yet.";

        $this->view->messages = $norec_arr;


        if($this->getRequest()->getPost())
        {
            $result = $this->save($form,array());
            $this->view->msgarray = $result;
            $this->view->messages = $result;
        }
	}

	/**
	 * This action is used for adding/updating data.
	 * @parameters
	 * @param $id  =  id of requisition.
	 *
	 * @return Zend_Form.
	 */
	public function editAction()
	{
	    $id = $this->getRequest()->getParam('id',null);
		$auth = Zend_Auth::getInstance();
		$data = array();

		if($auth->hasIdentity())
		{
			$sess_vals = $auth->getStorage()->read();
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}


		$form = new Default_Form_Monthlypayroll();
		$monthlypayroll_model = new Default_Model_Monthlypayroll();
		$usersModel = new Default_Model_Users();
		$user_model = new Default_Model_Usermanagement();

		$form->setAttrib('action',BASE_URL.'monthlypayroll/edit/id/'.$id);
		$form->submit->setLabel('Update');

		$edit_flag = '';
		$edit_order = '';

		try {
            if ($id > 0 && is_numeric($id)) {
                $id = abs($id);

                $data = $monthlypayroll_model->getMonthlypayrollForEdit($id);

                if (!empty($data)) {

                    /*
                    if($aflag == 'approver' && $data['appstatus'.$aorder] == 'Initiated')
                    {
                        $data['onboard_date'] = sapp_Global::change_date($data['onboard_date'], 'view');
                        $business_units_list = $requi_model->getBusinessUnitsList();
                        $data['bunit_name'] = $business_units_list[$data['businessunit_id']];

                        $departments_list = $requi_model->getDepartmentList($data['businessunit_id']);
                        $data['dept_name'] = $departments_list[$data['department_id']];

                        $job_data = $requi_model->getJobTitleList();
                        if(isset($job_data[$data['jobtitle']])) { $data['jtitle_name'] = $job_data[$data['jobtitle']]; }
                        else $data['jtitle_name'] = 'Select Job Title';

                        $pos_data = $requi_model->getPositionOptions($data['jobtitle']);
                        $data['position_name'] = $pos_data[$data['position_id']];

                        $emptype_options = $requi_model->getStatusOptionsForRequi();
                        if(isset($emptype_options[$data['emp_type']])) { $data['emptype_name'] = $emptype_options[$data['emp_type']]; }
                        else $data['emptype_name'] = 'Select Employment Status';

                        $report_manager_data = $user_model->getUserDataById($data['reporting_id']);

                        $data['mngrname'] = $report_manager_data['userfullname'];
                        $form->req_status->addMultiOptions(array(
                            '0'		=>		'Select status',
                            '2'		=>		'Approved',
                            '3'		=> 		'Rejected'
                            ));
                            $form->req_status->setRequired(true)->addErrorMessage('Please select the status.');
                            $form->req_status->addFilter('Int')->addValidator('NotEmpty',true, array('integer','zero'));

                            $elements = $form->getElements();
                            if(count($elements)>0)
                            {
                                foreach($elements as $key=>$element)
                                {
                                    if($key!='req_status')
                                    $element->setRequired(false);
                                }
                            }

                            if($data['approver1'] != '')
                            {
                                $app1_opt = $user_model->getUserDataById($data['approver1']);
                                $data_m['approver1'] = $app1_opt['userfullname'];
                            }
                            if($data['approver2'] != '')
                            {
                                $app2_opt = $user_model->getUserDataById($data['approver2']);
                                $data_m['approver2'] = $app2_opt['userfullname'];
                            }
                            if($data['approver3'] != '')
                            {
                                $app3_opt = $user_model->getUserDataById($data['approver3']);
                                $data_m['approver3'] = $app3_opt['userfullname'];
                            }
                        $form->recruiters->setAttrib("disabled", "disabled");

                    }
                    else
                    {
                        $edit_flag = 'no';
                        if(in_array('Approved', array($data['appstatus1'],$data['appstatus2'],$data['appstatus3'])))
                        {
                            $edit_flag = 'yes';
                        }
                        if($edit_flag == 'yes')
                        {
                            $form->recruiters->setAttrib("disabled", "disabled");
                            $data['onboard_date'] = sapp_Global::change_date($data['onboard_date'], 'view');
                            $business_units_list = $requi_model->getBusinessUnitsList();
                            $data['bunit_name'] = $business_units_list[$data['businessunit_id']];

                            $departments_list = $requi_model->getDepartmentList($data['businessunit_id']);
                            $data['dept_name'] = $departments_list[$data['department_id']];

                            $job_data = $requi_model->getJobTitleList();
                            if(isset($job_data[$data['jobtitle']])) { $data['jtitle_name'] = $job_data[$data['jobtitle']]; }
                            else $data['jtitle_name'] = 'Select Job Title';

                            $pos_data = $requi_model->getPositionOptions($data['jobtitle']);
                            $data['position_name'] = $pos_data[$data['position_id']];

                            $emptype_options = $requi_model->getStatusOptionsForRequi();
                            if(isset($emptype_options[$data['emp_type']])) { $data['emptype_name'] = $emptype_options[$data['emp_type']]; }
                            else $data['emptype_name'] = 'Select Employment Status';

                            $report_manager_data = $user_model->getUserDataById($data['reporting_id']);
                            $data['mngrname'] = $report_manager_data['userfullname'];

                            $elements = $form->getElements();
                            if(count($elements)>0)
                            {
                                foreach($elements as $key=>$element)
                                {
                                    $element->setRequired(false);
                                }
                            }
                            if($data['appstatus1'] == 'Approved')
                            {
                                $edit_order = 1;
                                $app1_opt = $user_model->getUserDataById($data['approver1']);
                                $data_m['approver1'] = $app1_opt['userfullname'];

                                $report_manager_options = $requi_model->getapprovers($data['reporting_id'], $data['department_id']);
                                $app2_options = array();
                                foreach($report_manager_options as $app1)
                                {
                                    if($app1['id'] != $loginUserId && $app1['id'] != $data['approver1'])
                                    $approver2_opt[] = array('id'=>$app1['id'],'name'=>ucwords($app1['name']),'profileimg'=>$app1['profileimg']);
                                }

                                $form->setDefault('approver1',$data['approver1']);

                                $form->approver2->clearMultiOptions();
                                $form->setDefault('approver2',$data['approver2']);

                                if($data['approver2'] != '')
                                {
                                    $app3_options = array();
                                    foreach($report_manager_options as $app1)
                                    {
                                        if($app1['id'] != $loginUserId && $app1['id'] != $data['approver1'] && $app1['id'] != $data['approver2'])
                                        $approver3_opt[] = array('id'=>$app1['id'],'name'=>ucwords($app1['name']),'profileimg'=>$app1['profileimg']);
                                    }
                                    $form->approver3->clearMultiOptions();
                                    $form->setDefault('approver3',$data['approver3']);
                                }

                            }
                            if($data['appstatus2'] == 'Approved')
                            {
                                $edit_order = 2;
                                $app2_opt = $user_model->getUserDataById($data['approver2']);
                                $data_m['approver2'] = $app2_opt['userfullname'];

                                $report_manager_options = $requi_model->getapprovers($data['reporting_id'], $data['department_id']);

                                $app3_options = array();
                                foreach($report_manager_options as $app1)
                                {
                                    if($app1['id'] != $loginUserId && $app1['id'] != $data['approver1'] && $app1['id'] != $data['approver2'])
                                    $app3_options[$app1['id']] = ucwords($app1['name']);
                                }

                                $form->approver3->clearMultiOptions();
                                $form->approver3->addMultiOptions(array(''=>'Select Approver -3')+$app3_options);

                                $form->setDefault('approver3',$data['approver3']);
                            }
                            if($data['appstatus3'] == 'Approved')
                            {
                                $edit_order = 3;
                                $app3_opt = $user_model->getUserDataById($data['approver3']);
                                $data_m['approver3'] = $app3_opt['userfullname'];
                            }


                        }
                        else
                        {
                            $business_units_list = $requi_model->getBusinessUnitsList();
                            $form->business_unit->addMultiOptions(array(''=>'Select Business Unit')+$business_units_list);
                            $form->setDefault('business_unit',$data['businessunit_id']);

                            $departments_list = $requi_model->getDepartmentList($data['businessunit_id']);
                            $form->department->addMultiOptions(array(''=>'Select Department')+$departments_list);
                            $form->setDefault('department',$data['department_id']);

                            $job_data = $requi_model->getJobTitleList();
                            $form->jobtitle->addMultiOptions(array(''=>'Select Job Title')+$job_data);
                            $form->setDefault('jobtitle',$data['jobtitle']);

                            $pos_data = $requi_model->getPositionOptions($data['jobtitle']);
                            $form->position_id->addMultiOptions(array(''=>'Select Position')+$pos_data);
                            $form->setDefault('position_id',$data['position_id']);

                            $emptype_options = $requi_model->getStatusOptionsForRequi();
                            $form->emp_type->addMultiOptions(array(''=>'Select Employment Status')+$emptype_options);
                            $form->requisition_code->setValue($data['requisition_code']);
                            $form->setDefault('emp_type',$data['emp_type']);



                            $form->req_status->addMultiOptions(array(
                                                    '1' => 'Initiated'
                                                    ));

                                                    if($loginuserGroup == HR_GROUP || $loginuserGroup == '' || $loginuserGroup == MANAGEMENT_GROUP)
                                                    {
                                                        if($loginuserGroup == '')
                                                        $reportingManagerData = $requi_model->getReportingmanagers('',$loginUserId,'',$data['department_id'],'requisition');
                                                        else
                                                            $reportingManagerData = $requi_model->getReportingmanagers('','','',$data['department_id'],'requisition');

                                                        if(isset($_POST['business_unit']) && $_POST['business_unit']!='')
                                                        {
                                                            $departments_list = $requi_model->getDepartmentList($_POST['business_unit']);
                                                            $form->department->addMultiOptions(array(''=>'Select Department')+$departments_list);
                                                        }
                                                        if((isset($_POST['department']) && $_POST['department']!=''))
                                                        {

                                                            $reportingManagerData = $requi_model->getReportingmanagers('',$loginUserId,'',$_POST['department'],'requisition');

                                                        }

                                                        $form->setDefault('reporting_id',$data['reporting_id']);
                                                        $form->setDefault('req_status',$data['req_status']);
                                                        $form->req_status->setAttrib("disabled", "disabled");
                                                         if($loginuserGroup == HR_GROUP)
                                                        {
                                                            $departments_list = $requi_model->getDepartmentList($sess_vals->businessunit_id);
                                                            $data_m['bunit_data']['id'] = $sess_vals->businessunit_id;
                                                            $data_m['bunit_data']['name'] = $business_units_list[$sess_vals->businessunit_id];
                                                            $form->department->addMultiOptions(array(''=>'Select Department')+$departments_list);
                                                        }
                                                    }
                                                    else //for managers login
                                                    {
                                                        $report_manager_options = $user_model->getUserDataById($data['reporting_id']);
                                                        $departments_list = $requi_model->getDepartmentList($data['businessunit_id']);
                                                        $data_m['manager_data']['id'] = $data['reporting_id'];
                                                        $data_m['manager_data']['name'] = $report_manager_options['userfullname'];
                                                        $data_m['bunit_data']['id'] = $data['businessunit_id'];
                                                        $data_m['bunit_data']['name'] = $business_units_list[$data['businessunit_id']];
                                                        $data_m['dept_data']['id'] = $data['department_id'];
                                                        $data_m['dept_data']['name'] = $departments_list[$data['department_id']];

                                                        $form->setDefault('req_status',$data['req_status']);
                                                        $form->req_status->setAttrib("disabled", "disabled");
                                                    }
                                                    //start of approvers options
                                                    $approver_opt = $requi_model->getapprovers($data['reporting_id'], $data['department_id']);
                                                    $app1_opt = array();
                                                    $app2_opt = array();
                                                    $app3_opt = array();
                                                    if(count($approver_opt) > 0 && count($_POST) == 0)
                                                    {
                                                        foreach($approver_opt as $app1)
                                                        {
                                                            if($loginUserId !=$app1['id'])
                                                                $approver1_opt[] = array('id'=>$app1['id'],'name'=>ucwords($app1['name']),'profileimg'=>$app1['profileimg']);
                                                        }
                                                        foreach($approver_opt as $app1)
                                                        {
                                                            if($loginUserId !=$app1['id'])
                                                            {
                                                            if($app1['id'] != $data['approver1'])
                                                                $approver2_opt[] = array('id'=>$app1['id'],'name'=>ucwords($app1['name']),'profileimg'=>$app1['profileimg']);
                                                             }
                                                        }
                                                        foreach($approver_opt as $app1)
                                                        {
                                                            if($loginUserId !=$app1['id'])
                                                            {
                                                            if($app1['id'] != $data['approver1'] && $app1['id'] != $data['approver2'])
                                                                $approver3_opt[] = array('id'=>$app1['id'],'name'=>ucwords($app1['name']),'profileimg'=>$app1['profileimg']);
                                                             }
                                                        }
                                                        if($data['approver2'] == '')
                                                            $approver3_opt = array();
                                                    }

                                                    //end of approvers options
                                                    foreach($data as $key=>$val)
                                                    {
                                                        $data[$key] = htmlentities(addslashes($val), ENT_QUOTES, "UTF-8");
                                                    }
                                                    $data['onboard_date'] = sapp_Global::change_date($data['onboard_date'], 'view');
                                                    $form->populate($data);
                                                    if(isset($_POST['business_unit']) && $_POST['business_unit']!='')
                                                    {
                                                        $departments_list = $requi_model->getDepartmentList($_POST['business_unit']);
                                                        $form->department->clearMultiOptions();
                                                        $form->department->addMultiOptions(array(''=>'Select Department')+$departments_list);
                                                    }
                                                    if(isset($_POST['jobtitle']) && $_POST['jobtitle']!='')
                                                    {
                                                        $pos_data = $requi_model->getPositionOptions($_POST['jobtitle']);
                                                        $form->position_id->clearMultiOptions();
                                                        $form->position_id->addMultiOptions(array(''=>'Select Position')+$pos_data);
                                                    }
                                                    if(isset($_POST['reporting_id']) && $_POST['reporting_id'] != '')
                                                    {
                                                        $app1_data = $requi_model->getapprovers($_POST['reporting_id'], $_POST['department']);
                                                        $app1_opt = array();
                                                        if(count($app1_data) > 0)
                                                        {
                                                            foreach($app1_data as $app1)
                                                            {
                                                                $app1_opt[$app1['id']] = ucwords($app1['name']);
                                                                $approver1_opt[] = array('id'=>$app1['id'],'name'=>ucwords($app1['name']),'profileimg'=>$app1['profileimg']);
                                                            }
                                                            $form->reporting_id->setValue($_POST['reporting_id']);
                                                        }
                                                    }
                                                    if(isset($_POST['approver1']) && $_POST['approver1'] != '')
                                                    {
                                                        $app1_data = $requi_model->getapprovers($_POST['reporting_id'], $_POST['department']);
                                                        $app1_opt = array();
                                                        if(count($app1_data) > 0)
                                                        {
                                                            foreach($app1_data as $app1)
                                                            {
                                                                if($app1['id'] != $_POST['approver1'])
                                                                $approver2_opt[] = array('id'=>$app1['id'],'name'=>ucwords($app1['name']),'profileimg'=>$app1['profileimg']);
                                                            }
                                                            $form->approver1->setValue($_POST['approver1']);
                                                        }
                                                    }
                                                    if(isset($_POST['approver2']) && $_POST['approver2'] != '')
                                                    {
                                                        $app1_data = $requi_model->getapprovers($_POST['reporting_id'], $_POST['department']);
                                                        $app1_opt = array();
                                                        if(count($app1_data) > 0)
                                                        {
                                                            foreach($app1_data as $app1)
                                                            {
                                                                if($app1['id'] != $_POST['approver1'] && $app1['id'] != $_POST['approver2'])
                                                                $approver3_opt[] = array('id'=>$app1['id'],'name'=>ucwords($app1['name']),'profileimg'=>$app1['profileimg']);
                                                            }
                                                            $form->approver2->setValue($_POST['approver2']);
                                                        }
                                                    }
                                                    if(isset($_POST['approver3']) && $_POST['approver3'] != '')
                                                    {
                                                        $form->approver3->setValue($_POST['approver3']);
                                                    }
                        }
                    }//end of else of aflag.
                    */
                    // date format convert

                    $data['contract_date'] = sapp_Global::change_date($data['contract_date'], 'view');
                    $data['starting_date'] = sapp_Global::change_date($data['starting_date'], 'view');

                    $form->setDefault('comments',$data['comments']);
                    $form->setDefault('sick_leavedays',$data['sick_leavedays']);
                    $form->setDefault('standby_hours',$data['standby_hours']);
                    $form->setDefault('overtime_hours',$data['overtime_hours']);
                    $form->setDefault('addition_rollposition',$data['addition_rollposition']);
                    $form->setDefault('annual_leavedays',$data['annual_leavedays']);
                    $form->setDefault('weekend_nationaldays',$data['weekend_nationaldays']);
                    $form->setDefault('daily_allowance',$data['daily_allowance']);
                    $form->setDefault('deductadd_salary',$data['deductadd_salary']);
                    $form->setDefault('gross_salary',$data['gross_salary']);
                    $form->setDefault('work_days',$data['work_days']);
                    $form->setDefault('monthlygross_salary',$data['monthlygross_salary']);
                    $form->setDefault('contribution_salary',$data['contribution_salary']);
                    $form->setDefault('employeesocial_insurance',$data['employeesocial_insurance']);
                    $form->setDefault('employeehealth_insurance',$data['employeehealth_insurance']);
                    $form->setDefault('employeetotal_insurance',$data['employeetotal_insurance']);
                    $form->setDefault('employersocial_insurance',$data['employersocial_insurance']);
                    $form->setDefault('employerhealth_insurance',$data['employerhealth_insurance']);
                    $form->setDefault('employertotal_insurance',$data['employertotal_insurance']);
                    $form->setDefault('whtaxpaided_salary',$data['whtaxpaided_salary']);
                    $form->setDefault('progresive_whtax',$data['progresive_whtax']);
                    $form->setDefault('bankpaid_salary',$data['bankpaid_salary']);
                    $form->setDefault('whtax_salary',$data['whtax_salary']);



                    $this->view->loginuserGroup = $loginuserGroup;
                    $this->view->form = $form;
                    $this->view->data = $data;

                    $this->view->edit_flag = $edit_flag;
                    $this->view->edit_order = $edit_order;

                    if ($this->getRequest()->getPost()) {
                        $result = $this->save($form, $data);
                        $this->view->msgarray = $result;
                        $this->view->messages = $result;
                    }
                    $this->view->ermsg = '';

                } else {
                    $this->view->nodata = 'nodata';
                }
            } else {
                $this->view->nodata = 'nodata';
            }
        }
		catch(Exception $e)
		{
			$this->view->nodata = 'nodata';
		}
	}

	public function save($monthlypayrollform,$data)
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$monthlypayroll_model = new Default_Model_Monthlypayroll();
		$user_model = new Default_Model_Usermanagement();

		$appr_mail = '';$appr_per = '';


		if($monthlypayrollform->isValid($this->_request->getPost()))
		{
			$trDb = Zend_Db_Table::getDefaultAdapter();
			// starting transaction
			$trDb->beginTransaction();
			try
			{
				$id = (int)$this->_getParam('id',null);
				$employee_id = $this->_getParam('employee_id',null);
                $employee_name = $this->_getParam('employee_name',null);
				$department = $this->_getParam('department',null);
				$starting_date = $this->_getParam('starting_date',null);
				$comments = $this->_getParam('comments',null);
				$sick_leavedays = $this->_getParam('sick_leavedays',null);
				$standby_hours = $this->_getParam('standby_hours',null);
                $overtime_hours = $this->_getParam('overtime_hours',null);
                $addition_rollposition = $this->_getParam('addition_rollposition',null);
                $annual_leavedays = $this->_getParam('annual_leavedays',null);
                $weekend_nationaldays = $this->_getParam('weekend_nationaldays',null);
                $contract_date = $this->_getParam('contract_date',null);
                $daily_allowance = $this->_getParam('daily_allowance',null);
                $grossbase_salary = $this->_getParam('grossbase_salary',null);
                $deductadd_salary = $this->_getParam('deductadd_salary',null);
                $gross_salary = $this->_getParam('gross_salary',null);
                $work_days	 = $this->_getParam('work_days',null);
                $monthlygross_salary = $this->_getParam('monthlygross_salary',null);
                $contribution_salary = $this->_getParam('contribution_salary',null);
                $employeesocial_insurance = $this->_getParam('employeesocial_insurance',null);
                $employeehealth_insurance= $this->_getParam('employeehealth_insurance',null);
                $employeetotal_insurance= $this->_getParam('employeetotal_insurance',null);
                $employersocial_insurance = $this->_getParam('employersocial_insurance',null);
                $employerhealth_insurance = $this->_getParam('employerhealth_insurance',null);
                $employertotal_insurance = $this->_getParam('employertotal_insurance',null);
                $whtaxpaided_salary = $this->_getParam('whtaxpaided_salary',null);
                $progresive_whtax = $this->_getParam('progresive_whtax',null);
                $bankpaid_salary = $this->_getParam('bankpaid_salary',null);
                $whtax_salary = $this->_getParam('whtax_salary',null);
                $edit_flag = $this->_getParam('edit_flag',null);


                $data = array(
                    'employee_id' 	    =>	trim($employee_id),
                    'employee_name' 	=>	trim($employee_name),
                    'department' 		=>	trim($department),
                    'starting_date'	    =>	sapp_Global::change_date(trim($starting_date),'database'),
                    'comments'		    =>	trim($comments),
                    'sick_leavedays'    => 	trim($sick_leavedays),
                    'standby_hours'	    =>	trim($standby_hours),
                    'overtime_hours'	=>	trim($overtime_hours),
                    'addition_rollposition' => 	trim($addition_rollposition),
                    'annual_leavedays'      => 	trim($annual_leavedays),
                    'weekend_nationaldays' 	=> 	trim($weekend_nationaldays),
                    'contract_date' 		=> 	sapp_Global::change_date(trim($contract_date),'database'),
                    'daily_allowance' 		=> 	trim($daily_allowance),
                    'grossbase_salary' 	    => 	trim($grossbase_salary),
                    'deductadd_salary' 	    => 	trim($deductadd_salary),
                    'gross_salary' 	        => 	trim($gross_salary),
                    'work_days' 	        => 	trim($work_days),
                    'monthlygross_salary' 	=> 	trim($monthlygross_salary),
                    'contribution_salary' 	=> 	trim($contribution_salary),
                    'employeesocial_insurance' 	=> 	trim($employeesocial_insurance),
                    'employeehealth_insurance' 	=> 	trim($employeehealth_insurance),
                    'employeetotal_insurance' 	=> 	trim($employeetotal_insurance),
                    'employersocial_insurance' 	=> 	trim($employersocial_insurance),
                    'employerhealth_insurance' 	=> 	trim($employerhealth_insurance),
                    'employertotal_insurance' 	=> 	trim($employertotal_insurance),
                    'whtaxpaided_salary' 	    => 	trim($whtaxpaided_salary),
                    'progresive_whtax' 	        => 	trim($progresive_whtax),
                    'bankpaid_salary' 	        => 	trim($bankpaid_salary),
                    'whtax_salary' 	            => 	trim($whtax_salary),
                    'createdby' 		        => 	trim($loginUserId),
                    'modifiedby' 		        => 	trim($loginUserId),
                    'createdon' 		        => 	gmdate("Y-m-d H:i:s"),
                    'modifiedon' 		        => 	gmdate("Y-m-d H:i:s")

                );
					
                if($edit_flag!='' && $edit_flag == 'yes')
                {
                    $data = array(
                          'modifiedby' => trim($loginUserId),
                          'modifiedon' => gmdate("Y-m-d H:i:s"),
                    );
                }

				$where = "";
				if($id != '')
				{
					unset($data['createdby']);
					unset($data['createdon']);
					$where = "id = ".$id;

				}

				$result = $monthlypayroll_model->SaveorUpdateMonthlypayrollData($data, $where);


                if($id != '')
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Monthlypayroll updated successfully."));
                else
                $this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Monthlypayroll added successfully."));
                $trDb->commit();
                $this->_redirect('/monthlypayroll');

			}
			catch (Exception $e)
			{
				$trDb->rollBack();

				$this->_helper->getHelper("FlashMessenger")->addMessage(array("error"=>"Something went wrong, please try again later."));
				$this->_redirect('/monthlypayroll');
			}
		}
		else
		{
			$messages = $monthlypayrollform->getMessages();
			
			foreach ($messages as $key => $val)
			{
				foreach($val as $key2 => $val2)
				{
					$msgarray[$key] = $val2;
					break;
				}
			}
			return $msgarray;
		}
	}



	public function viewhrAction()
	{
		$this->view->message = 'This is view resource requisition action page';
	}

	public function approverequisitionAction()
	{
		$req_model = new Default_Model_Requisition();
		$req_data = $req_model->getReqCodes('Initiated');
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
		}
		if(isset($_POST['btnsubmit']))
		{
			$menumodel = new Default_Model_Menu();
			$data = array(
                'req_status' => $_POST['sel_app_status'],
                'modifiedby' => $loginUserId,
                'modifiedon' => gmdate("Y-m-d H:i:s"),
			);
			$where = "id = ".$_POST['sel_req_code'];
			$req_model->SaveorUpdateRequisitionData($data, $where);
			$actionflag = 2;
			$objidArr = $menumodel->getMenuObjID('/roles');
			$objID = $objidArr[0]['id'];
			sapp_Global::logManager($objID,$actionflag,$loginUserId,$_POST['sel_req_code']);
		}

		$this->view->req_data = $req_data;
	}

	public function addcandidateAction()
	{
		$this->view->message = 'This is add candidate page';
	}

	public function interviewAction()
	{
		$this->view->message = 'This is interview page';
	}

	/**
	 * This function is used for ajax call to get departments based on
	 * business unit id.
	 * @parameters
	 * @param {Integer} bunitid  =  id of business unit.
	 *
	 * @return Array of departments in json format.
	 */
	public function getdepartmentsAction()
	{
		$bunit_id = $this->_getParam('bunitid',null);
		$dept_model = new Default_Model_Departments();
			
		$options_data = "";
		$options_data .= sapp_Global::selectOptionBuilder('', 'Select Department');
		if($bunit_id != '')
		{
			$dept_data = $dept_model->getAllDeptsForUnit($bunit_id);
			foreach($dept_data as $dept)
			{
				$options_data .= sapp_Global::selectOptionBuilder($dept['id'], $dept['deptname']);
			}
		}
		$this->_helper->json(array('options'=>$options_data));
	}

	/**
	 * This function is used for ajax call to get positions based on
	 * business unit id and department id.
	 * @parameters
	 * @param {Integer} bunitid  =  id of business unit.
	 *
	 * @return Array of departments in json format.
	 */
	public function getpositionsAction()
	{
		$bunit_id = $this->_getParam('bunitid',null);
		$dept_id = $this->_getParam('dept_id',null);
		$job_id = $this->_getParam('job_id',null);
		$position_model = new Default_Model_Positions();
			
		$options_data = "";
		$options_data .= sapp_Global::selectOptionBuilder('', 'Select Position');
		if($job_id != '')
		{
			$dept_data = $position_model->getPositionOptions($bunit_id,$dept_id,$job_id);
			foreach($dept_data as $dept)
			{
				$options_data .= sapp_Global::selectOptionBuilder($dept['id'], $dept['positionname']);
			}
		}
		$this->_helper->json(array('options'=>$options_data));
	}

	public function viewpopupAction()
	{
		$id = $this->getRequest()->getParam('id');
		$call = $this->getRequest()->getParam('call');
		if($call == 'ajaxcall' ){
			Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		}
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$data = array();$jobtitle = '';
		$requi_model = new Default_Model_Requisition();
		$jobtitleModel = new Default_Model_Jobtitles();
		$user_model = new Default_Model_Usermanagement();
		try{
			
                        $data = $requi_model->getReqDataForView($id);                                        
		}catch(Exception $e){
			$this->view->ermsg = 'nodata';
		}
		if(!empty($data))
		{
                    $data = $data[0];
                    $data['jobtitlename'] = '';
                    $data['businessunit_name'] = $data['businessunit_name'];									
                    $data['dept_name'] = $data['department_name'];									
                    $data['titlename'] = $data['jobtitle_name'];									
                    $data['posname'] = $data['position_name'];									
                    $data['empttype'] = $data['emp_type_name'];						                       
                    $data['mngrname'] = $data['reporting_manager_name'];						
                    $data['raisedby'] = $data['createdby_name'];			                        
                    $data['app1_name'] = $data['approver1_name'];
                        
                    if($data['approver2'] != '')
                    {                        
                        $data['app2_name'] = $data['approver2_name'];
                    }
                    else 
                    {
                        $data['app2_name'] = 'No Approver';
                    }
                        
                    if($data['approver3'] != '')
                    {                        
                        $data['app3_name'] = $data['approver3_name'];
                    }
                    else 
                    {
                        $data['app3_name'] = 'No Approver';
                    }                        
			
                   /*  foreach($data as $key=>$val)
                    {
                        $data[$key] = htmlentities($val, ENT_QUOTES, "UTF-8");
                    }	 */            
                    $data['onboard_date'] = sapp_Global::change_date($data['onboard_date'], 'view');
			$this->view->data = $data;
			$this->view->ermsg = '';
		}else {
			$this->view->ermsg = 'nodata';
		}
			
	}

        /**
     * This action is used for viewing data.
     * @parameters
     * @param id  =  id of requisition
     *
     * @return Zend_Form.
     */
    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id');
        $requi_model = new Default_Model_Requisition();
		$clientsModel = new Default_Model_Clients();
		$usersModel = new Default_Model_Users();
		$auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity())
        {
            $loginUserId = $auth->getStorage()->read()->id;
            $login_group_id = $auth->getStorage()->read()->group_id;
            $login_role_id = $auth->getStorage()->read()->emprole;
        }
		$dataforapprovereject = $requi_model->getRequisitionForEdit($id,$loginUserId);
        $aflag =$dataforapprovereject['aflag'];
	    $aorder = $dataforapprovereject['aorder'];
        $ju_name = array();
        try
        {                        
            
            if(is_numeric($id) && $id >0)
            {
                $id = abs($id);
                $data = $requi_model->getReqDataForView($id);
                $app1_name = $app2_name = $app3_name='';
                if(count($data)>0  && $data[0]['req_status'] == 'Initiated')
                {
                    $data = $data[0];
                    $auth = Zend_Auth::getInstance();
                    if($auth->hasIdentity())
                    {
                        $loginUserId = $auth->getStorage()->read()->id;
                        $loginuserRole = $auth->getStorage()->read()->emprole;
                        $loginuserGroup = $auth->getStorage()->read()->group_id;
                    }
                    												 
                    $data['jobtitlename'] = '';			
                    $data['businessunit_name'] = $data['businessunit_name'];									
                    $data['dept_name'] = $data['department_name'];									
                    $data['titlename'] = $data['jobtitle_name'];									
                    $data['posname'] = $data['position_name'];									
                    $data['empttype'] = $data['emp_type_name'];						                       
                    $data['mngrname'] = $data['reporting_manager_name'];						
                    $data['raisedby'] = $data['createdby_name'];			                        
                    $data['app1_name'] = $data['approver1_name'];
                        
                    if($data['approver2'] != '')
                    {                        
                        $data['app2_name'] = $data['approver2_name'];
                    }
                    else 
                    {
                        $data['app2_name'] = 'No Approver';
                    }
                        
                    if($data['approver3'] != '')
                    {                        
                        $data['app3_name'] = $data['approver3_name'];
                    }
                    else 
                    {
                        $data['app3_name'] = 'No Approver';
                    }    
					if($data['client_id'] != '')
					{
						$clien_data = $clientsModel->getClientDetailsById($data['client_id']);
					    $data['client_id']=$clien_data[0]['client_name'];
					}  
					if($data['recruiters'] != '')
					{
						$name = '';
						$recData=$usersModel->getUserDetailsforView($data['recruiters']);
						if(count($recData)>0)
						{
							foreach($recData as $dataname){
								$name = $name.','.$dataname['name'];
							}

						}
						$data['recruiters']=ltrim($name,',');
					}                    
			
                    /*foreach($data as $key=>$val)
                    {
                        $data[$key] = htmlentities($val, ENT_QUOTES, "UTF-8");
                    }	*/

                    
                    if($data['req_priority'] == 1) {
                    	$data['req_priority']='High';
                    }else if($data['req_priority'] == 2) {
                    	$data['req_priority']='Medium';
                    }else {
                    $data['req_priority']='Low';
                    }
                    $data['onboard_date'] = sapp_Global::change_date($data['onboard_date'], 'view');
                  //to show requisition history in view
                    $reqh_model = new Default_Model_Requisitionhistory();
	                $requisition_history = $reqh_model->getRequisitionHistory($id);
					
                    $previ_data = sapp_Global::_checkprivileges(REQUISITION,$login_group_id,$login_role_id,'edit');

                    $this->view->previ_data = $previ_data;
                    $this->view->data = $data;
                    $this->view->requisition_history = $requisition_history;
                    $this->view->id = $id;
                    $this->view->controllername = "requisition";
                    $this->view->ermsg = '';
					$this->view->aflag = $aflag;
					$this->view->aorder = $aorder;
                }
                else
                {
                    $this->view->nodata = 'nodata';
                }
            }
            else
            {
                $this->view->nodata = 'nodata';
            }
        }
        catch(Exception $e)
        {               
            $this->view->nodata = 'nodata';
        }
    }
    

	/**
	 * This action is used to delete requisition.
	 * @parameters
	 * @param objid    =   id of requisition.
	 *
	 * @return  {String} =   success/failure message
	 */
	public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('objid');
		$deleteflag= $this->_request->getParam('deleteflag');
		$messages['message'] = '';
		$actionflag = 3;
		if($id)
		{
			$requi_model = new Default_Model_Requisition();
			$data = array('isactive'=>0,'modifiedon' => gmdate("Y-m-d H:i:s"));
			$where = array('id=?'=>$id);
			$req_status = $requi_model->getRequisitionForEdit($id, $loginUserId);

			if($req_status['aflag'] == 'creator')
			$Id = $requi_model->SaveorUpdateRequisitionData($data, $where);
			else
			$Id = "not deleted";
			if($Id == 'update')
			{
				$menuID = REQUISITION;
				sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id);
				$messages['message'] = 'Requisition deleted successfully.';
				$messages['msgtype'] = 'success';
				$messages['flagtype']='process';
			}
			else{
				$messages['message'] = 'Requisition cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		}
		else
		{
			$messages['message'] = 'Requisition cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		if($deleteflag==1)
		{
			if(	$messages['msgtype'] == 'error')
			{
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("error"=>$messages['message'],"msgtype"=>$messages['msgtype'] ,'deleteflag'=>$deleteflag));
			}
			if(	$messages['msgtype'] == 'success')
			{
				$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>$messages['message'],"msgtype"=>$messages['msgtype'],'deleteflag'=>$deleteflag));
			}
			
		}
		$this->_helper->json($messages);
	}
	/**
	 * This function gives all data of a particular requisition id.
	 * @parameters
	 * @param {Integer} req_id = id of requisition.
	 *
	 * @return {Json} Json array of all values.
	 */
	public function getapprreqdataAction()
	{
		$req_data = array();
		$req_id = $this->_getParam('req_id',null);
		$requ_model = new Default_Model_Requisition();
		if($req_id != '')
		$req_data = $requ_model->getAppReqById($req_id);
		$this->_helper->json($req_data);
	}
	public function chkreqforcloseAction()
	{
		$req_id = $this->_getParam('req_id',null);
		$requ_model = new Default_Model_Requisition();
		$req_data = $requ_model->getRequisitionDataById($req_id);
		if($req_data['req_no_positions'] == $req_data['filled_positions'])
		$result = 'yes';
		else
		$result = 'no';
		$this->_helper->_json(array('result'=>$result));
	}

	/**
	 * This function is used for ajax call to get reporting managers based on  department
	 * @parameters	department id.
	 * @return Array of managers in json format.
	 */
	public function getempreportingmanagersAction()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('getempreportingmanagers', 'html')->initContext();
		$form = new Default_Form_Requisition();

		$dept_id = $this->_getParam('id',null);

		$requi_model = new Default_Model_Requisition();
		$auth = Zend_Auth::getInstance();

		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		if($dept_id != '')
		{
			if($loginuserGroup == '')
			$reportingManagerData = $requi_model->getReportingmanagers('', $loginUserId, '', $dept_id, 'requisition');
			else
			$reportingManagerData = $requi_model->getReportingmanagers('', '', '', $dept_id, 'requisition');//for hr,management
			if(empty($reportingManagerData))
			{
				$flag = 'true';
			}
			else
			{
				$flag = 'false';
			}
		}
		$this->view->RMdata=$reportingManagerData;
		$this->view->reqform=$form;
		$this->view->flag=$flag;

	}
	public function getemailcountAction()
	{
		$bunitid = $this->_getParam('bunitid',null);
		$count = '';
		if(defined("REQ_HR_".$bunitid) && defined("REQ_MGMT_".$bunitid))
		{
			$count = '1';
		}

		$this->_helper->_json(array('count'=>$count));
	}
	public function getapproversAction()
	{
		$report_id = $this->_getParam('report_id',null);
		$dept_id = $this->_getParam('dept_id',null);
			
		$auth = Zend_Auth::getInstance();

		if($auth->hasIdentity())
		{
			$sess_vals = $auth->getStorage()->read();
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		$approver1_val = $this->_getParam('approver1_val',0);
		$approver2_val = $this->_getParam('approver2_val',0);
		$requi_model = new Default_Model_Requisition();
		$options = $requi_model->getapprovers($report_id, $dept_id);
		if($approver1_val == '0')
		$opt_str = sapp_Global::selectOptionBuilder('', 'Select Approver -1');
		else if($approver2_val == '0')
		$opt_str = sapp_Global::selectOptionBuilder('', 'Select Approver -2');
		else
		$opt_str = sapp_Global::selectOptionBuilder('', 'Select Approver -3');
		if(count($options) > 0)
		{
			foreach($options as $opt)
			{
				if($approver1_val != $opt['id'] && $approver2_val != $opt['id'] && $loginUserId != $opt['id'])
				{
					$opt_str .= sapp_Global::selectOptionBuilder($opt['id'], ucwords($opt['name']),$opt['profileimg']);
				}
			}
		}
		$this->_helper->_json(array('options' =>$opt_str));
	}
	public function approverejectrequisitionAction()
	{
	    $req_status = $this->_getParam('flag',null);
		$req_id = $this->_getParam('req_id',null);
		$requi_model = new Default_Model_Requisition();
		//$requisitionData=$requi_model->getRequisitionDataById($req_id );
	
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$sess_vals = $auth->getStorage()->read();
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
	   $requisitionData=$requi_model->getRequisitionForEdit($req_id,$loginUserId);
	   $aflag=$requisitionData['aflag'];
	   $aorder=$requisitionData['aorder'];
	
	if($req_status == 3)//for rejected
		{
			$data = array(
						'modifiedby' => trim($loginUserId),
						'modifiedon' => gmdate("Y-m-d H:i:s"),
						'appstatus'.$aorder => $req_status,                                       
						'req_status' => $req_status,
			);
		}
		
		else //for approved
		{
			
			if($aorder == 1)
			{
				if($requisitionData['approver2'] != '')
				{
					$data = array(
									'appstatus1' =>$req_status,
									'appstatus2' => 'Initiated',                                            
								  );
				}
				else
				{
					$data = array(
									'appstatus1' =>$req_status,
									'req_status' => $req_status,                                            
								 );
				}
			}
			else if($aorder == 2)
			{
				if($requisitionData['approver3'] != '')
				{
					$data = array(
									'appstatus2' =>$req_status,
									'appstatus3' => 'Initiated',                                            
								);
					
				}
				else
				{
					$data = array(
									'appstatus2' =>$req_status,
									'req_status' => $req_status,                                            
								  );								
				}
			}
			else if($aorder == 3)
			{
				$data = array(
									'appstatus3' =>$req_status,
									'req_status' => $req_status,                                            
				);
				
			}
			$data['modifiedby'] = trim($loginUserId);
			$data['modifiedon'] = gmdate("Y-m-d H:i:s");
		}
		$where = "id = ".$req_id;		
		$result = $requi_model->SaveorUpdateRequisitionData($data, $where);
				
	      //for saving Requisition history
			  if($result == 'update')
				{
					 $data = $requi_model->getReqDataForView($req_id);
				}
				if($result == 'update' && $data[0]['req_status']!='Initiated')
				{
                    $requisition_id=$req_id;
 					$history = 'Requisition status has been '.$data[0]['req_status'].' by ';
                    $createdby =$loginUserId;
					$modifiedby=$loginUserId;
					
					 $reqh_model = new Default_Model_Requisitionhistory();
					$requisition_history = array(											
										'requisition_id' =>$requisition_id,
										'description' => $history,
										'createdby' => $createdby,
										'modifiedby' => $modifiedby,
										'isactive' => 1,
										'createddate' =>gmdate("Y-m-d H:i:s"),
										'modifieddate'=> gmdate("Y-m-d H:i:s"),
									);
					$where = '';
					$historyId = $reqh_model->saveOrUpdateRequisitionHistory($requisition_history,$where); 
				}
				
			// History end
			
			//start of mailing
				$tableid = $result;
				if($result != '')
				{
					
						//start of mailing
						$user_model = new Default_Model_Usermanagement();
						$jobtitleModel = new Default_Model_Jobtitles();
						$requisition_data = $requi_model->getRequisitionDataById($req_id);
						$report_person_data = $user_model->getUserDataById($requisition_data['reporting_id']);

					    	$st_arr = array(
							'0'		=>		'Select status',
							'2'		=>		'Approved',
							'3'		=> 		'Rejected'
							);

							if($req_status == 3 )//for rejected
							{

								$approver1_person_data = $user_model->getUserDataById($requisition_data['approver1']);
								$Raisedby_person_data = $user_model->getUserDataById($requisition_data['createdby']);

								$jobttlArr = $jobtitleModel->getsingleJobTitleData(trim($requisition_data['jobtitle']));
								if(!empty($jobttlArr) && $jobttlArr != 'norows')
								{
									$jobtitlename = ' - '.$jobttlArr[0]['jobtitlename'];
								}
								else
								$jobtitlename = '';

								$mail_arr[0]['name'] = 'HR';
								$mail_arr[0]['email'] = defined('REQ_HR_'.$requisition_data['businessunit_id'])?constant('REQ_HR_'.$requisition_data['businessunit_id']):"";
								$mail_arr[0]['type'] = 'HR';
								$mail_arr[1]['name'] = 'Management';
								$mail_arr[1]['email'] = defined('REQ_MGMT_'.$requisition_data['businessunit_id'])?constant('REQ_MGMT_'.$requisition_data['businessunit_id']):"";
								$mail_arr[1]['type'] = 'Management';
								$mail_arr[2]['name'] = $Raisedby_person_data['userfullname'];
								$mail_arr[2]['email'] = $Raisedby_person_data['emailaddress'];
								$mail_arr[2]['type'] = 'Raise';
								$mail_arr[3]['name'] = $approver1_person_data['userfullname'];
								$mail_arr[3]['email'] = $approver1_person_data['emailaddress'];
								$mail_arr[3]['type'] = 'Approver';
								
								$appr_str = "";
								$appr_str = $approver1_person_data['userfullname'];
								/* if($requisition_data['approver2'] != '')
								{ */
									$approver2_person_data = $user_model->getUserDataById($requisition_data['approver2']);
									$appr_str .= ", ".$approver2_person_data['userfullname'];
									$mail_arr[4]['name'] = $approver2_person_data['userfullname'];
									$mail_arr[4]['email'] = $approver2_person_data['emailaddress'];
									$mail_arr[4]['type'] = 'Approver';
								//}
								/* if($requisition_data['approver3'] != '')
								{ */
									$approver3_person_data = $user_model->getUserDataById($requisition_data['approver3']);
									$appr_str .= " and ".$approver3_person_data['userfullname'];
									$mail_arr[5]['name'] = $approver3_person_data['userfullname'];
									$mail_arr[5]['email'] = $approver3_person_data['emailaddress'];
									$mail_arr[5]['type'] = 'Approver';
								//}
								// Check if the reporting person and raised person are same - Requisition raised by Manager case
								if($requisition_data['reporting_id'] != $requisition_data['createdby']){
									$mail_arr[6]['name'] = $report_person_data['userfullname'];
									$mail_arr[6]['email'] = $report_person_data['emailaddress'];
									$mail_arr[6]['type'] = 'Report';
								}
								
								$mail = array();
								for($ii = 0;$ii < count($mail_arr);$ii++)
								{
									$base_url = 'http://'.$this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
									$view = $this->getHelper('ViewRenderer')->view;
									$this->view->emp_name = (!empty($mail_arr[$ii]['name']))?$mail_arr[$ii]['name']:'';
									$this->view->base_url=$base_url;
									$this->view->type = (!empty($mail_arr[$ii]['type']))?$mail_arr[$ii]['type']:'';
									$this->view->jobtitle = $jobtitlename;
									$this->view->requisition_code = $requisition_data['requisition_code'];
									$this->view->approver_str = $appr_str;
									$this->view->raised_name = $Raisedby_person_data['userfullname'];
									$this->view->req_status = $st_arr[$req_status];
									$this->view->reporting_manager = $report_person_data['userfullname'];
									$text = $view->render('mailtemplates/changedrequisition.phtml');
									$options['subject'] = ($st_arr[$req_status]=='Approved')?APPLICATION_NAME.': Requisition is approved':APPLICATION_NAME.': Requisition is rejected';
									
									$options['header'] = 'Requisition Status';
									$options['toEmail'] = (!empty($mail_arr[$ii]['email']))?$mail_arr[$ii]['email']:'';
									$options['toName'] = (!empty($mail_arr[$ii]['name']))?$mail_arr[$ii]['name']:'';
									$options['message'] = $text;
									$mail[$ii] =$options;
									$options['cron'] = 'yes';
									if($options['toEmail'] != ''){
										sapp_Global::_sendEmail($options);
									}
								}
									
							}
							else if($req_status == 2 )//for approved
							{
							
								$approver_person_data = $user_model->getUserDataById($loginUserId);
							/* 	$mail_arr[0]['name'] = $approver_person_data['userfullname'];
								$mail_arr[0]['email'] = $approver_person_data['emailaddress'];
								$mail_arr[0]['type'] = 'Approver'; */
								/* if($edit_flag == 'yes')
								{
									$approved_by_data = $user_model->getUserDataById($requisition_data['approver'.$appr_per]);
									$req_status = 2;
								} */
								//else
								$approved_by_data = $user_model->getUserDataById($loginUserId);

								$Raisedby_person_data = $user_model->getUserDataById($requisition_data['createdby']);
								$appr_str = $approved_by_data['userfullname'];
								
								
								$mail_arr[0]['name'] = 'HR';
								$mail_arr[0]['email'] = defined('REQ_HR_'.$requisition_data['businessunit_id'])?constant('REQ_HR_'.$requisition_data['businessunit_id']):"";
								$mail_arr[0]['type'] = 'HR';
								$mail_arr[1]['name'] = 'Management';
								$mail_arr[1]['email'] = defined('REQ_MGMT_'.$requisition_data['businessunit_id'])?constant('REQ_MGMT_'.$requisition_data['businessunit_id']):"";
								$mail_arr[1]['type'] = 'Management';
								$mail_arr[2]['name'] = $Raisedby_person_data['userfullname'];
								$mail_arr[2]['email'] = $Raisedby_person_data['emailaddress'];
								$mail_arr[2]['type'] = 'Raise';
								
								
								if($requisition_data['approver1'] != '')
								{
									$approver1_person_data = $user_model->getUserDataById($requisition_data['approver1']);
									//$appr_str .= ", ".$approver1_person_data['userfullname'];
									$mail_arr[3]['name'] = $approver1_person_data['userfullname'];
									$mail_arr[3]['email'] = $approver1_person_data['emailaddress'];
									$mail_arr[3]['type'] = 'Approver';
								}
								
								
								if($requisition_data['approver2'] != '')
								{
									$approver2_person_data = $user_model->getUserDataById($requisition_data['approver2']);
									//$appr_str .= ", ".$approver2_person_data['userfullname'];
									$mail_arr[4]['name'] = $approver2_person_data['userfullname'];
									$mail_arr[4]['email'] = $approver2_person_data['emailaddress'];
									$mail_arr[4]['type'] = 'Approver';
								}
								if($requisition_data['approver3'] != '')
								{
									$approver3_person_data = $user_model->getUserDataById($requisition_data['approver3']);
									//$appr_str .= " and ".$approver3_person_data['userfullname'];
									$mail_arr[5]['name'] = $approver3_person_data['userfullname'];
									$mail_arr[5]['email'] = $approver3_person_data['emailaddress'];
									$mail_arr[5]['type'] = 'Approver';
								}

								for($ii = 0;$ii < count($mail_arr);$ii++)
								{
									$base_url = 'http://'.$this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
									$view = $this->getHelper('ViewRenderer')->view;
									$this->view->emp_name = $mail_arr[$ii]['name'];
									$this->view->base_url=$base_url;
									$this->view->type = $mail_arr[$ii]['type'];
									$this->view->requisition_code = $requisition_data['requisition_code'];
									$this->view->req_status = $st_arr[$req_status];
									$this->view->raised_name = $Raisedby_person_data['userfullname'];
									$this->view->approver_str = $appr_str;
									$text = $view->render('mailtemplates/changedrequisition.phtml');
									$options['subject'] = ($st_arr[$req_status]=='Approved')?APPLICATION_NAME.': Requisition is approved':APPLICATION_NAME.': Requisition is rejected';
									$options['header'] = 'Requisition Status';
									$options['toEmail'] = $mail_arr[$ii]['email'];
									$options['toName'] = $mail_arr[$ii]['name'];
									$options['message'] = $text;

									$options['cron'] = 'yes';
									if($options['toEmail'] != ''){
										sapp_Global::_sendEmail($options);
									}
								}
							}
					
				}//end of mailing
			
				$this->_helper->_json(array('msg' =>"success"));
	}
	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity())
        {
            $loginUserId = $auth->getStorage()->read()->id;
            $login_group_id = $auth->getStorage()->read()->group_id;
            $login_role_id = $auth->getStorage()->read()->emprole;
        }        		
        $id = $this->_getParam('id',null);
        $clientsModel = new Default_Model_Clients();
        $usersModel = new Default_Model_Users();
	    $requi_model = new Default_Model_Requisition();
            if(is_numeric($id) && $id >0)
            {
                $id = abs($id);
                $data = $requi_model->getReqDataForView($id);
                $app1_name = $app2_name = $app3_name='';
                if(count($data)>0  && $data[0]['req_status'] == 'Initiated')
                {
                    $data = $data[0];                 										 
                    $data['jobtitlename'] = '';			
                    $data['businessunit_name'] = $data['businessunit_name'];									
                    $data['dept_name'] = $data['department_name'];									
                    $data['titlename'] = $data['jobtitle_name'];									
                    $data['posname'] = $data['position_name'];									
                    $data['empttype'] = $data['emp_type_name'];						                       
                    $data['mngrname'] = $data['reporting_manager_name'];						
                    $data['raisedby'] = $data['createdby_name'];			                        
                    $data['app1_name'] = $data['approver1_name'];
                        
                    if($data['approver2'] != '')
                    {                        
                        $data['app2_name'] = $data['approver2_name'];
                    }
                    else 
                    {
                        $data['app2_name'] = 'No Approver';
                    }
                        
                    if($data['approver3'] != '')
                    {                        
                        $data['app3_name'] = $data['approver3_name'];
                    }
                    else 
                    {
                        $data['app3_name'] = 'No Approver';
                    }                        
			
                   /*  foreach($data as $key=>$val)
                    {
                        $data[$key] = htmlentities($val, ENT_QUOTES, "UTF-8");
                    }	 */
                    if($data['client_id'] != '')
                    {
                    	$clien_data = $clientsModel->getClientDetailsById($data['client_id']);
                    	$data['client_id']=$clien_data[0]['client_name'];
                    }
                    if($data['recruiters'] != '')
                    {
                    	$name = '';
                    	$recData=$usersModel->getUserDetailsforView($data['recruiters']);
                    	if(count($recData)>0)
                    	{
                    		foreach($recData as $dataname){
                    			$name = $name.','.$dataname['name'];
                    		}
                    
                    	}
                    	$data['recruiters']=ltrim($name,',');
                    }
                    
                    if($data['req_priority'] == 1) {
                    	$data['req_priority']='High';
                    }else if($data['req_priority'] == 2) {
                    	$data['req_priority']='Medium';
                    }else {
                    $data['req_priority']='Low';
                    }
                    $data['onboard_date'] = sapp_Global::change_date($data['onboard_date'], 'view');
                    
                    $previ_data = sapp_Global::_checkprivileges(REQUISITION,$login_group_id,$login_role_id,'edit');

                    $this->view->previ_data = $previ_data;
                    $this->view->data = $data;
                    
                    $this->view->id = $id;
                    $this->view->controllername = "requisition";
                    $this->view->ermsg = '';
					
                }
                else
                {
                    $this->view->nodata = 'nodata';
                }
            }
            else
            {
                $this->view->nodata = 'nodata';
            }
		
			$this->view->id=  $id;
			$this->view->controllername='requisition';
		
	}

	public function uploadAction()
    {

    }

    public function uploadviewAction()
    {

    }
}