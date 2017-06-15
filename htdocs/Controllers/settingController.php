<?php

namespace Hmg\Controllers;

use Hmg\Models\Setting;
use Hmg\Models\SchoolDistrict;
use Hmg\Models\SchoolDistrictZipcodes;
use Hmg\Models\CountyZipcodes;
use Hmg\Models\ReferralServices;

class SettingController
{
    public function __construct()
    {
        
        if (isset($_REQUEST['save']) && isset($_REQUEST['json'])) {
            $data = json_decode($_REQUEST['json']);
            //echo "<pre>";print_r($data);die;
		   
            if(isset($data->is_zip) && $data->is_zip == 'zip') { //251116
                $zipcodes = new SchoolDistrictZipcodes();
                if (is_array($data->settings)) {
                    foreach ($data->settings as $settingObj) {
                        if ($settingObj->id || $settingObj->value) {
                            $zipcodes->zipcodes = array($settingObj->id => $settingObj->value);
                            $saved = $zipcodes->save($data->district_id);
                        }
                    }
                }
                $zipcodes = new SchoolDistrictZipcodes();
                $zipcodesArray = $zipcodes->getAll($data->district_id);
                $jsonEncodedSettings = json_encode($zipcodesArray);
            } else if(isset($data->is_zip) && $data->is_zip == 'district') { //251116
                //echo "<pre>";print_r($data);
                $schoolDistrict = new SchoolDistrict();
                if (is_array($data->settings)) {
                    foreach ($data->settings as $settingObj) {
                        if ($settingObj->id || $settingObj->value) {
                            $schoolDistrict->districts = array($settingObj->id => $settingObj->value);
                            $saved = $schoolDistrict->save();
                        }
                    }
                }
                $schoolDistrict = new SchoolDistrict();
                $districtsArray = $schoolDistrict->getAll();
                //echo "<pre>";print_r($districtsArray);die;
                $jsonEncodedSettings = json_encode($districtsArray);
            } else if(isset($data->is_zip) && $data->is_zip == 'county_zip') { //281116
                //echo "<pre>";print_r($data);die;
                $countyZipcodes = new CountyZipcodes();
                if (is_array($data->settings)) {
                    foreach ($data->settings as $settingObj) {
                        if ($settingObj->id || $settingObj->value) {
                            $countyZipcodes->zipcodes = array($settingObj->id => $settingObj->value);
                            $saved = $countyZipcodes->save($settingObj->zip, $data->district_id);
                        }
                    }
                }
                $countyZipcodes = new CountyZipcodes();
                $zipcodesArray = $countyZipcodes->getAll($data->district_id);
                $jsonEncodedSettings = json_encode($zipcodesArray);
            } 
			else if(isset($data->type) && $data->type == 'media'){
				$type = 'how_heard_category';
				$typeName = $data->mediaName;
				$setting = new Setting($type);
                $newSettings = array();
                if (is_array($data->settings)) {
                    foreach ($data->settings as $settingObj) {
                        if ($settingObj->id || $settingObj->value) {
                            $setting->settings = array($settingObj->id => $settingObj->value);
                            $saved = $setting->save('', $type, '', $data->id,$typeName);
                        }  
                    }
                }
				$jsonEncodedSettings = json_encode(array('msg' => "child save"));	
				//$jsonEncodedSettings = json_encode($setting->settings);
			}
			else {
                if (isset($_REQUEST['save']) && isset($_REQUEST['type']) && isset($_REQUEST['json']) 
                    && $_REQUEST['type'] == 'org') { //save organization data
                   $type = 'organization_name';
                } else {
                    $type = ltrim($data->id, '#');
                }
				
                $setting = new Setting($type);
                $newSettings = array();
                if (is_array($data->settings)) {
                    foreach ($data->settings as $settingObj) {
                        if ($settingObj->id || $settingObj->value) {
                            $setting->settings = array($settingObj->id => $settingObj->value);
                            $token = '';
                            if(isset($settingObj->token) && $settingObj->token) {
                                $token = $settingObj->token;
                            }
                            $org_id = isset($settingObj->org_id) ? $settingObj->org_id : 0;
                            $saved = $setting->save($token, $type, $org_id);//28-10
                        }
                        
                    }
                }

                if ($type === 'best_hours') {
                    $setting->setBestHoursSettings();
                } else {
                    $setting->setSettings();
                }       
                $jsonEncodedSettings = json_encode($setting->settings);
            }

            header('Content-Type: aplication/json');
            echo $jsonEncodedSettings;
        }
        if (isset($_REQUEST['update']) && isset($_REQUEST['json'])) {
            $data = json_decode($_REQUEST['json']);
            //echo "<pre>";print_r($data);die;
            $id = $data->id;
            $value = $data->value;
            if(isset($data->is_zip) && $data->is_zip == 'zip') { //251116
                $zipcodes = new SchoolDistrictZipcodes();
                $updated  = $zipcodes->updateRecordDisabled($id, $value);
            } else if(isset($data->is_zip) && $data->is_zip == 'district') { //251116
                $schoolDistrict = new SchoolDistrict();
                $updated = $schoolDistrict->updateRecordDisabled($id, $value);
            } else if(isset($data->is_zip) && $data->is_zip == 'county_zip') { //281116
                $countyZipcodes = new CountyZipcodes();
                $updated = $countyZipcodes->updateRecordDisabled($id, $value);
            } elseif(isset($data->is_service) && $data->is_service == 'service') { //010217
                $referralServices = new ReferralServices($data->sites_id);
                $updated = $referralServices->updateServiceDisabled($data->id, $data->value);
            } else {
                $setting = new Setting();
                $updated = $setting->updateSettingDisabled($id, $value);
            }

            header('Content-Type: aplication/json');
            echo '{ "success" : ' . $updated . ' }';
        }

        if (isset($_REQUEST['type']) && isset($_REQUEST['search'])) {
            $setting = new Setting();
            $setting->setType($_REQUEST['type']);
            $list = $setting->getNamesAndIds($_REQUEST['search']);
            $this->displaySettingsNameJson($list);
        }
        if (isset($_REQUEST['get-select']) 
            && $_REQUEST['get-select'] == true
            && !empty($_REQUEST['heard_id'])) {
            //fetch how heard details based on heard_id
            $heardDetails = new Setting();
            $select = $heardDetails->displayHeardDetailsSelect(
                $parent = $_REQUEST['heard_id'],
                'data[how_heard_details_id]',
                (isset($_REQUEST['selected']) ? $_REQUEST['selected'] : ''), '',
                $label    = ' ',
                $tabIndex = '',
                $required = false,
                $addtlclasses = 'chosen-select',
                true,
                $filtered = true,
                $allowDisableSelect = true,
                'how_heard_details_id'
            );
            header('Content-Type: text/html');
            echo $select;
            exit;
        }
    }

    public function displaySettingsNameJson($list)
    {
        if (is_array($list)) {
            $json = json_encode($list);
            header('Content-Type: application/json');
            echo $json;
        }
        exit;
    }
}
