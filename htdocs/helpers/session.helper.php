<?php

namespace Hmg\Helpers;

class SessionHelper
{
    public function getFamilyFilters()
    {
        $filters['first_name_1'] = (isset($_SESSION['families']['filters']['first_name_1']) ? $_SESSION['families']['filters']['first_name_1'] : '');
        $filters['last_name_1'] = (isset($_SESSION['families']['filters']['last_name_1']) ? $_SESSION['families']['filters']['last_name_1'] : '');
        $filters['child_name'] = (isset($_SESSION['families']['filters']['child_name']) ? $_SESSION['families']['filters']['child_name'] : '');
        $filters['child_first_name'] = (isset($_SESSION['families']['filters']['child_first_name']) ? $_SESSION['families']['filters']['child_first_name'] : '');
        $filters['child_last_name'] = (isset($_SESSION['families']['filters']['child_last_name']) ? $_SESSION['families']['filters']['child_last_name'] : '');
        $filters['child_id'] = (isset($_SESSION['families']['filters']['child_id']) ? $_SESSION['families']['filters']['child_id'] : '');
        $filters['primary_phone'] = (isset($_SESSION['families']['filters']['primary_phone']) ? $_SESSION['families']['filters']['primary_phone'] : '');
        $filters['family_code'] = (isset($_SESSION['families']['filters']['family_code']) ? $_SESSION['families']['filters']['family_code'] : '');
        $filters['email'] = (isset($_SESSION['families']['filters']['email']) ? $_SESSION['families']['filters']['email'] : '');
        $filters['city'] = (isset($_SESSION['families']['filters']['city']) ? $_SESSION['families']['filters']['city'] : '');
        $filters['county'] = (isset($_SESSION['families']['filters']['county']) ? $_SESSION['families']['filters']['county'] : '');
        $filters['zip'] = (isset($_SESSION['families']['filters']['zip']) ? $_SESSION['families']['filters']['zip'] : '');
        $filters['school_district'] = (isset($_SESSION['families']['filters']['school_district']) ? $_SESSION['families']['filters']['school_district'] : '');
        $filters['age_min'] = (isset($_SESSION['families']['filters']['age_min']) ? $_SESSION['families']['filters']['age_min'] : '');
        $filters['age_max'] = (isset($_SESSION['families']['filters']['age_max']) ? $_SESSION['families']['filters']['age_max'] : '');
        $filters['status'] = (isset($_SESSION['families']['filters']['status']) ? $_SESSION['families']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['families']['filters']['start_date']) ? $_SESSION['families']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['families']['filters']['end_date']) ? $_SESSION['families']['filters']['end_date'] : '');
        $filters['hmg_worker'] = (isset($_SESSION['families']['filters']['hmg_worker']) ? $_SESSION['families']['filters']['hmg_worker'] : '');
        $filters['language_id'] = (isset($_SESSION['families']['filters']['language_id']) ? $_SESSION['families']['filters']['language_id'] : '');
        $filters['race_id'] = (isset($_SESSION['families']['filters']['race_id']) ? $_SESSION['families']['filters']['race_id'] : '');
        $filters['ethnicity_id'] = (isset($_SESSION['families']['filters']['ethnicity_id']) ? $_SESSION['families']['filters']['ethnicity_id'] : '');
        $filters['family_heard_id'] = (isset($_SESSION['families']['filters']['family_heard_id']) ? $_SESSION['families']['filters']['family_heard_id'] : '');
        $filters['call_reason_id'] = (isset($_SESSION['families']['filters']['call_reason_id']) ? $_SESSION['families']['filters']['call_reason_id'] : '');
        $filters['issue'] = (isset($_SESSION['families']['filters']['issue']) ? $_SESSION['families']['filters']['issue'] : '');
        $filters['cc_level'] = (isset($_SESSION['families']['filters']['cc_level']) ? $_SESSION['families']['filters']['cc_level'] : '');
        $filters['region_id'] = (isset($_SESSION['families']['filters']['region_id']) ? $_SESSION['families']['filters']['region_id'] : '');
        $filters['success_story'] = (isset($_SESSION['families']['filters']['success_story']) ? $_SESSION['families']['filters']['success_story'] : '');
        $filters['provider_or_clinic'] = (isset($_SESSION['families']['filters']['provider_or_clinic']) ? $_SESSION['families']['filters']['provider_or_clinic'] : '');
        $filters['quick'] = (isset($_SESSION['families']['filters']['quick']) ? $_SESSION['families']['filters']['quick'] : '');

        return $filters;
    }

    public function getFollowUpFilters()
    {
        $filters['hmg_worker'] = (isset($_SESSION['followUps']['filters']['hmg_worker']) ? $_SESSION['followUps']['filters']['hmg_worker'] : '');
        $filters['language_id'] = (isset($_SESSION['followUps']['filters']['language_id']) ? $_SESSION['followUps']['filters']['language_id'] : '');
        $filters['status'] = (isset($_SESSION['followUps']['filters']['status']) ? $_SESSION['followUps']['filters']['status'] : '');
        $filters['follow_up_task'] = (isset($_SESSION['followUps']['filters']['follow_up_task']) ? $_SESSION['followUps']['filters']['follow_up_task'] : '');
        $filters['start_date'] = (isset($_SESSION['followUps']['filters']['start_date']) ? $_SESSION['followUps']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['followUps']['filters']['end_date']) ? $_SESSION['followUps']['filters']['end_date'] : '');
        $filters['done'] = (isset($_SESSION['followUps']['filters']['done']) ? $_SESSION['followUps']['filters']['done'] : '');

        return $filters;
    }
	

    public function getCountFilters()
    {
        $filterKeys = array(
            'county',
            'school_district',
            'zip',
            'status',
            'start_date',
            'end_date',
            'region_id',
            'hmg_worker',
            'language_id',
            'city',
            'cc_level',
            'family_heard_id',
            'provider_or_clinic'
        );

        foreach ($filterKeys as $key) {
            if($key == 'region_id' 
                && isset($_SESSION['user'][$key]) && !empty($_SESSION['user'][$key])) { //globally set region filter acc. to user permissions
                $filters[$key] = isset($_SESSION['user'][$key]) ?
                $_SESSION['user'][$key] : '';
            } else {
                $filters[$key] = isset($_SESSION['count']['filters'][$key]) ?
                    $_SESSION['count']['filters'][$key] : '';
            }
        }

        return $filters;
    }

    public function getReportFilters()
    {
        $filters['county']          = (isset($_SESSION['report-filters']['county']) ? $_SESSION['report-filters']['county'] : '');
        $filters['school_district'] = (isset($_SESSION['report-filters']['school_district']) ? $_SESSION['report-filters']['school_district'] : '');
        $filters['zip']             = (isset($_SESSION['report-filters']['zip']) ? $_SESSION['report-filters']['zip'] : '');
        $filters['status']          = (isset($_SESSION['report-filters']['status']) ? $_SESSION['report-filters']['status'] : '');
        $filters['start_date']      = (isset($_SESSION['report-filters']['start_date']) ? $_SESSION['report-filters']['start_date'] : '');
        $filters['end_date']        = (isset($_SESSION['report-filters']['end_date']) ? $_SESSION['report-filters']['end_date'] : '');
        $filters['region_id']       = (isset($_SESSION['report-filters']['region_id']) ? $_SESSION['report-filters']['region_id'] : '');

        return $filters;
    }

    public function getOrganizationFilters()
    {
        $filters['child_id'] = (isset($_SESSION['organizations']['filters']['child_id']) ? $_SESSION['organizations']['filters']['child_id'] : '');
        $filters['primary_phone'] = (isset($_SESSION['organizations']['filters']['primary_phone']) ? $_SESSION['organizations']['filters']['primary_phone'] : '');
        $filters['family_code'] = (isset($_SESSION['organizations']['filters']['family_code']) ? $_SESSION['organizations']['filters']['family_code'] : '');
        $filters['email'] = (isset($_SESSION['organizations']['filters']['email']) ? $_SESSION['organizations']['filters']['email'] : '');
        $filters['city'] = (isset($_SESSION['organizations']['filters']['city']) ? $_SESSION['organizations']['filters']['city'] : '');
        $filters['county'] = (isset($_SESSION['organizations']['filters']['county']) ? $_SESSION['organizations']['filters']['county'] : '');
        $filters['zip'] = (isset($_SESSION['organizations']['filters']['zip']) ? $_SESSION['organizations']['filters']['zip'] : '');
        $filters['school_district'] = (isset($_SESSION['organizations']['filters']['school_district']) ? $_SESSION['organizations']['filters']['school_district'] : '');
        $filters['age_min'] = (isset($_SESSION['organizations']['filters']['age_min']) ? $_SESSION['organizations']['filters']['age_min'] : '');
        $filters['age_max'] = (isset($_SESSION['organizations']['filters']['age_max']) ? $_SESSION['organizations']['filters']['age_max'] : '');
        $filters['status'] = (isset($_SESSION['organizations']['filters']['status']) ? $_SESSION['organizations']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['organizations']['filters']['start_date']) ? $_SESSION['organizations']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['organizations']['filters']['end_date']) ? $_SESSION['organizations']['filters']['end_date'] : '');
        $filters['hmg_worker'] = (isset($_SESSION['organizations']['filters']['hmg_worker']) ? $_SESSION['organizations']['filters']['hmg_worker'] : '');
        $filters['language_id'] = (isset($_SESSION['organizations']['filters']['language_id']) ? $_SESSION['organizations']['filters']['language_id'] : '');
        $filters['race_id'] = (isset($_SESSION['organizations']['filters']['race_id']) ? $_SESSION['organizations']['filters']['race_id'] : '');
        $filters['ethnicity_id'] = (isset($_SESSION['organizations']['filters']['ethnicity_id']) ? $_SESSION['organizations']['filters']['ethnicity_id'] : '');
        $filters['family_heard_id'] = (isset($_SESSION['organizations']['filters']['family_heard_id']) ? $_SESSION['organizations']['filters']['family_heard_id'] : '');
        $filters['call_reason_id'] = (isset($_SESSION['organizations']['filters']['call_reason_id']) ? $_SESSION['organizations']['filters']['call_reason_id'] : '');
        $filters['issue'] = (isset($_SESSION['organizations']['filters']['issue']) ? $_SESSION['organizations']['filters']['issue'] : '');
        $filters['cc_level'] = (isset($_SESSION['organizations']['filters']['cc_level']) ? $_SESSION['organizations']['filters']['cc_level'] : '');
        $filters['region_id'] = (isset($_SESSION['organizations']['filters']['region_id']) ? $_SESSION['organizations']['filters']['region_id'] : '');
        $filters['success_story'] = (isset($_SESSION['organizations']['filters']['success_story']) ? $_SESSION['organizations']['filters']['success_story'] : '');
        $filters['provider_or_clinic'] = (isset($_SESSION['organizations']['filters']['provider_or_clinic']) ? $_SESSION['organizations']['filters']['provider_or_clinic'] : '');
        $filters['quick'] = (isset($_SESSION['organizations']['filters']['quick']) ? $_SESSION['organizations']['filters']['quick'] : '');

        //new setting's filters
        $filters['organization_type_id'] = (isset($_SESSION['organizations']['filters']['organization_type_id']) ? $_SESSION['organizations']['filters']['organization_type_id'] : '');
        $filters['partnership_level_id'] = (isset($_SESSION['organizations']['filters']['partnership_level_id']) ? $_SESSION['organizations']['filters']['partnership_level_id'] : '');
        $filters['resource_database_id'] = (isset($_SESSION['organizations']['filters']['resource_database_id']) ? $_SESSION['organizations']['filters']['resource_database_id'] : '');
        $filters['type_of_contact_id'] = (isset($_SESSION['organizations']['filters']['type_of_contact_id']) ? $_SESSION['organizations']['filters']['type_of_contact_id'] : '');
        $filters['contact_name'] = (isset($_SESSION['organizations']['filters']['contact_name']) ? $_SESSION['organizations']['filters']['contact_name'] : '');
        $filters['event_type_id'] = (isset($_SESSION['organizations']['filters']['event_type_id']) ? $_SESSION['organizations']['filters']['event_type_id'] : '');
		
	   $filters['organization_type_id'] = (isset($_SESSION['organizations']['filters']['organization_type_id']) ? $_SESSION['organizations']['filters']['organization_type_id'] : '');
		
		$filters['quick'] = (isset($_SESSION['organizations']['filters']['quick']) ? $_SESSION['organizations']['filters']['quick'] : '');
        $filters['done'] = (isset($_SESSION['organizations']['filters']['done']) ? $_SESSION['organizations']['filters']['done'] : '');
        $filters['follow_up_task'] = (isset($_SESSION['followUps']['filters']['follow_up_task']) ? $_SESSION['followUps']['filters']['follow_up_task'] : '');
		

        return $filters;
    }
	
	public function getOrganizationFollowUpssFilters()
    {
        $filters['child_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['child_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['child_id'] : '');
        $filters['primary_phone'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['primary_phone']) ? $_SESSION['OrganizationFollowUpss']['filters']['primary_phone'] : '');
        $filters['family_code'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['family_code']) ? $_SESSION['OrganizationFollowUpss']['filters']['family_code'] : '');
        $filters['email'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['email']) ? $_SESSION['OrganizationFollowUpss']['filters']['email'] : '');
        $filters['city'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['city']) ? $_SESSION['OrganizationFollowUpss']['filters']['city'] : '');
        $filters['county'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['county']) ? $_SESSION['OrganizationFollowUpss']['filters']['county'] : '');
        $filters['zip'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['zip']) ? $_SESSION['OrganizationFollowUpss']['filters']['zip'] : '');
        $filters['school_district'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['school_district']) ? $_SESSION['OrganizationFollowUpss']['filters']['school_district'] : '');
        $filters['age_min'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['age_min']) ? $_SESSION['OrganizationFollowUpss']['filters']['age_min'] : '');
        $filters['age_max'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['age_max']) ? $_SESSION['OrganizationFollowUpss']['filters']['age_max'] : '');
        $filters['status'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['status']) ? $_SESSION['OrganizationFollowUpss']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['start_date']) ? $_SESSION['OrganizationFollowUpss']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['end_date']) ? $_SESSION['OrganizationFollowUpss']['filters']['end_date'] : '');
        $filters['hmg_worker'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['hmg_worker']) ? $_SESSION['OrganizationFollowUpss']['filters']['hmg_worker'] : '');
        $filters['language_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['language_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['language_id'] : '');
        $filters['race_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['race_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['race_id'] : '');
        $filters['ethnicity_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['ethnicity_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['ethnicity_id'] : '');
        $filters['family_heard_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['family_heard_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['family_heard_id'] : '');
        $filters['call_reason_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['call_reason_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['call_reason_id'] : '');
        $filters['issue'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['issue']) ? $_SESSION['OrganizationFollowUpss']['filters']['issue'] : '');
        $filters['cc_level'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['cc_level']) ? $_SESSION['OrganizationFollowUpss']['filters']['cc_level'] : '');
        $filters['region_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['region_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['region_id'] : '');
        $filters['success_story'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['success_story']) ? $_SESSION['OrganizationFollowUpss']['filters']['success_story'] : '');
        $filters['provider_or_clinic'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['provider_or_clinic']) ? $_SESSION['OrganizationFollowUpss']['filters']['provider_or_clinic'] : '');
        $filters['quick'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['quick']) ? $_SESSION['OrganizationFollowUpss']['filters']['quick'] : '');

        //new setting's filters
        $filters['organization_type_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['organization_type_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['organization_type_id'] : '');
        $filters['partnership_level_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['partnership_level_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['partnership_level_id'] : '');
        $filters['resource_database_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['resource_database_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['resource_database_id'] : '');
        $filters['type_of_contact_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['type_of_contact_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['type_of_contact_id'] : '');
        $filters['contact_name'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['contact_name']) ? $_SESSION['OrganizationFollowUpss']['filters']['contact_name'] : '');
        $filters['event_type_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['event_type_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['event_type_id'] : '');
		
	   $filters['organization_type_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['organization_type_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['organization_type_id'] : '');
		
		$filters['quick'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['quick']) ? $_SESSION['OrganizationFollowUpss']['filters']['quick'] : '');
        $filters['done'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['done']) ? $_SESSION['OrganizationFollowUpss']['filters']['done'] : '');
        $filters['follow_up_task_id'] = (isset($_SESSION['OrganizationFollowUpss']['filters']['follow_up_task_id']) ? $_SESSION['OrganizationFollowUpss']['filters']['follow_up_task_id'] : '');
		

        return $filters;
    }
	
	
}
