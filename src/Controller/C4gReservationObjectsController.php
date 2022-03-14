<?php
/*
 * This file is part of con4gis, the gis-kit for Contao CMS.
 * @package con4gis
 * @version 8
 * @author con4gis contributors (see "authors.txt")
 * @license LGPL-3.0-or-later
 * @copyright (c) 2010-2022, by Küstenschmiede GmbH Software & Design
 * @link https://www.con4gis.org
 */

namespace con4gis\ReservationBundle\Controller;

use con4gis\CoreBundle\Classes\C4GVersionProvider;
use con4gis\CoreBundle\Classes\Helper\StringHelper;
use con4gis\ProjectsBundle\Classes\Actions\C4GBrickActionType;
use con4gis\ProjectsBundle\Classes\Actions\C4GSaveAndRedirectDialogAction;
use con4gis\ProjectsBundle\Classes\Buttons\C4GBrickButton;
use con4gis\ProjectsBundle\Classes\Common\C4GBrickCommon;
use con4gis\ProjectsBundle\Classes\Common\C4GBrickConst;
use con4gis\ProjectsBundle\Classes\Conditions\C4GBrickCondition;
use con4gis\ProjectsBundle\Classes\Conditions\C4GBrickConditionType;
use con4gis\ProjectsBundle\Classes\Fieldlist\C4GBrickFieldSourceType;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GButtonField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GCheckboxField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GDateField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GDateTimePickerField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GDecimalField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GEmailField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GFileField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GForeignArrayField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GForeignKeyField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GImageField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GInfoTextField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GKeyField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GMultiCheckboxField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GNumberField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GPostalField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GSelectField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GSignaturePadField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GSubDialogField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GTelField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GTextareaField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GTextField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GTimepickerField;
use con4gis\ProjectsBundle\Classes\Fieldtypes\C4GTrixEditorField;
use con4gis\ProjectsBundle\Classes\Files\C4GBrickFileType;
use con4gis\ProjectsBundle\Classes\Framework\C4GBaseController;
use con4gis\ProjectsBundle\Classes\Framework\C4GController;
use con4gis\ProjectsBundle\Classes\Views\C4GBrickViewType;
use con4gis\ReservationBundle\Classes\Models\C4gReservationLocationModel;
use con4gis\ReservationBundle\Classes\Models\C4gReservationModel;
use con4gis\ReservationBundle\Classes\Models\C4gReservationObjectModel;
use con4gis\ReservationBundle\Classes\Models\C4gReservationParamsModel;
use con4gis\ReservationBundle\Classes\Models\C4gReservationTypeModel;
use con4gis\ReservationBundle\Classes\Projects\C4gReservationBrickTypes;
use con4gis\ReservationBundle\Classes\Utils\C4gReservationCalculator;
use con4gis\ReservationBundle\Classes\Utils\C4gReservationHandler;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class C4gReservationObjectsController extends C4GBaseController
{
    public const TYPE = 'C4gReservationObjects';

    protected $tableName    = 'tl_c4g_reservation_object';
    protected $modelClass   = C4gReservationObjectModel::class;
    protected $languageFile = 'fe_c4g_reservation_objects';
    protected $brickKey     = C4gReservationBrickTypes::BRICK_RESERVATION_OBJECTS;
    protected $viewType     = C4GBrickViewType::MEMBERBASED;
    protected $sendEMails   = null;
    protected $withNotification = false;
    protected $permalink_name = 'object';

    //Resource Params
    protected $loadDefaultResources = true;
    protected $loadTrixEditorResources = true;
    protected $loadDateTimePickerResources = false;
    protected $loadChosenResources = false;
    protected $loadClearBrowserUrlResources = false;
    protected $loadConditionalFieldDisplayResources = false;
    protected $loadMoreButtonResources = false;
    protected $loadFontAwesomeResources = true;
    protected $loadTriggerSearchFromOtherModuleResources = false;
    protected $loadFileUploadResources = true;
    protected $loadMultiColumnResources = false;
    protected $loadMiniSearchResources = false;
    protected $loadHistoryPushResources = true;

    protected $loadSignaturePadResources = true;

    //JQuery GUI Resource Params
    protected $jQueryAddCore = true;
    protected $jQueryAddJquery = true;
    protected $jQueryAddJqueryUI = true;
    protected $jQueryUseTree = false;
    protected $jQueryUseTable = true;
    protected $jQueryUseHistory = false;
    protected $jQueryUseTooltip = false;
    protected $jQueryUseMaps = false;
    protected $jQueryUseGoogleMaps = false;
    protected $jQueryUseMapsEditor = false;
    protected $jQueryUseWswgEditor = false;
    protected $jQueryUseScrollPane = true;
    protected $jQueryUsePopups = false;

    protected $withPermissionCheck = false;


    /**
     * @param string $rootDir
     * @param Session $session
     * @param ContaoFramework $framework
     */
    public function __construct(string $rootDir, Session $session, ContaoFramework $framework, ModuleModel $model = null)
    {
        parent::__construct($rootDir, $session, $framework, $model);
    }

    /**
     * @param Template $template
     * @param ModuleModel $model
     * @param Request $request
     * @return Response|null
     */
    public function getResponse(Template $template, ModuleModel $model, Request $request): ?Response {
        $result = parent::getResponse($template, $model, $request);

        return $result;
    }

    /**
     * @param $id
     * @return void
     */
    public function initBrickModule($id)
    {
        parent::initBrickModule($id);

        $this->listParams->setScrollX(false);
        $this->listParams->setResponsive(true);

        if ($this->login_redirect_site) {
            $this->dialogParams->getViewParams()->setLoginRedirect($this->login_redirect_site);
        }

    }

    public function addFields() : array
    {
        $fieldList = array();

        $idField = new C4GKeyField();
        $idField->setFieldName('id');
        $idField->setEditable(false);
        $idField->setFormField(false);
        $idField->setSortColumn(false);
        $idField->setPrintable(false);
        $fieldList[] = $idField;

        $ignorePostal = false;
        if ($this->postals) {
            $ignorePostal = true;
            if (FE_USER_LOGGED_IN === true) {
                $member = FrontendUser::getInstance();
                if ($member) {
                    $postals = explode(',', $this->postals);
                    foreach ($postals as $postal) {
                        if (trim($postal) == $member->postal) {
                            $ignorePostal = false;
                            break;
                        }
                    }
                }
            }

            if ($ignorePostal) {
                $this->dialogParams->setWithoutGuiHeader(true);
                $this->dialogParams->deleteButton(C4GBrickConst::BUTTON_SAVE);
                $this->dialogParams->deleteButton(C4GBrickConst::BUTTON_SAVE_AND_NEW);
                $this->dialogParams->deleteButton(C4GBrickConst::BUTTON_DELETE);
                $this->dialogParams->setIgnoreChanges(true);
                $this->setDialogParams($this->dialogParams);

                $info = new C4GInfoTextField();
                $info->setFieldName('info');
                $info->setEditable(false);
                $info->setInitialValue($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['wrong_postal']);
                $info->setDatabaseField(false);
                $info->setFormField(true);
                $info->setComparable(false);
                $fieldList[] = $info;
                return $fieldList;
            }
        }

        $typeArray = StringUtil::deserialize($this->reservation_object_types);
        if ($typeArray) {
            $typeIds = implode(',',$typeArray);
            $t = 'tl_c4g_reservation_type';
            $arrValues = array();
            $arrOptions = array('order' => "$t.caption ASC, $t.options ASC",);
            $arrColumns = array("$t.published='1' AND NOT $t.reservationObjectType='2' AND $t.id IN($typeIds)");
            $types = C4gReservationTypeModel::findBy($arrColumns, $arrValues, $arrOptions);
            $typelist = [];
            foreach ($types as $type) {
                $typelist[] = ['id'=>$type->id, 'name'=>$type->caption]; //ToDo options!!!
            }

            $reservationTypeField = new C4GMultiCheckboxField();
//            $reservationTypeField->setChosen(true);
            $reservationTypeField->setFieldName('viewableTypes');
            $reservationTypeField->setTitle($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['viewableTypes']);
            $reservationTypeField->setSortColumn(false);
            $reservationTypeField->setTableColumn(true);
            //$reservationTypeField->setColumnWidth(20);
            //$reservationTypeField->setSize(1); //count($typelist)
            $reservationTypeField->setOptions($typelist);
            $reservationTypeField->setMandatory(true);
            $reservationTypeField->setNotificationField(true);
            $reservationTypeField->setInitialValue($typelist[0]);
            $reservationTypeField->setHidden(count($typelist) == 1);
            $fieldList[] = $reservationTypeField;
        } else {
            //FEHLER KEINE ART AUSGEWÄHLT
        }

        $captionField = new C4GTextField();
        $captionField->setFieldName('caption');
        $captionField->setTitle($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['caption']);
        $captionField->setSortColumn(false);
        $captionField->setTableColumn(true);
        $captionField->setMandatory(true);
        $captionField->setNotificationField(true);
        $captionField->setTableColumnPriority(2);
        $fieldList[] = $captionField;

        $quantityField = new C4GNumberField();
        $quantityField->setFieldName('quantity');
        $quantityField->setTitle($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['quantity']);
        $quantityField->setInitialValue(1);
        $quantityField->setFormField(true);
        $quantityField->setTableColumn(true);
        $fieldList[] = $quantityField;

        $descriptionField = new C4GTrixEditorField();
        $descriptionField->setFieldName('description');
        $descriptionField->setTitle($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['description']);
        $descriptionField->setFormField(true);
        $descriptionField->setTableColumn(false);
        $descriptionField->setNotificationField(true);
        $fieldList[] = $descriptionField;

        $condition = new C4GBrickCondition(C4GBrickConditionType::BOOLSWITCH, 'image');

        $imgField = new C4GImageField();
        $imgField->setFieldName('img');
        //$imgField->setTitle($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['image']);
        $imgField->setShowIfEmpty(false);
        $imgField->setFileTypes(C4GBrickFileType::IMAGES_PNG_JPG);
        $imgField->setFormField(true);
        $imgField->setTableColumn(false);
        $imgField->setSize(500);
        $imgField->setDatabaseField(false);
        $imgField->setSource(C4GBrickFieldSourceType::OTHER_FIELD);
        $imgField->setSourceField('image');
        $imgField->setCondition($condition);
        $fieldList[] = $imgField;

        $imgFileField = new C4GFileField();
        $imgFileField->setFieldName('image');
        $imgFileField->setTitle($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['image']);
        $imgFileField->setShowIfEmpty(true);
        $imgFileField->setFileTypes(C4GBrickFileType::IMAGES_PNG_JPG);
        $imgFileField->setFormField(true);
        $imgFileField->setCallOnChange(true);
        $fieldList[] = $imgFileField;

        $quantityField = new C4GSelectField();
        $quantityField->setFieldName('priceoption');
        $quantityField->setInitialValue('pAmount');
        $quantityField->setFormField(false);
        $fieldList[] = $quantityField;

        $priceField = new C4GDecimalField();
        $priceField->setFieldName('price');
        $priceField->setTitle($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['price']);
        //$priceField->setInitialValue(0);
        $priceField->setFormField(true);
        $priceField->setTableColumn(true);
//        $priceField->setDecimalPoint('.');
        $priceField->setDecimals(2);
        $fieldList[] = $priceField;

        //Location
        $locationKey = new C4GKeyField();
        $locationKey->setFieldName('id');
        $locationKey->setComparable(false);
        $locationKey->setEditable(false);
        $locationKey->setHidden(true);
        $locationKey->setFormField(true);

        $locationForeign = new C4GForeignKeyField();
        $locationForeign->setFieldName('member_id');
        $locationForeign->setHidden(true);
        $locationForeign->setFormField(true);

        $locationFieldArr = [];

        $nameField = new C4GTextField();
        $nameField->setFieldName('name');
        $nameField->setTitle('Bezeichnung');
        $nameField->setColumnWidth(10);
        $nameField->setSortColumn(false);
        $nameField->setTableColumn(true);
        $nameField->setMandatory(true);
        $nameField->setNotificationField(false);
        $locationFieldArr[] = $nameField;

        $contactNameField = new C4GTextField();
        $contactNameField->setFieldName('contact_name');
        $contactNameField->setTitle('Name');
        $contactNameField->setColumnWidth(10);
        $contactNameField->setSortColumn(false);
        $contactNameField->setTableColumn(true);
        $contactNameField->setMandatory(true);
        $contactNameField->setNotificationField(false);
        $locationFieldArr[] = $contactNameField;

        $contactEmailField = new C4GEmailField();
        $contactEmailField->setFieldName('contact_email');
        $contactEmailField->setTitle('E-Mail');
        $contactEmailField->setColumnWidth(10);
        $contactEmailField->setSortColumn(false);
        $contactEmailField->setTableColumn(false);
        $contactEmailField->setMandatory($rowMandatory);
        $contactEmailField->setNotificationField(false);
        $locationFieldArr[] = $contactEmailField;

//        $locationFields = new C4GSubDialogField();
//        $locationFields->setFieldName('locationFields');
//        $locationFields->setTitle('Ort');
//        $locationFields->setShowButtons(false);
//        $locationFields->setTable('tl_c4g_reservation_location');
//        $locationFields->addFields($locationFieldArr);
//        $locationFields->setKeyField($locationKey);
//        $locationFields->setForeignKeyField($locationForeign);
//        $locationFields->setModelClass(C4gReservationLocationModel::class);
//        $locationFields->setMandatory(true);
//        $locationFields->setNotificationField(false);
//        $locationFields->setShowDataSetsByCount(1);
//        //$location->setParentFieldList($fieldList);
//        $locationFields->setDelimiter('§');
//        $locationFields->setSaveInNewDataset(true);
//        $locationFields->setShowFirstDataSet(true);
//        $fieldList[] = $locationFields;

        $publishedField = new C4GCheckboxField();
        $publishedField->setFieldName('published');
        $publishedField->setTitle($GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['published']);
        $publishedField->setSortColumn(false);
        $publishedField->setTableColumn(true);
        $publishedField->setNotificationField(true);
        $fieldList[] = $publishedField;

//        $clickButton = new C4GBrickButton(
//            C4GBrickConst::BUTTON_CLICK,
//            $GLOBALS['TL_LANG']['fe_c4g_reservation_objects']['buttonSaveObject'],
//            $visible = true,
//            $enabled = true,
//            $action = '',
//            $accesskey = '',
//            $defaultByEnter = true);
//
//        $buttonField = new C4GButtonField($clickButton);
//        $buttonField->setOnClickType(C4GBrickConst::ONCLICK_TYPE_SERVER);
//        $buttonField->setOnClick('saveObject');
//        $buttonField->setWithoutLabel(true);
//        $fieldList[] = $buttonField;

        return $fieldList;
    }

    /**
     * @param $values
     * @param $putVars
     * @return void
     */
    public function saveObject($values, $putVars)
    {
        $fieldList = $this->getFieldList();
        $action = new C4GSaveAndRedirectDialogAction($this->getDialogParams(), $this->getListParams(), $fieldList, $putVars, $this->getBrickDatabase());
        $action->setModule($this);
        $result = $action->run();
    }

}

