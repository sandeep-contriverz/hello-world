<?php


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0


ini_set('display_errors', 'on');
//error_reporting(E_ALL ^ E_NOTICE);
error_reporting(E_ALL);

require('../vendor/autoload.php');

const CLASS_PATH        = 'Models';
const VIEW_PATH         = 'views';
const CONTROLLER_PATH   = 'Controllers';
const TEMPLATE_PATH     = 'templates';
const TEMPLATE          = '/site_db_template.phtml';
const SITE_TEMPLATE     = '/site_main_template.phtml';
const PRINT_TEMPLATE    = '/site_print_template.phtml';

$env = getenv('ENVIRONMENT');

switch($env){
    case 'UTAH':
        $host       = 'localhost';
        $database   = 'hmgutah';
        $user       = 'hmgdbuser';
        $pass       = 'webadmin';
        $GLOBALS['faxLogo']    = 'images/HMGU-logo-black-pdf.png';
        $GLOBALS['pdfLogo']    = 'images/HMGU-logo-black-pdf.png';
        $GLOBALS['printLogo']    = 'images/HMGU-logo-black.png';
        $GLOBALS['printLogoClass']    = 'print-logo-utah';
        $GLOBALS['footerText'] = 'helpmegrowutah.org | helpmegrow@unitedwayuc.org |'
            . ' 801-691-5322 | 148 North 100 West, Provo, UT 84604';
        $GLOBALS['certificate'] = [
            'vertical-line-color' => [152, 162, 0],
            'horz-thick-line-color' => [127, 97, 118],
            'horz-thin-line-color' => [225, 178, 46]
        ];
        $GLOBALS['sync_url'] = 'https://unitedwayuc.org/scripts/HMG-utah/sync-asq.php';
        break;
    case 'ALABAMA':
        $host       = 'localhost';
        $database   = 'hmg_alabama';
        $user       = 'hmgalabama';
        $pass       = 'montgomery';
        $GLOBALS['faxLogo']    = 'images/pdf-logo.png';
        $GLOBALS['pdfLogo']    = 'images/hmg_logo.jpg';
        $GLOBALS['printLogo']    = 'images/hmg_logo.jpg';
        $GLOBALS['printLogoClass']    = 'print-logo';
        $GLOBALS['footerText'] = '3600 8th Avenue South | P.O. Box 320189 |'
            . ' Birmingham, AL 35232 | 205-458-2070 or dial 2-1-1';
        $GLOBALS['certificate'] = [
            'vertical-line-color' => [125, 65, 123],
            'horz-thick-line-color' => [125, 65, 123],
            'horz-thin-line-color' => [66, 180, 230],
            'stroke-line-color' => [66, 180, 230]
        ];
        $GLOBALS['sync_url'] = 'https://unitedwayuc.org/scripts/HMG-alabama/sync-asq.php';
        break;
    case 'SKELETON':
        $host       = 'localhost';
        $database   = 'hmg_skeleton';
        $user       = 'hmgskeleton';
        $pass       = 'hmgskeleton';
        $GLOBALS['faxLogo']    = 'images/pdf-logo.png';
        $GLOBALS['pdfLogo']    = 'images/pdf-logo.png';
        $GLOBALS['printLogo']    = 'images/hmg_logo.jpg';
        $GLOBALS['printLogoClass']    = 'print-logo';
        $GLOBALS['footerText'] = $GLOBALS['footerText'] = 'helpmegrowutah.org |'
            . ' helpmegrow@unitedwayuc.org | 801-691-5322 | 148 North 100 West, Provo, UT 84604';
        $GLOBALS['certificate'] = [
            'vertical-line-color' => [152, 162, 0],
            'horz-thick-line-color' => [127, 97, 118],
            'horz-thin-line-color' => [225, 178, 46]
        ];
        break;
    default:
        $host       = 'localhost';
        $database   = 'importing_hmg_v3_backup1';
        $user       = 'root';
        $pass       = 'root';
        $GLOBALS['faxLogo']    = 'images/pdf-logo.png';
        $GLOBALS['pdfLogo']    = 'images/pdf-logo.png';
        $GLOBALS['printLogo']    = 'images/hmg_logo.jpg';
        $GLOBALS['printLogoClass']    = 'print-logo';
        $GLOBALS['footerText'] = $GLOBALS['footerText'] = 'helpmegrowutah.org |'
            . ' helpmegrow@unitedwayuc.org | 801-691-5322 | 148 North 100 West, Provo, UT 84604';
        $GLOBALS['certificate'] = [
            'vertical-line-color' => [152, 162, 0],
            'horz-thick-line-color' => [127, 97, 118],
            'horz-thin-line-color' => [225, 178, 46]
        ];
        $GLOBALS['agencyReportClass'] = '\PdfAgencyReferrals';
        $GLOBALS['sync_url'] = '/script/sync-asq.php';
		$GLOBALS['PdfCertificate'] = '\PdfCertificate';
		$GLOBALS['certificateLogo'] = 'images/MMGU-pdf-logo.png';
		$GLOBALS['PdfOrgCertificate'] = '\PdfOrgCertificate';
		$GLOBALS['faxCoverSheetClass'] = '\PdfFaxCoverSheet';
		$GLOBALS['PdfOrgCertificateLogo'] = 'images/MMGU-pdf-logo.png';
		
}

$db = new Hmg\Controllers\DbController($host, $user, $pass, $database);
$db->connect();

$action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : '');

if (!is_null($action)) {
    $action = strtolower($action);
} else {
    $action = null;
}

function frontController($action)
{

    session_start();

    $GLOBALS['action'] = $action;

    $loginController =  new Hmg\Controllers\LoginController();

    switch($action){

        case 'login':
            $user = new Hmg\Models\User($_POST['Email'], $_POST['Password']);
            $loginController =  new Hmg\Controllers\LoginController($user, $_POST['Password']);
            break;

        case 'logout':
            if ($_SESSION['user']['id']) {
                $_SESSION = array();
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(
                        session_name(),
                        '',
                        time() - 42000,
                        $params["path"],
                        $params["domain"],
                        $params["secure"],
                        $params["httponly"]
                    );
                }
                session_destroy();
            }
            header("Location: index.php");
            break;

        case 'families':
            $loginController->checkLoggedIn();
            $familiesController = new Hmg\Controllers\FamiliesController();
            break;

        case 'city':
            $loginController->checkLoggedIn();
            $cityController = new Hmg\Controllers\CityController();
            break;

        case 'family':
            $loginController->checkLoggedIn();
            $familyController = new Hmg\Controllers\FamilyController();
            break;

        case 'organizations':
            $loginController->checkLoggedIn();
            $organizationsController = new Hmg\Controllers\OrganizationsController();
            break;
        case 'organization':
            $loginController->checkLoggedIn();
            $organizationController = new Hmg\Controllers\OrganizationController();
            break;
        case 'contact':
            $loginController->checkLoggedIn();
            $contactController = new Hmg\Controllers\ContactController();
            break;
        case 'event':
            $loginController->checkLoggedIn();
            $eventController = new Hmg\Controllers\EventController();
            break;

        case 'child':
            $loginController->checkLoggedIn();
            $childController = new Hmg\Controllers\ChildController();
            break;

        case 'child-referral':
            $loginController->checkLoggedIn();
            $childReferralController = new Hmg\Controllers\ChildReferralController();
            break;

        case 'child-follow-up':
            $loginController->checkLoggedIn();
            $childFollowUpController = new Hmg\Controllers\ChildFollowUpController();
            break;

        case 'contact-referral':
            $loginController->checkLoggedIn();
            $contactReferralController = new Hmg\Controllers\ContactReferralController();
            break;

        case 'contact-follow-up':
            $loginController->checkLoggedIn();
            $contactFollowUpController = new Hmg\Controllers\ContactFollowUpController();
            break;

        case 'child-prior-resource':
            $loginController->checkLoggedIn();
            $childPriorResourceController = new Hmg\Controllers\ChildPriorResourceController();
            break;

        case 'child-developmental-screening':
            $loginController->checkLoggedIn();
            $childDevelopmentalScreeningController = new Hmg\Controllers\ChildDevelopmentalScreeningController();
            break;
			
		case 'event-file-attachment':
            $loginController->checkLoggedIn();
            $eventFileControllerController = new Hmg\Controllers\EventFileController();
            break;

		case 'contact-attachments':
            $loginController->checkLoggedIn();
            $contactFileControllerController = new Hmg\Controllers\ContactFileController();
            break;	
			

        case 'screening-attachment':
            $loginController->checkLoggedIn();
            $screeningAttachmentController = new Hmg\Controllers\ScreeningAttachmentController();
            break;
		case 'family-attachments':
            $loginController->checkLoggedIn();
            $screeningAttachmenstController = new Hmg\Controllers\FamilyAttachmentsController();
            break;
		case 'family-screening':
            $loginController->checkLoggedIn();
            $childDevelopmentalScreeningController = new Hmg\Controllers\FamilyScreeningController();
            break;

        case 'family-screening-attachment':
            $loginController->checkLoggedIn();
            $screeningAttachmentController = new Hmg\Controllers\FamilyScreeningAttachmentController();
            break;
        case 'family-screening-attachment':
            $loginController->checkLoggedIn();
            $screeningAttachmentController = new Hmg\Controllers\FamilyScreeningAttachmentController();
            break;

        case 'screening-attachments':
            $loginController->checkLoggedIn();
            $screeningAttachmenstController = new Hmg\Controllers\ScreeningAttachmentsController();
            break;
        case 'family-screening-attachments':
            $loginController->checkLoggedIn();
            $screeningAttachmenstController = new Hmg\Controllers\FamilyScreeningAttachmentsController();
            break;

        case 'screenings-file':
            $loginController->checkLoggedIn();
            $screeningsFileController = new Hmg\Controllers\ScreeningsFileController();
            break;
        case 'family-screenings-file':
            $loginController->checkLoggedIn();
            $screeningsFileController = new Hmg\Controllers\FamilyScreeningsFileController();
            break;

        case 'providers':
            $loginController->checkLoggedIn();
            $providersController = new Hmg\Controllers\ProvidersController();
            break;

        case 'provider':
            $loginController->checkLoggedIn();
            $providerController = new Hmg\Controllers\ProviderController();
            break;

        case 'family-provider':
            $loginController->checkLoggedIn();
            $familyProviderController = new Hmg\Controllers\FamilyProviderController();
            break;

        case 'family-start-end':
            $loginController->checkLoggedIn();
            $familyStartEndController = new Hmg\Controllers\FamilyStartEndController();
            break;

        case 'family-start-ends':
            $loginController->checkLoggedIn();
            $familyStartEndsController = new Hmg\Controllers\FamilyStartEndsController();
            break;

        case 'family-attachments':
            $loginController->checkLoggedIn();
            $familyAttachmentsController = new Hmg\Controllers\FamilyAttachmentsController();
            break;

        case 'volunteers':
            $loginController->checkLoggedIn();
            $volunteersController = new Hmg\Controllers\VolunteersController();
            break;

        case 'volunteer':
            $loginController->checkLoggedIn();
            $volunteerController = new Hmg\Controllers\VolunteerController();
            break;

        case 'volunteering':
            $loginController->checkLoggedIn();
            $volunteerController = new Hmg\Controllers\VolunteeringController();
            break;

        case 'counts':
            $loginController->checkLoggedIn();
            $countsController = new Hmg\Controllers\CountsController();
            break;

        case 'users':
            $loginController->checkLoggedIn();
            $usersController = new Hmg\Controllers\UsersController();
            break;

        case 'user':
            $loginController->checkLoggedIn();
            $userController = new Hmg\Controllers\UserController();
            break;

        case 'settings':
            $loginController->checkLoggedIn();
            $settings = new Hmg\Controllers\SettingsController();
            break;

        case 'setting':
            $loginController->checkLoggedIn();
            $setting = new Hmg\Controllers\SettingController();
            break;

        case 'referral-services':
            $loginController->checkLoggedIn();
            $referralServices = new Hmg\Controllers\ReferralServiceController();
            break;

        case 'region-counties':
            $loginController->checkLoggedIn();
            $regionCounties = new Hmg\Controllers\RegionCountiesController();
            break;

        case 'note':
            $loginController->checkLoggedIn();
            $note = new Hmg\Controllers\NoteController();
            break;

        case 'organization-note':
            $loginController->checkLoggedIn();
            $organizationNote = new Hmg\Controllers\OrganizationNoteController();
            break;

        case 'child-note':
            $loginController->checkLoggedIn();
            $childNote = new Hmg\Controllers\ChildNoteController();
            break;

        case 'contact-note':
            $loginController->checkLoggedIn();
            $contactNote = new Hmg\Controllers\ContactNoteController();
            break;

        case 'zip':
            $loginController->checkLoggedIn();
            $zip = new Hmg\Controllers\ZipController();
            break;

        case 'letter':
            $loginController->checkLoggedIn();
            $letterController = new Hmg\Controllers\LetterController();
            break;

        case 'family-referral':
            $loginController->checkLoggedIn();
            $familyReferralController = new Hmg\Controllers\FamilyReferralController();
            break;

        case 'family-follow-up':
            $loginController->checkLoggedIn();
            $familyFollowUpController = new Hmg\Controllers\FamilyFollowUpController();
            break;
		
			
		case 'organization-follow-ups':
            $loginController->checkLoggedIn();
            $OrganizationfollowUpsController = new Hmg\Controllers\OrganizationFollowUpsController();
            break;
			
		case 'organization-follow-up':
            $loginController->checkLoggedIn();
            $OrganizationfollowUpController = new Hmg\Controllers\OrganizationFollowUpController();
            break;
			
	   case 'organization-attachments':
            $loginController->checkLoggedIn();
            $organizationAttachmentsController = new Hmg\Controllers\OrganizationAttachmentsController();
            break;	
       
		
		case 'organization-file':
            $loginController->checkLoggedIn();
            $organizationFileController = new Hmg\Controllers\OrganizationFileController();
            break;	
			
			
	   case 'organization-event-attachments':
            $loginController->checkLoggedIn();
            $organizationEventAttachmentController = new Hmg\Controllers\OrganizationEventAttachmentController();
            break;	
  		
        case 'follow-ups':
            $loginController->checkLoggedIn();
            $followUpsController = new Hmg\Controllers\FollowUpsController();
            break;

        case 'file':
            $loginController->checkLoggedIn();
            $fileController = new Hmg\Controllers\FileController();
            break;

        case 'reports':
            $loginController->checkLoggedIn();
            $reportController = new Hmg\Controllers\ReportsController();
            break;

        case 'refresh-session':
            $loginController->checkLoggedIn();
            $refreshController = new Hmg\Controllers\RefreshController();
            break;

        case 'import':
            $loginController->checkLoggedIn();
            $importController = new Hmg\Controllers\ImportController();
            break;
        case 'report':
            $loginController->checkLoggedIn();
            $reportController = new Hmg\Controllers\ReportController();
            break;
        case 'organization-start-end':
            $loginController->checkLoggedIn();
            $familyStartEndController = new Hmg\Controllers\OrganizationStartEndController();
            break;

        case 'organization-start-ends':
            $loginController->checkLoggedIn();
            $familyStartEndsController = new Hmg\Controllers\OrganizationStartEndsController();
            break;        
            
        case 'referral-org':
            $loginController->checkLoggedIn();
            $referralServices = new Hmg\Controllers\ReferralServiceController();
            break;

        default:
            $loginController->checkLoggedIn();
            $loginController->displayLoggedIn($_SESSION['user']);
    }
}

frontController($action);
